<?php
// logout

if (session_status() === PHP_SESSION_NONE) {
    // session not started, start it to destroy it
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy session
session_destroy();

// Delete session cookie if exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header("Location: login");
exit;
?>
