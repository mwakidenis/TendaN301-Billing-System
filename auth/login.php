<?php
// /auth/login.php
require_once __DIR__ . '/config.php';

// Get router ID from frontend request
$router_id = $_GET['id'] ?? null;
if (!$router_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Router ID not specified.']);
    exit;
}

// Fetch router configuration from database
$routerData = getRouterConfig($router_id);
if (!$routerData) {
    http_response_code(404);
    echo json_encode(['error' => 'Router not found.']);
    exit;
}

// Build router URL
$ip   = $routerData['ip'];
$port = $routerData['port'] ?: 80; // default to 80 if not specified
$router = "http://$ip" . ($port != 80 ? ":$port" : "");
$password = $routerData['password'];

// Create a cookie file for this session
$cookie = createCookieFile();

// Devices allowed full internet (lowercase MACs)
$ALLOW_LIST = [
    // Add allowed MACs here if needed
];

/* =======================
   CURL HELPERS
======================= */
function curl_post($url, $data, $cookie, $referer = null) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE     => $cookie,
        CURLOPT_COOKIEJAR      => $cookie,
        CURLOPT_USERAGENT      => "Mozilla/5.0",
        CURLOPT_REFERER        => $referer ?? $url,
        CURLOPT_TIMEOUT        => 5,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function curl_get($url, $cookie, $referer = null) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE     => $cookie,
        CURLOPT_COOKIEJAR      => $cookie,
        CURLOPT_USERAGENT      => "Mozilla/5.0",
        CURLOPT_REFERER        => $referer ?? $url,
        CURLOPT_TIMEOUT        => 5,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

/* =======================
   LOGIN TO ROUTER
======================= */
curl_post(
    $router . "/login/Auth",
    ["password" => base64_encode($password)],
    $cookie
);

/* =======================
   FETCH CURRENT QOS
======================= */
$qos_json = curl_get(
    $router . "/goform/getQos?random=" . microtime(true) . "&modules=onlineList,blackList",
    $cookie,
    $router . "/index.html"
);

$qos = json_decode($qos_json, true);
$online = $qos["onlineList"] ?? [];
$black  = $qos["blackList"] ?? [];

/* =======================
   BUILD RESPONSE
======================= */
$allowListNorm = array_map('strtolower', $ALLOW_LIST);
$devices = [];

// Online devices
foreach ($online as $dev) {
    $mac = strtolower($dev["qosListMac"] ?? "");
    if (!$mac) continue;

    $devices[] = [
        'hostname' => $dev["qosListHostname"] ?? "unknown",
        'ip'       => $dev["qosListIP"] ?? "",
        'mac'      => $mac,
        'type'     => $dev["qosListConnectType"] ?? "",
        'internet' => in_array($mac, $allowListNorm, true) || $dev["qosListConnectType"] === "wired" ? true : false
    ];
}

// Blacklisted devices
foreach ($black as $dev) {
    $mac = strtolower($dev["qosListMac"] ?? "");
    if (!$mac) continue;

    $devices[] = [
        'hostname' => $dev["qosListHostname"] ?? "unknown",
        'ip'       => $dev["qosListIP"] ?? "",
        'mac'      => $mac,
        'type'     => $dev["qosListConnectType"] ?? "",
        'internet' => false
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'router'  => ['id' => $router_id, 'name' => $routerData['name']],
    'devices' => $devices
]);

// Cleanup cookie
// unlink($cookie); // optional
