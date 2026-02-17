<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db/routers.db'; // adjust if needed
require_once __DIR__ . '/../config.php';      

// -----------------------------
// CURL helper
// -----------------------------
function callBillingApi($routerId, $paidMac = null, $planId = null) {
    $params = [
        'id' => $routerId
    ];
    if ($paidMac) $params['paid_mac'] = $paidMac;
    if ($planId)  $params['plan_id'] = $planId;

    $url = "http://127.0.0.1:8000/auth/billing.php?" . http_build_query($params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($res === false) {
        throw new RuntimeException("Failed to call billing.php: $err");
    }

    $data = json_decode($res, true);
    if (!is_array($data)) {
        throw new RuntimeException("Invalid JSON response from billing.php");
    }

    return $data;
}

// -----------------------------
// GET all routers
// -----------------------------
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../db/routers.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->query("SELECT id, name, ip, port FROM routers ORDER BY name ASC");
    $routers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($routers as $router) {
        $routerId = $router['id'];

        try {
            // Call billing.php to get devices + apply block rules
            $billingData = callBillingApi($routerId);

            // Normalize devices
            $devices = $billingData['devices'] ?? [];

            $activeUsers = [];
            $blockedUsers = [];

            foreach ($devices as $dev) {
                if ($dev['internet'] ?? false) {
                    $activeUsers[] = $dev;
                } else {
                    $blockedUsers[] = $dev;
                }
            }

            $router['active']  = $activeUsers;
            $router['blocked'] = $blockedUsers;
            $router['status']  = 'online'; // optionally can check TCP ping
        } catch (Exception $e) {
            $router['active']  = [];
            $router['blocked'] = [];
            $router['status']  = 'offline';
            $router['error']   = $e->getMessage();
        }

        $result[] = $router;
    }

    echo json_encode(['success' => true, 'routers' => $result]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
