<?php
// Set up PDO connection
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../db/routers.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Function to apply a plan to a device
function applyPlan($routerId, $mac, $planId) {
    global $db;

    try {
        // Check if the device already exists in the 'devices' table
        $stmt = $db->prepare("SELECT * FROM devices WHERE router_id = :router_id AND mac = :mac");
        $stmt->bindParam(':router_id', $routerId, PDO::PARAM_INT);
        $stmt->bindParam(':mac', $mac, PDO::PARAM_STR);
        $stmt->execute();
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        // If device not found, insert a new device
        if (!$device) {
            // Check if a valid plan exists
            $stmt = $db->prepare("SELECT * FROM plans WHERE id = :plan_id");
            $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
            $stmt->execute();
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plan) {
                return ['status' => 'error', 'error' => 'Plan not found'];
            }

            // Insert new device into the devices table
            $stmt = $db->prepare("
                INSERT INTO devices (router_id, mac, plan_id, internet_access)
                VALUES (:router_id, :mac, :plan_id, 1)  -- Default to internet_access = 1 (enabled)
            ");
            $stmt->bindParam(':router_id', $routerId, PDO::PARAM_INT);
            $stmt->bindParam(':mac', $mac, PDO::PARAM_STR);
            $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
            $stmt->execute();

            return ['status' => 'success', 'message' => 'New device added and plan applied successfully'];
        }

        // Update the user's plan and mark as having access
        $stmt = $db->prepare("
            UPDATE devices 
            SET plan_id = :plan_id, internet_access = 1
            WHERE router_id = :router_id AND mac = :mac
        ");
        $stmt->bindParam(':plan_id', $planId, PDO::PARAM_INT);
        $stmt->bindParam(':router_id', $routerId, PDO::PARAM_INT);
        $stmt->bindParam(':mac', $mac, PDO::PARAM_STR);
        $stmt->execute();

        return ['status' => 'success', 'message' => 'Billing updated successfully'];

    } catch (Exception $e) {
        // Log error message and return a detailed error response
        return ['status' => 'error', 'error' => 'An error occurred: ' . $e->getMessage()];
    }
}

// Handle the GET request with query parameters
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id']) && isset($_GET['paid_mac']) && isset($_GET['plan_id'])) {
        $routerId = (int)$_GET['id'];
        $mac = $_GET['paid_mac'];
        $planId = (int)$_GET['plan_id'];

        $result = applyPlan($routerId, $mac, $planId);

        echo json_encode($result);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'error' => 'Missing parameters']);
        exit;
    }
} else {
    echo json_encode(['status' => 'error', 'error' => 'Invalid request method']);
    exit;
}
