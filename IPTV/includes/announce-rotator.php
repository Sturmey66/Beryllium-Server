#!/usr/bin/env php
<?php
// ------------------- CLI ONLY -------------------
if (php_sapi_name() !== 'cli') {
    die("This script must be run from CLI.\n");
}

// ------------------- ERROR REPORTING -------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ------------------- PATHS -------------------
$BASE_DIR     = "/IPTV";
$serverPath   = "$BASE_DIR/includes/server.ini";
$announceFile = "$BASE_DIR/includes/announce.txt";
$tmpDir       = "/LIVE/announce/tmp";
$currentFile  = "$tmpDir/current.txt";

@mkdir($tmpDir, 0777, true);

// ------------------- ROTATOR LOOP -------------------
$lastRaw = '';
$pageIndex = 0;

while (true) {
    // Reload config each iteration in case pagespeed changes
    $ini = parse_ini_file($serverPath, true);
    $pagespeed = max(1, (int)($ini['announce']['pagespeed'] ?? 10));

    // Reload announce.txt only if changed
    $raw = is_file($announceFile) ? file_get_contents($announceFile) : ' ';
    if ($raw !== $lastRaw) {
        $pages = array_values(array_filter(array_map('trim', preg_split('/\[===\]/', $raw))));
        if (empty($pages)) $pages = [" "];
        $lastRaw = $raw;
        $pageIndex = 0;
    }

    // Update current.txt with the current page
    if (!empty($pages)) {
        file_put_contents($currentFile, $pages[$pageIndex]);
        $pageIndex = ($pageIndex + 1) % count($pages);
    }

    // Sleep for pagespeed seconds
    sleep($pagespeed);
}
