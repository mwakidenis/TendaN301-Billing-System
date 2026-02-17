<?php
/**
 * Check if a router (or any device) is online
 * @param string $ip IP or host
 * @param int $port Port number
 * @param int $timeout Seconds to wait
 * @return bool true = online, false = offline
 */
function isRouterOnline($ip, $port = 80, $timeout = 2) {
    // Suppress warnings with @
    $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

// Example usage:
$routerIp = "192.168.100.104";
$routerPort = 8080;

if (isRouterOnline($routerIp, $routerPort)) {
    echo "$routerIp:$routerPort is ONLINE\n";
} else {
    echo "$routerIp:$routerPort is OFFLINE\n";
}
