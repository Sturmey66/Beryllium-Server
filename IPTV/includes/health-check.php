<?php
// health-check.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base paths
$baseDir    = '/LIVE';
$healthFile = '/IPTV/includes/health.json';
$logFile    = '/var/log/health.log';

$health = [];
$now = time();

// Recursive function to find index.m3u8
function scanLive($dir) {
    $files = [];
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $files = array_merge($files, scanLive($path));
        } elseif (is_file($path) && basename($path) === 'index.m3u8') {
            $files[] = $path;
        }
    }
    return $files;
}

$m3uFiles = scanLive($baseDir);

foreach ($m3uFiles as $file) {
    // Folder name is the parent directory
    $name = basename(dirname($file));
    $lastMod = file_exists($file) ? filemtime($file) : null;
    $ageSecs = $lastMod ? $now - $lastMod : null;

    $health[$name] = [
        'status'    => ($ageSecs !== null && $ageSecs < 60) ? 'good' : 'bad',
        'last_mod'  => $lastMod ? date('Y-m-d H:i:s', $lastMod) : null,
        'age_secs'  => $ageSecs,
        'file'      => $file
    ];
}

// Save JSON
file_put_contents($healthFile, json_encode($health, JSON_PRETTY_PRINT));

// Append log entry
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Health check run\n", FILE_APPEND);

// Output summary for CLI
if (php_sapi_name() === 'cli') {
    echo "Health check complete. Results:\n";
    foreach ($health as $name => $info) {
        echo sprintf(
            "%s -> %s (last modified: %s, age: %s sec, file: %s)\n",
            $name,
            $info['status'],
            $info['last_mod'] ?? 'N/A',
            $info['age_secs'] ?? 'N/A',
            $info['file']
        );
    }
}
