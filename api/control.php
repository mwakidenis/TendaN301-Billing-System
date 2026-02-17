<?php
header('Content-Type: application/json');

// Path to the SQLite database
$dbPath = __DIR__ . '/../db/routers.db';

// Function to check if the router is online via TCP connection
function isRouterOnline($ip, $port = 80, $timeout = 2) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if ($fp) {
        fclose($fp);
        return true; // Router is online
    }
    return false; // Router is offline
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // -----------------------------
        // DELETE ROUTER
        // -----------------------------
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        if (!empty($input['delete']) && !empty($input['id'])) {
            // Delete router from database
            $stmt = $db->prepare("DELETE FROM routers WHERE id = :id");
            $stmt->execute([':id' => $input['id']]);

            echo json_encode(['success' => true, 'message' => 'Router deleted successfully.']);
            exit;
        }

        // -----------------------------
        // ADD / UPDATE ROUTER
        // -----------------------------
        $required = ['name', 'ip', 'port', 'password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing field: $field"]);
                exit;
            }
        }

        $name = trim($input['name']);
        $ip   = trim($input['ip']);
        $port = intval($input['port']);
        $pass = trim($input['password']);

        // Check if router exists
        $stmt = $db->prepare("SELECT id FROM routers WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update existing router
            $stmt = $db->prepare("
                UPDATE routers 
                SET ip = :ip, port = :port, password = :password
                WHERE id = :id
            ");
            $stmt->execute([
                ':ip'       => $ip,
                ':port'     => $port,
                ':password' => $pass,
                ':id'       => $existing['id']
            ]);
            $message = "Router '$name' updated successfully.";
        } else {
            // Insert new router
            $stmt = $db->prepare("
                INSERT INTO routers (name, ip, port, password)
                VALUES (:name, :ip, :port, :password)
            ");
            $stmt->execute([
                ':name'     => $name,
                ':ip'       => $ip,
                ':port'     => $port,
                ':password' => $pass
            ]);
            $message = "Router '$name' added successfully.";
        }

        echo json_encode(['success' => true, 'message' => $message]);
        exit;
    }

    elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // -----------------------------
        // FETCH ALL ROUTERS
        // -----------------------------
        $stmt = $db->query("SELECT id, name, ip, port FROM routers ORDER BY name ASC");
        $routers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check online status for each router
        foreach ($routers as &$router) {
            $router['online'] = isRouterOnline($router['ip'], $router['port']);
        }

        echo json_encode(['success' => true, 'routers' => $routers]);
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
