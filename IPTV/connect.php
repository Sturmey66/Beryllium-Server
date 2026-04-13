<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Project base directory
$BASE_DIR = "/IPTV";

// Include core files
include $BASE_DIR . "/includes/menu.php";
require_once $BASE_DIR . "/includes/init.php";

$generated = false;

// Helper function (if not already defined)
if (!function_exists('sanitize')) {
    function sanitize($str) {
        return htmlspecialchars(trim($str), ENT_QUOTES);
    }
}

// Handle playlist generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lines = ["#EXTM3U"];

    // Add channels.xml
    if (file_exists($channelsFile)) {
        $xml = simplexml_load_file($channelsFile);
        foreach ($xml->channel as $channel) {
            $enabled = strtolower((string)$channel->enabled) === 'true';
            if (!$enabled) continue; // skip disabled channels

            $name = sanitize((string)$channel->name);
            $logo = sanitize((string)$channel->{'tv-logo'});
            $url  = "http://$serverIP:$serverPort/$name/index.m3u8";
            $lines[] = "#EXTINF:-1 tvg-logo=\"$logo\",$name";
            $lines[] = $url;
        }
    }

    // Add announce stream if index.m3u8 exists
    $announceM3U8 = "/LIVE/announce/index.m3u8";
    if (file_exists($announceM3U8)) {
        $lines[] = "#EXTINF:-1 tvg-logo=\"\" group-title=\"announce\",Announcements";
        $lines[] = "http://$serverIP:$serverPort/announce/index.m3u8";
    }

    // Add wfeeds.xml
    if (file_exists($wfeedsPath)) {
        $xml = simplexml_load_file($wfeedsPath);
        foreach ($xml->feed as $feed) {
            $logo  = sanitize((string)$feed->{'tv-logo'});
            $group = sanitize((string)$feed->{'group-title'});
            $name  = sanitize((string)$feed->name);
            $url   = sanitize((string)$feed->url);
            $lines[] = "#EXTINF:-1 tvg-logo=\"$logo\" group-title=\"$group\",$name";
            $lines[] = $url;
        }
    }

    // Add .mp4 files from $vodDir
    if (is_dir($vodDir)) {
        foreach (glob("$vodDir/*.mp4") as $file) {
            $filename = basename($file);
            $name     = pathinfo($filename, PATHINFO_FILENAME);
            $url      = "http://$serverIP:$serverPort/VOD/$filename";
            $lines[]  = "#EXTINF:-1,$name";
            $lines[]  = $url;
        }
    }

    // Ensure directory exists for playlist
    @mkdir(dirname($playlistFile), 0777, true);

    // Write playlist
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
<h2>Generate Playlist</h2>
<p>Download or access it here: 
    <a href="http://<?= $serverIP ?>:<?= $serverPort ?>/service.m3u" target="_blank">
        http://<?= $serverIP ?>:<?= $serverPort ?>/service.m3u
    </a>
</p>

<form method="post">
    <button type="submit">Generate updated service.m3u</button>
</form>

<?php if ($generated): ?>
<p><strong>Playlist generated successfully.</strong></p>
<?php endif; ?>
</body>
</html>
