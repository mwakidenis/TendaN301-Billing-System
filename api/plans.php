<?php
// /api/plans.php
header('Content-Type: application/json');
$dbPath = __DIR__ . '/../db/routers.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $method = $_SERVER['REQUEST_METHOD'];

    // -----------------------------
    // GET: fetch all plans
    // -----------------------------
    if ($method === 'GET') {
        $stmt = $db->query("SELECT id, name, days, hours, minutes, created_at FROM plans ORDER BY created_at DESC");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'plans' => $plans]);
        exit;
    }

    // -----------------------------
    // POST: create a new plan
    // -----------------------------
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $name = trim($input['name'] ?? '');
        $days = intval($input['days'] ?? 0);
        $hours = intval($input['hours'] ?? 0);
        $minutes = intval($input['minutes'] ?? 0);

        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Plan name is required']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO plans (name, days, hours, minutes) VALUES (:name, :days, :hours, :minutes)");
        $stmt->execute([
            ':name' => $name,
            ':days' => $days,
            ':hours' => $hours,
            ':minutes' => $minutes
        ]);

        echo json_encode(['success' => true, 'message' => 'Plan created successfully', 'plan_id' => $db->lastInsertId()]);
        exit;
    }

    // -----------------------------
    // PUT: update an existing plan
    // -----------------------------
    elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $days = intval($input['days'] ?? 0);
        $hours = intval($input['hours'] ?? 0);
        $minutes = intval($input['minutes'] ?? 0);

        if (!$id || !$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Plan ID and name are required']);
            exit;
        }

        $stmt = $db->prepare("UPDATE plans SET name = :name, days = :days, hours = :hours, minutes = :minutes WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':days' => $days,
            ':hours' => $hours,
            ':minutes' => $minutes,
            ':id' => $id
        ]);

        echo json_encode(['success' => true, 'message' => 'Plan updated successfully']);
        exit;
    }

    // -----------------------------
    // DELETE: remove a plan
    // -----------------------------
    elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Plan ID is required']);
            exit;
        }

        $stmt = $db->prepare("DELETE FROM plans WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true, 'message' => 'Plan deleted successfully']);
        exit;
    }

    else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
