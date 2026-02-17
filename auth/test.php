<?php
header('Content-Type: application/json');

// -----------------------
// DATABASE HELPER
// -----------------------
function getDb(): PDO {
    $dbPath = __DIR__ . '/../db/routers.db';
    if (!file_exists($dbPath)) {
        die(json_encode(['error' => 'Database file not found']));
    }
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

// -----------------------
// TEST CONNECTION
// -----------------------
try {
    $db = getDb();
    echo json_encode(['status' => 'Database connected successfully']);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
