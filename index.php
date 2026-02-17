<?php
session_start();

// -----------------------------
// Suppress deprecation notices (PHP 8.1+)
// -----------------------------
error_reporting(E_ALL & ~E_DEPRECATED);

// Optional: Display all errors except deprecations
ini_set('display_errors', '1');

// -----------------------------
// Simple dynamic router
// -----------------------------
$request = $_SERVER['REQUEST_URI'];

// Remove query string
$request = strtok($request, '?');

// Trim leading/trailing slashes
$page = trim($request, '/');

// Default page
if ($page === '') {
    $page = 'login'; // Default page is login
}

// Sanitize page name to prevent directory traversal
$page = basename($page);

// Check if the page is allowed for public access (e.g. login page)
$publicPages = ['login'];

if (!isset($_SESSION['logged_in']) && !in_array($page, $publicPages)) {
    header('Location: /login');
    exit;
}

// Full path to page
$pageFile = __DIR__ . "/pages/$page.php";

// Check if file exists
if (file_exists($pageFile)) {
    require $pageFile;
} else {
    http_response_code(404);
    echo "<h1>404 - Page Not Found</h1>";
}