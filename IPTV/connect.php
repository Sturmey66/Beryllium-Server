<?php 
include "./includes/auth.php"; // Session and login check
require_once __DIR__ . "/includes/functions.php";
?>
<?php
$channelsFile = __DIR__ . "/channels.xml";
$feedsFile = __DIR__ . "/wfeeds.xml";
$liveDir = "/LIVE/VOD";
$announceFile = "/LIVE/announce/index.m3u8"; // announce stream
$playlistFile = "/LIVE/service.m3u";
$serverIni = parse_ini_file(__DIR__ . "/includes/server.ini");
$serverIP = $serverIni['serverIP'] ?? '127.0.0.1';


function sanitize($text) {
    return htmlspecialchars(trim($text), ENT_QUOTES);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lines = ["#EXTM3U"];

    // Add channels.xml
    if (file_exists($channelsFile)) {
        $xml = simplexml_load_file($channelsFile);
        foreach ($xml->channel as $channel) {
            $name = sanitize((string)$channel->name);
            // $url = sanitize((string)$channel->url);
            $url = "http://$serverIP:8080/$name/index.m3u8";
            $lines[] = "#EXTINF:-1,$name";
            $lines[] = $url;
        }
    }

    // Insert announce stream if it exists
    if (file_exists($announceFile)) {
        $lines[] = "#EXTINF:-1 tvg-logo=\"\" group-title=\"announce\",Announcements";
        $lines[] = "http://$serverIP:8080/announce/index.m3u8";
    }
    
    // Add wfeeds.xml
    if (file_exists($feedsFile)) {
        $xml = simplexml_load_file($feedsFile);
        foreach ($xml->feed as $feed) {
            $logo = sanitize((string)$feed->{'tv-logo'});
            $group = sanitize((string)$feed->{'group-title'});
            $name = sanitize((string)$feed->name);
            $url = sanitize((string)$feed->url);
            $lines[] = "#EXTINF:-1 tvg-logo=\"$logo\" group-title=\"$group\",$name";
            $lines[] = $url;
        }
    }

    // Add .mp4 files from live directory
    if (is_dir($liveDir)) {
        foreach (glob("$liveDir/*.mp4") as $file) {
            $filename = basename($file);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $url = "http://$serverIP:8080/VOD/$filename";
            $lines[] = "#EXTINF:-1,$name";
            $lines[] = $url;
        }
    }

    // Write to file
    file_put_contents($playlistFile, implode("\n", $lines));
    $generated = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Generate service.m3u</title>
</head>
<body>
<?php include "./includes/menu.php"; ?>
<h2>Generate Playlist</h2>
  <p>Download or access it here: <a href="http://<?= $serverIP ?>:8080/service.m3u" target="_blank">http://<?= $serverIP ?>:8080/service.m3u</a></p>

<form method="post">
    <button type="submit">Generate updated service.m3u</button>
</form>

<?php if (!empty($generated)): ?>
    <p><strong>Playlist generated successfully.</strong></p>
<?php endif; ?>
</body>
</html>
