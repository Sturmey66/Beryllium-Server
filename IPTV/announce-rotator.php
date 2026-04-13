<?php
// Announce rotator: updates current.txt for FFmpeg drawtext
$baseDir = '/IPTV';
$announceFile = "$baseDir/includes/announce.txt";
$tmpDir = "/LIVE/announce/tmp";
@mkdir($tmpDir, 0777, true);

// ---------------- LOAD CONFIG ----------------
$serverIni = "$baseDir/includes/server.ini";
$pagespeed = 10; // default
if (is_file($serverIni)) {
    $ini = parse_ini_file($serverIni, true);
    if (!empty($ini['announce']['pagespeed'])) {
        $pagespeed = max(1, (int)$ini['announce']['pagespeed']);
    }
}

// ---------------- LOAD PAGES ----------------
// Read announce.txt and split into pages using [===] delimiter
$pages = [];
if (is_file($announceFile)) {
    $text = file_get_contents($announceFile);
    // Normalize line endings to LF
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $pages = explode('[===]', $text);
}
if (empty($pages)) $pages = [' ']; // fallback page

// ---------------- ROTATE ----------------
$idx = 0;
while (true) {
    // Remove any trailing control characters (including CR) from the page
    $currentPage = preg_replace('/[\x00-\x1F\x7F]+$/u', '', $pages[$idx % count($pages)]);

    // Save current page for FFmpeg drawtext
    file_put_contents("$tmpDir/current.txt", $currentPage);

    $idx++;
    sleep($pagespeed);
}
