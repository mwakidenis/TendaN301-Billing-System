<?php
ob_start();

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../auth/config.php';

// ---------------------------
// Connect to SQLite Database
// ---------------------------
$db = new PDO('sqlite:' . __DIR__ . '/../db/routers.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ---------------------------
// CURL helpers (same as add_user.php)
// ---------------------------
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

// ---------------------------
// AJAX for throttling a user manually
// ---------------------------
if (isset($_POST['ajax_throttle_user_id'])) {
    $uid = $_POST['ajax_throttle_user_id'];

    // Mark user as expired in DB
    $stmt = $db->prepare("UPDATE billing SET remaining_time = 0 WHERE id = ?");
    $stmt->execute([$uid]);

    // Get user and router info
    $stmtUser = $db->prepare("SELECT b.mac, b.router_id, r.ip, r.port, r.password FROM billing b JOIN routers r ON b.router_id = r.id WHERE b.id = ?");
    $stmtUser->execute([$uid]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $routerId = $user['router_id'];
        $ip = $user['ip'];
        $port = $user['port'] ?: 80;
        $password = $user['password'];
        $routerUrl = "http://$ip" . ($port != 80 ? ":$port" : "");
        $cookieFile = tempnam(sys_get_temp_dir(), 'cookie');

        try {
            // 1️⃣ Login to router
            curl_post("$routerUrl/login/Auth", ["password" => base64_encode($password)], $cookieFile);

            // 2️⃣ Fetch all users for this router
            $stmtUsers = $db->prepare("SELECT * FROM users WHERE router_id = ?");
            $stmtUsers->execute([$routerId]);
            $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

            // 3️⃣ Build onlineList with 1KB limits for throttled users
            $onlineList = [];
            foreach ($users as $u) {
                $mac = strtoupper($u['mac']);
                $hostname = $u['hostname'] ?: 'unknown';
                // Throttle only the selected user
                $upLimit = ($u['mac'] === $user['mac']) ? 1 : 10240;
                $downLimit = $upLimit;
                $onlineList[] = "$hostname\t$hostname\t$mac\t$upLimit\t$downLimit\ttrue";
            }

            // 4️⃣ Apply QoS
            curl_post("$routerUrl/goform/setQos", [
                'module1' => 'onlineList',
                'onlineList' => implode("\n", $onlineList),
                'onlineListLen' => count($onlineList),
                'qosEn' => '1',
                'qosAccessEn' => '1'
            ], $cookieFile, "$routerUrl/index.html");

            // 5️⃣ Save config
            curl_post("$routerUrl/goform/save", ['random' => time()], $cookieFile, "$routerUrl/index.html");

        } catch (Exception $e) {
            // Optionally log error
        }
    }

    echo json_encode(['status' => 'success']);
    exit;
}

// ---------------------------
// Fetch routers and plans
// ---------------------------
$routers = $db->query("SELECT id, name FROM routers")->fetchAll(PDO::FETCH_ASSOC);
$plans = $db->query("SELECT * FROM plans")->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------
// Format remaining time
// ---------------------------
function formatRemainingTime($seconds) {
    if ($seconds <= 0) return "0d 0h 0m 0s";
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return "{$days}d {$hours}h {$minutes}m {$secs}s";
}

// ---------------------------
// Update old rows
// ---------------------------
$updateStmt = $db->prepare("UPDATE billing SET end_at = ?, remaining_time = ? WHERE id = ?");
$rows = $db->query("SELECT b.id, b.created_at, p.days, p.hours, p.minutes 
                    FROM billing b 
                    JOIN plans p ON b.plan_id = p.id 
                    WHERE b.plan_id IS NOT NULL AND (b.end_at IS NULL OR b.end_at = '')")
           ->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $created = strtotime($row['created_at']);
    $duration = ($row['days'] ?? 0) * 86400 + ($row['hours'] ?? 0) * 3600 + ($row['minutes'] ?? 0) * 60;
    $endAt = date('Y-m-d H:i:s', $created + $duration);
    $updateStmt->execute([$endAt, $duration, $row['id']]);
}

// ---------------------------
// Handle plan change / delete
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id'], $_POST['new_plan_id'])) {
        $userId = $_POST['user_id'];
        $newPlanId = $_POST['new_plan_id'];

        $newPlanStmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
        $newPlanStmt->execute([$newPlanId]);
        $newPlan = $newPlanStmt->fetch(PDO::FETCH_ASSOC);

        $durationInSeconds = ($newPlan['days'] ?? 0) * 86400
                           + ($newPlan['hours'] ?? 0) * 3600
                           + ($newPlan['minutes'] ?? 0) * 60;

        $userStmt = $db->prepare("SELECT created_at FROM billing WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $createdAt = strtotime($user['created_at']);

        $endAt = date('Y-m-d H:i:s', $createdAt + $durationInSeconds);

        $updateStmt = $db->prepare("UPDATE billing SET plan_id = ?, remaining_time = ?, end_at = ? WHERE id = ?");
        $updateStmt->execute([$newPlanId, $durationInSeconds, $endAt, $userId]);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['delete_user_id'])) {
        $deleteUserId = $_POST['delete_user_id'];
        $deleteStmt = $db->prepare("DELETE FROM billing WHERE id = ?");
        $deleteStmt->execute([$deleteUserId]);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid">
            <h1 class="mt-4 mb-4 text-center">Users by Router</h1>

            <?php foreach ($routers as $router): ?>
                <h2><?php echo htmlspecialchars($router['name']); ?></h2>

                <?php
                $stmt = $db->prepare("
                    SELECT b.*, p.name AS plan_name, p.days, p.hours, p.minutes
                    FROM billing b
                    JOIN plans p ON b.plan_id = p.id
                    WHERE b.router_id = ?
                    ORDER BY b.created_at DESC
                ");
                $stmt->execute([$router['id']]);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if ($users): ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone Number</th>
                                        <th>MAC Address</th>
                                        <th>Plan</th>
                                        <th>Plan Duration</th>
                                        <th>Remaining Time</th>
                                        <th>Created At</th>
                                        <th>Ends At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user):
                                        $planDuration = ($user['days'] ?? 0) . "d "
                                                      . ($user['hours'] ?? 0) . "h "
                                                      . ($user['minutes'] ?? 0) . "m";

                                        $endAt = $user['end_at'] ?? null;
                                        $remainingSeconds = $endAt ? max(strtotime($endAt) - time(), 0) : 0;
                                        $isExpired = $remainingSeconds <= 0;
                                    ?>
                                        <tr id="user-<?php echo $user['id']; ?>" style="background-color: <?php echo $isExpired ? '#f8d7da' : ''; ?>">
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                            <td><?php echo htmlspecialchars($user['mac']); ?></td>
                                            <td><?php echo htmlspecialchars($user['plan_name']); ?></td>
                                            <td><?php echo $planDuration; ?></td>
                                            <td><?php echo formatRemainingTime($remainingSeconds); ?></td>
                                            <td><?php echo $user['created_at']; ?></td>
                                            <td><?php echo $endAt ?? 'N/A'; ?></td>
                                            <td>
                                                <form method="POST" class="mb-1">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <select name="new_plan_id" class="form-control form-control-sm mb-1">
                                                        <?php foreach ($plans as $plan): ?>
                                                            <option value="<?php echo $plan['id']; ?>" <?php echo $plan['id'] == $user['plan_id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($plan['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-info btn-sm w-100">Change Plan</button>
                                                </form>
                                                <form method="POST">
                                                    <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm w-100">Delete</button>
                                                </form>
                                                <button class="btn btn-warning btn-sm w-100 mt-1 throttle-btn" data-user-id="<?php echo $user['id']; ?>">Throttle</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <p>No users found for this router.</p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script>
// Manual throttle button
document.querySelectorAll('.throttle-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const userId = btn.dataset.userId;
        const formData = new FormData();
        formData.append('ajax_throttle_user_id', userId);

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                console.log("User throttled", data);
                btn.textContent = "Throttled";
                btn.disabled = true;
                const row = document.getElementById('user-' + userId);
                row.style.backgroundColor = '#f8d7da';
            })
            .catch(err => console.error(err));
    });
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
<?php ob_end_flush(); ?>
