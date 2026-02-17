<?php
/* =======================
   CONFIG
======================= */
$router   = "http://192.168.100.104:8080";
$password = "12Serya"; // router password
$cookie   = tempnam(sys_get_temp_dir(), 'tenda_'); // temporary cookie file

// Devices allowed full internet (lowercase MACs)
$ALLOW_LIST = [
    // "aa:bb:cc:dd:ee:ff",
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
   LOGIN
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
   BUILD FULL QOS TABLE
======================= */
$qosList = "";
$allowListNorm = array_map('strtolower', $ALLOW_LIST);

foreach ($online as $dev) {
    $mac  = strtolower($dev["qosListMac"] ?? "");
    if (!$mac) continue;

    $type  = $dev["qosListConnectType"] ?? "";
    $allow = in_array($mac, $allowListNorm, true);

    if ($type === "wifi" && !$allow) {
        // Block Wi-Fi devices
        $access = "false";
        $up     = "0";
        $down   = "0";
    } else {
        // Allowed Wi-Fi or Ethernet
        $access = "true";
        $up     = $dev["qosListUpLimit"] ?? "0";
        $down   = $dev["qosListDownLimit"] ?? "0";
    }

    $qosList .= sprintf(
        "%s\t%s\t%s\t%s\t%s\t%s\n",
        $dev["qosListHostname"] ?? "",
        $dev["qosListRemark"]  ?? "",
        $mac,
        $up,
        $down,
        $access
    );
}

// Append blacklisted devices
foreach ($black as $dev) {
    $mac = strtolower($dev["qosListMac"] ?? "");
    if (!$mac) continue;
    $qosList .= sprintf(
        "%s\t%s\t%s\t0\t0\tfalse\n",
        $dev["qosListHostname"] ?? "",
        $dev["qosListRemark"]  ?? "",
        $mac
    );
}

/* =======================
   APPLY FULL QOS TABLE
======================= */
curl_post(
    $router . "/goform/setQos",
    [
        "module1" => "qosList",
        "qosList" => $qosList
    ],
    $cookie,
    $router . "/index.html"
);

/* =======================
   REFRESH AND DISPLAY STATUS
======================= */
$qos_json = curl_get(
    $router . "/goform/getQos?random=" . microtime(true) . "&modules=onlineList,blackList",
    $cookie,
    $router . "/index.html"
);
$qos = json_decode($qos_json, true);
$online = $qos["onlineList"] ?? [];

echo "\nONLINE DEVICES:\n";

foreach ($online as $dev) {
    $mac = strtolower($dev["qosListMac"] ?? "");
    $internet = in_array($mac, $allowListNorm, true) || $dev["qosListConnectType"] === "wired" ? "YES" : "NO";

    echo "----------------------\n";
    echo "Name : " . ($dev["qosListHostname"] ?? "unknown") . "\n";
    echo "IP   : " . ($dev["qosListIP"] ?? "") . "\n";
    echo "MAC  : $mac\n";
    echo "Type : " . ($dev["qosListConnectType"] ?? "") . "\n";
    echo "Internet: $internet\n";
}

// unlink($cookie); // optional cleanup
?>
