<?php
// /auth/config.php

// Path to the SQLite database
define('DB_PATH', __DIR__ . '/../db/routers.db');

/**
 * Fetch router config by ID using PDO
 * @param int $id Router ID
 * @return array|null Returns ['id'=>..., 'name'=>..., 'ip'=>..., 'port'=>..., 'password'=>...] or null
 * @throws Exception If database is missing or query fails
 */
function getRouterConfig($id) {
    if (!file_exists(DB_PATH)) {
        throw new Exception("Database file not found: " . DB_PATH);
    }

    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare("SELECT * FROM routers WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $router = $stmt->fetch(PDO::FETCH_ASSOC);
        return $router ?: null;
    } catch (Exception $e) {
        throw new Exception("Failed to fetch router config: " . $e->getMessage());
    }
}

/**
 * Create a temporary cookie file for router sessions
 * @param string $prefix Optional prefix
 * @return string Path to the cookie file
 */
function createCookieFile($prefix = 'tenda_') {
    return tempnam(sys_get_temp_dir(), $prefix);
}
