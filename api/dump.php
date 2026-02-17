<?php
// -----------------------
// CONFIG
// -----------------------
$routerIP = "192.168.0.1"; // router IP
$files = [
    "userManage.js"    => "/js/userManage.js",
    "advanced.js"      => "/js/advanced.js",
    "net-control.html" => "/net-control.html",
    "login.html"       => "/login.html",
    "wireless.js"      => "/js/wireless.js"
];
$saveDir = __DIR__ . "/router_files";

// Make sure the directory exists
if (!is_dir($saveDir)) {
    mkdir($saveDir, 0755, true);
}

// -----------------------
// DOWNLOAD FUNCTION
// -----------------------
function downloadFile($routerIP, $remotePath, $localPath) {
    $url = "http://$routerIP$remotePath";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: text/javascript, application/javascript, text/html, */*; q=0.01",
        "Accept-Language: en-US,en;q=0.9",
        "Connection: keep-alive"
    ]);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
    curl_setopt($ch, CURLOPT_REFERER, "http://$routerIP/");

    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        die("cURL error for $remotePath: " . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        die("Failed to download $remotePath, HTTP status: $httpCode\n");
    }

    file_put_contents($localPath, $data);
    echo "Downloaded $remotePath to $localPath\n";

    // Optional: Prettier formatting
    $cmd = "npx prettier --write " . escapeshellarg($localPath);
    exec($cmd, $output, $return_var);
    if ($return_var === 0) {
        echo "Formatted $localPath with Prettier\n";
    } else {
        echo "Prettier failed for $localPath\n";
    }
}

// -----------------------
// DOWNLOAD ALL FILES
// -----------------------
foreach ($files as $name => $path) {
    $localPath = "$saveDir/$name";
    downloadFile($routerIP, $path, $localPath);
}

echo "All files downloaded and formatted.\n";
