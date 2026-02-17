<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

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

// Only define createCookieFile if not already defined
if (!function_exists('createCookieFile')) {
    function createCookieFile() {
        return tempnam(sys_get_temp_dir(), 'cookie_');
    }
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
        $cookie = createCookieFile();
        curl_post("$router/login/Auth", ["password" => base64_encode($password)], $cookie);

        // -----------------------
        // FETCH CURRENT ONLINE & BLOCKED DEVICES
        // -----------------------
        $qos_json = curl_get("$router/goform/getQos?random=" . microtime(true) . "&modules=onlineList,macFilter", $cookie, "$router/index.html");
        $qos = json_decode($qos_json, true) ?: [];
        $online = $qos['onlineList'] ?? [];
        $black  = $qos['macFilterList'] ?? [];

        // Merge devices by MAC
        $devices = [];
        foreach (array_merge($online, $black) as $d) {
            if (!empty($d['qosListMac'])) {
                $mac = strtoupper($d['qosListMac']);
                $devices[$mac] = $d;
            }
        }

        // -----------------------
        // SYNC DEVICES TO DATABASE
        // -----------------------
        foreach ($devices as $mac => $dev) {
            $stmt = $db->prepare("
                INSERT INTO users (hostname, ip, mac, router_id, internet_access, connected_at)
                VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP)
                ON CONFLICT(mac, router_id)
                DO UPDATE SET hostname=excluded.hostname, ip=excluded.ip
            ");
            $stmt->execute([
                $dev['qosListHostname'] ?? 'unknown',
                $dev['qosListIP'] ?? '',
                $mac,
                $routerId
            ]);
        }

        // -----------------------
        // FETCH USERS & PREPARE LISTS
        // -----------------------
        $stmt = $db->prepare("SELECT mac, internet_access, hostname FROM users WHERE router_id = ?");
        $stmt->execute([$routerId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $onlineList = [];
        $macFilterList = [];

        foreach ($users as $u) {
            $mac = strtoupper($u['mac']);
            if (!isset($devices[$mac])) continue;

            $blocked = ((int)$u['internet_access'] === 0);
            $hostname = $u['hostname'] ?: $devices[$mac]['qosListHostname'] ?? 'unknown';
            $upLimit   = $devices[$mac]['qosListUpLimit'] ?? '0';
            $downLimit = $devices[$mac]['qosListDownLimit'] ?? '0';
            $access    = $blocked ? 'false' : 'true';

            $onlineList[] = "$hostname\t$hostname\t$mac\t$upLimit\t$downLimit\t$access";

            if ($blocked) {
                $macFilterList[] = "$hostname\t$hostname\t$mac\t$upLimit\t$downLimit\tfalse";
            }
        }

        $onlineListStr = implode("\n", $onlineList);
        $macFilterStr  = implode("\n", $macFilterList);

        // -----------------------
        // APPLY TO ROUTER (combined POST)
        // -----------------------
        $curFilterMode = count($macFilterList) > 0 ? 'deny' : 'pass';

        curl_post("$router/goform/setQos", [
            'module1'       => 'onlineList',
            'onlineList'    => $onlineListStr,
            'onlineListLen' => count($onlineList),
            'qosEn'         => '1',
            'qosAccessEn'   => '1',
            'module2'       => 'macFilter',
            'macFilterList' => $macFilterStr,
            'macFilterListLen' => count($macFilterList),
            'curFilterMode' => $curFilterMode
        ], $cookie, "$router/index.html");

        // -----------------------
        // SAVE SETTINGS
        // -----------------------
        curl_post("$router/goform/save", ['random' => time()], $cookie, "$router/index.html");

        $results[] = [
            'router_id'       => $routerId,
            'ip'              => $ip,
            'total_devices'   => count($devices),
            'blocked_devices' => count($macFilterList),
            'status'          => 'QoS + MAC filter enforced'
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
