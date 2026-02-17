<?php
require_once __DIR__ . '/../config.php';

/* =======================
   INPUT
======================= */
$data = json_decode(file_get_contents("php://input"), true);

$router_id = $data['id'] ?? null;
$macTarget = strtolower($data['mac'] ?? '');
$action    = $data['action'] ?? null;

if (!$router_id || !$macTarget || !in_array($action, ['block', 'unblock'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

/* =======================
   ROUTER CONFIG
======================= */
$routerData = getRouterConfig($router_id);
if (!$routerData) {
    http_response_code(404);
    echo json_encode(['error' => 'Router not found']);
    exit;
}

$ip   = $routerData['ip'];
$port = $routerData['port'] ?: 80;
$router = "http://$ip" . ($port != 80 ? ":$port" : "");

/* =======================
   REUSE EXISTING SESSION
======================= */
$cookie = createCookieFile($router_id);

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
   FETCH QOS (SESSION ALREADY AUTHED)
======================= */
$qos_json = curl_get(
    $router . "/goform/getQos?modules=onlineList,blackList",
    $cookie,
    $router . "/index.html"
);

$qos = json_decode($qos_json, true);
if (!is_array($qos)) {
    http_response_code(401);
    echo json_encode(['error' => 'Session expired – call /auth/login.php first']);
    exit;
}

$online = $qos['onlineList'] ?? [];
$black  = $qos['blackList'] ?? [];

/* =======================
   REBUILD FULL QOS TABLE
======================= */
$qosList = "";

/* ONLINE */
foreach ($online as $dev) {
    $macOrig = $dev['qosListMac'] ?? '';
    if (!$macOrig) continue;

    $macNorm = strtolower($macOrig);
    $access  = $dev['qosListAccess'] ?? 'true';

    if ($macNorm === $macTarget) {
        $access = ($action === 'block') ? 'false' : 'true';
    }

    $qosList .= sprintf(
        "%s\t%s\t%s\t%s\t%s\t%s\n",
        $dev['qosListHostname'] ?? '',
        $dev['qosListRemark'] ?? '',
        $macOrig,
        $dev['qosListUpLimit'] ?? '0',
        $dev['qosListDownLimit'] ?? '0',
        $access
    );
}

/* BLACKLIST */
foreach ($black as $dev) {
    $macOrig = $dev['qosListMac'] ?? '';
    if (!$macOrig) continue;

    $macNorm = strtolower($macOrig);

    if ($macNorm === $macTarget && $action === 'unblock') {
        continue;
    }

    $qosList .= sprintf(
        "%s\t%s\t%s\t%s\t%s\tfalse\n",
        $dev['qosListHostname'] ?? '',
        $dev['qosListRemark'] ?? '',
        $macOrig,
        $dev['qosListUpLimit'] ?? '0',
        $dev['qosListDownLimit'] ?? '0'
    );
}

/* =======================
   PUSH FULL REPLACEMENT
======================= */
$res = curl_post(
    $router . "/goform/setQos",
    [
        'module1' => 'qosList',
        'qosList' => $qosList
    ],
    $cookie,
    $router . "/index.html"
);

$json = json_decode($res, true);
if (($json['errCode'] ?? '1') !== '0') {
    http_response_code(500);
    echo json_encode(['error' => 'QoS update failed']);
    exit;
}

/* =======================
   RESPONSE
======================= */
echo json_encode([
    'status' => 'ok',
    'mac'    => $macTarget,
    'action' => $action
]);
