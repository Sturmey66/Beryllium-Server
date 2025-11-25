<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Project root
$BASE_DIR = "/IPTV";
$OUTPUT_DIR = "/LIVE";

// Ensure session and authentication
include $BASE_DIR . "/includes/auth.php";   // session + login check

// Core includes (functions, etc.)
require_once $BASE_DIR . "/includes/functions.php";

// File paths
$vodDir = "/LIVE/VOD";
$channelsFile       = $BASE_DIR . "/channels.xml";
$serverPath         = $BASE_DIR . "/includes/server.ini";
$wfeedsPath         = $BASE_DIR . "/wfeeds.xml";
$announceFile       = $BASE_DIR . "/includes/announce.txt";
$fontFile           = $BASE_DIR . "/includes/font/DejaVuSansMono.ttf";
$supervisorConfDir  = $BASE_DIR . "/supervisor";
$supervisorConfFile = $supervisorConfDir . "/iptv-announce.conf";
$settingsFile = $BASE_DIR . "/includes/server.ini";
$playlistFile = "/LIVE/service.m3u";


// Load configuration
$serverIni  = parse_ini_file($serverPath);
$serverIP   = $serverIni['serverIP'] ?? '127.0.0.1';
$serverPort = $serverIni['port'] ?? '8080';

// Load XML safely
if (!file_exists($channelsFile)) {
    die("Error: channels.xml not found at $channelsFile");
}
$xml = simplexml_load_file($channelsFile);
if ($xml === false) {
    die("Error: Failed to load channels.xml");
}

// Other variables
$feedback   = "";
$streamDir  = $OUTPUT_DIR . "/announce";
$tmpDir     = "/tmp/announce";
$currentTxt = $tmpDir . "/current.txt";
