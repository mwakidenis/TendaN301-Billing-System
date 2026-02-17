<?php
// billWorker.php
// This script runs continuously to update user billing and mark expired users

// Set infinite execution time
set_time_limit(0);

// Path to SQLite DB
$dbPath = __DIR__ . '/../db/routers.db';

// Connect to the database
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "Failed to connect to DB: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Interval in seconds to check users
$CHECK_INTERVAL = 60; // every 1 minute

// Helper: Format time (optional for logging)
function formatTime($seconds) {
    $d = floor($seconds / 86400);
    $h = floor(($seconds % 86400) / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return "{$d}d {$h}h {$m}m {$s}s";
}

echo "[" . date('Y-m-d H:i:s') . "] Billing worker started." . PHP_EOL;

// Main loop
while (true) {
    try {
        // Fetch all users
        $users = $db->query("SELECT * FROM billing")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $remainingTime = $user['remaining_time'];

            // Deduct CHECK_INTERVAL seconds
            $remainingTime -= $CHECK_INTERVAL;

            if ($remainingTime <= 0) {
                // Mark user as expired/unpaid
                $remainingTime = 0;

                // Optional: mark user as inactive / revoke access
                $updateUser = $db->prepare("UPDATE users SET internet_access = 0 WHERE mac = ? AND router_id = ?");
                $updateUser->execute([$user['mac'], $user['router_id']]);

                echo "[" . date('Y-m-d H:i:s') . "] User {$user['name']} expired, access revoked." . PHP_EOL;
            }

            // Update remaining time in billing table
            $updateBilling = $db->prepare("UPDATE billing SET remaining_time = ? WHERE id = ?");
            $updateBilling->execute([$remainingTime, $user['id']]);

            echo "[" . date('Y-m-d H:i:s') . "] User {$user['name']} remaining: " . formatTime($remainingTime) . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . PHP_EOL;
    }

    // Sleep until next check
    sleep($CHECK_INTERVAL);
}
