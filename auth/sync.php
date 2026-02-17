<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

// -----------------------
// INTERNAL LOOP SETUP
// -----------------------
// Run once, then sleep 60 seconds before next iteration
$loop = true; // you can set to false if you only want it once

do {

// -----------------------
// DATABASE CONNECTION
// -----------------------
if (!file_exists(DB_PATH)) {
    die(json_encode(['error' => 'Database not found']));
}

$db = new PDO('sqlite:' . DB_PATH);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// -----------------------
// CURL HELPERS
// -----------------------
function curl_post($url, $data = [], $cookieFile = '', $referer = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($referer) curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    if ($res === false) throw new Exception('Curl POST error: ' . curl_error($ch));
    curl_close($ch);
    return $res;
}

function curl_get($url, $cookieFile = '', $referer = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($referer) curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    if ($res === false) throw new Exception('Curl GET error: ' . curl_error($ch));
    curl_close($ch);
    return $res;
}

// -----------------------
// GET ALL ROUTERS
// -----------------------
$routers = $db->query("SELECT * FROM routers")->fetchAll(PDO::FETCH_ASSOC);
if (!$routers) die(json_encode(['error' => 'No routers found']));

$results = [];

foreach ($routers as $routerData) {
    try {
        $ip       = $routerData['ip'];
        $port     = $routerData['port'] ?: 80;
        $routerId = $routerData['id'];
        $password = $routerData['password'];
        $router   = "http://$ip" . ($port != 80 ? ":$port" : "");

        // -----------------------
        // LOGIN
        // -----------------------
        $cookie = createCookieFile(); // from config.php
        curl_post("$router/login/Auth", ["password" => base64_encode($password)], $cookie);

        // -----------------------
        // FETCH CURRENT ONLINE DEVICES
        // -----------------------
        $qos_json = curl_get("$router/goform/getQos?random=" . microtime(true) . "&modules=onlineList,macFilter", $cookie, "$router/index.html");
        $qos = json_decode($qos_json, true) ?: [];
        $online = $qos['onlineList'] ?? [];

        // -----------------------
        // SYNC NEW DEVICES TO DATABASE
        // -----------------------
        foreach ($online as $d) {
            if (empty($d['qosListMac'])) continue;
            $mac = strtoupper($d['qosListMac']);
            $hostname = $d['qosListHostname'] ?? 'unknown';
            $ipAddr = $d['qosListIP'] ?? '';

            // Check if user already exists
            $stmt = $db->prepare("SELECT internet_access FROM users WHERE mac = ? AND router_id = ?");
            $stmt->execute([$mac, $routerId]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existingUser) {
                // New device -> default unpaid
                $stmtInsert = $db->prepare("
                    INSERT INTO users (hostname, ip, mac, router_id, internet_access, connected_at)
                    VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP)
                ");
                $stmtInsert->execute([$hostname, $ipAddr, $mac, $routerId]);
            } else {
                // Update hostname/IP only, preserve internet_access
                $stmtUpdate = $db->prepare("UPDATE users SET hostname = ?, ip = ? WHERE mac = ? AND router_id = ?");
                $stmtUpdate->execute([$hostname, $ipAddr, $mac, $routerId]);
            }
        }

        // -----------------------
        // FETCH FULL USERS LIST
        // -----------------------
        $stmt = $db->prepare("SELECT * FROM users WHERE router_id = ?");
        $stmt->execute([$routerId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $onlineList = [];
        $macFilterList = []; // optional, empty for throttling

        foreach ($users as $u) {
            $mac = strtoupper($u['mac']);
            $hostname = $u['hostname'] ?: 'unknown';

            // Determine bandwidth limits
            if ((int)$u['internet_access'] === 1) {
                // Paid user -> full speed
                $upLimit = 10240;
                $downLimit = 10240;
            } else {
                // Unpaid -> throttled
                $upLimit = 1;
                $downLimit = 1;
            }

            $onlineList[] = "$hostname\t$hostname\t$mac\t$upLimit\t$downLimit\ttrue";
        }

        $onlineListStr = implode("\n", $onlineList);
        $macFilterStr = implode("\n", $macFilterList);

        // -----------------------
        // PUSH TO ROUTER
        // -----------------------
        curl_post("$router/goform/setQos", [
            'module1'       => 'onlineList',
            'onlineList'    => $onlineListStr,
            'onlineListLen' => count($onlineList),
            'qosEn'         => '1',
            'qosAccessEn'   => '1'
        ], $cookie, "$router/index.html");

        curl_post("$router/goform/setMacFilter", [
            'macFilterEn'      => count($macFilterList) > 0 ? '1' : '0',
            'macFilterMode'    => 'deny',
            'macFilterList'    => $macFilterStr,
            'macFilterListLen' => count($macFilterList)
        ], $cookie, "$router/index.html");

        curl_post("$router/goform/save", ['random' => time()], $cookie, "$router/index.html");

        $results[] = [
            'router_id'        => $routerId,
            'ip'               => $ip,
            'total_devices'    => count($users),
            'throttled_devices'=> count(array_filter($users, fn($x) => (int)$x['internet_access'] === 0)),
            'status'           => 'QoS throttling enforced'
        ];

    } catch (Exception $e) {
        $results[] = [
            'router_id' => $routerData['id'],
            'ip'        => $routerData['ip'],
            'error'     => $e->getMessage()
        ];
    }
}

// -----------------------
// FINAL RESPONSE
// -----------------------
echo json_encode($results, JSON_PRETTY_PRINT);

// -----------------------
// WAIT 1 MINUTE BEFORE NEXT ITERATION
// -----------------------
if ($loop) {
    sleep(60); // wait 60 seconds
}

} while($loop);
