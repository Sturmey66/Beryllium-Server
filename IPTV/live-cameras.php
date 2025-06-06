<?php
include "./includes/auth.php"; // Session and login check
require_once __DIR__ . "/includes/functions.php";

$channelsFile = __DIR__ . "/channels.xml";
$serverIni = parse_ini_file(__DIR__ . "/includes/server.ini");
$serverIP = $serverIni['serverIP'] ?? '127.0.0.1';
$xml = simplexml_load_file($channelsFile);
$feedback = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restart-time'])) {
    $_SESSION['restart_time'] = $_POST['restart-time'];
}
// Paths
$supervisorConfDir = "/IPTV/supervisor";

// Functions
function createSupervisorService($name, $url, $serverIP, $supervisorConfDir) {
    $targetDir = "/IPTV/live/$name";
    @mkdir($targetDir, 0777, true);

    $streamURL = "http://$serverIP:8080/live/$name/";

// command to make Roku compatible live stream.
$cmd = "/usr/bin/ffmpeg -hide_banner -rtbufsize 1G -i '$url' -fps_mode cfr -c:v libx264 -profile:v baseline -level 3.0 -b:v 5000k -maxrate 5000k -bufsize 600k -r 30 -preset ultrafast -c:a aac -ac 2 -ar 44100 -b:a 96k -strict -2 -f hls -hls_time 2 -hls_list_size 3 -hls_flags independent_segments+delete_segments+split_by_time -hls_segment_type mpegts -hls_base_url '$streamURL' -hls_segment_filename '$targetDir/fileSequence%%05d.ts' '$targetDir/index.m3u8'";




    $conf = "[program:iptv-$name]
command=$cmd
autostart=true
autorestart=true
stderr_logfile=/var/log/iptv-$name.err.log
stdout_logfile=/var/log/iptv-$name.out.log
";

    file_put_contents("$supervisorConfDir/iptv-$name.conf", $conf);
    shell_exec("supervisorctl reread && supervisorctl update");
}

function addCamera($name, $url, $logo) {
    global $channelsFile;
    $xml = simplexml_load_file($channelsFile);
    $newChannel = $xml->addChild('channel');
    $newChannel->addChild('name', $name);
    $newChannel->addChild('url', $url);
    $newChannel->addChild('tv-logo', $logo);
    $xml->asXML($channelsFile);
}

function deleteCamera($name) {
    global $channelsFile;
    
    $xml = simplexml_load_file($channelsFile);
    $indexToDelete = -1;

    foreach ($xml->channel as $index => $channel) {
        if (strcasecmp((string)$channel->name, $name) === 0) {
            $indexToDelete = $index;
            break;
        }
    }

    if ($indexToDelete !== -1) {
        unset($xml->channel[$indexToDelete]);
        $xml->asXML($channelsFile);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['startService'])) {
        $name = basename($_POST['name']);
        $url = $_POST['url'];
        createSupervisorService($name, $url, $serverIP, $supervisorConfDir);
        shell_exec("supervisorctl start iptv-$name");
        $feedback = "Started service iptv-$name.";
    }

    if (isset($_POST['stopService']) && !empty($_POST['name'])) {
        $name = basename($_POST['name']);
        shell_exec("supervisorctl stop iptv-$name");

        // Remove supervisor config
        $confFile = "$supervisorConfDir/iptv-$name.conf";
        if (file_exists($confFile)) {
            unlink($confFile);
        }

        // Reload Supervisor config
        shell_exec("supervisorctl reread && supervisorctl update");

        // Clean up stream folder
        $streamDir = "/IPTV/live/$name";
        if (is_dir($streamDir)) {
            array_map('unlink', glob("$streamDir/*"));
            rmdir($streamDir);
        }

        $feedback = "Stopped and removed service iptv-$name.";
    }



    if (isset($_POST['deleteCamera']) && !empty($_POST['name'])) {
        $name = $_POST['name'];
        deleteCamera($name);

        // Clean up live stream folder
        $streamDir = "/IPTV/live/$name";
        if (is_dir($streamDir)) {
            array_map('unlink', glob("$streamDir/*"));
            rmdir($streamDir);
        }

        $feedback = "Deleted camera $name.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['addCamera'])) {
        $name = $_POST['name'];
        $url = $_POST['url'];
        $logo = $_POST['logo'];
        addCamera($name, $url, $logo);
        $feedback = "Added camera $name.";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Camera Services</title>

</head>
<script>
function updateStatuses() {
    fetch('/includes/get-status.php')
        .then(response => response.json())
        .then(data => {
            data.forEach(item => {
                const statusSpan = document.querySelector(`#status-${item.name}`);
                if (statusSpan) {
                    statusSpan.className = "status-dot " + (item.status === "running" ? "green" : "red");
                    statusSpan.nextSibling.textContent = ` (${item.status})`;
                }
            });
        });
}

setInterval(updateStatuses, 15000); // every 15 seconds
updateStatuses(); // also run on load
</script>

<body>
<?php include "./includes/menu.php"; ?>
<!-- In your HTML head or body -->


<h2>Live Camera Services</h2>

<?php if (!empty($feedback)): ?>
    <p><strong><?= htmlspecialchars($feedback) ?></strong></p>
<?php endif; ?>

<!-- Add Camera Form -->
<h3>Add New Camera</h3>
<form method="post">
    <label for="name">Camera Name:</label>
    <input type="text" name="name" required><br>
    
    <label for="url">RTSP URL:</label>
    <input type="text" name="url" required><br>
    
    <label for="logo">TV Logo URL:</label>
    <input type="text" name="logo" required><br>
    
    <input type="submit" name="addCamera" value="Add Camera">
</form>

<h3>Existing Cameras</h3>
<?php foreach ($xml->channel as $channel):
    $name = (string) $channel->name;
    $url = (string) $channel->url;
    $logo = (string) $channel->{"tv-logo"};
    $safeName = basename($name);
    $status = getSupervisorStatus($safeName);
    $dotClass = ($status === "running") ? "green" : "red";
?>
    <div>
        <strong><?= htmlspecialchars($name) ?></strong> (<?= htmlspecialchars($url) ?>)
        <span id="status-<?= htmlspecialchars($name) ?>" class="status-dot <?= $dotClass ?>"></span>
        (<?= htmlspecialchars($status) ?>)
        <form method="post" style="display:inline;">
            <input type="hidden" name="name" value="<?= htmlspecialchars($name) ?>">
            <input type="hidden" name="url" value="<?= htmlspecialchars($url) ?>">
            <input type="submit" name="startService" value="Start">
            <input type="submit" name="stopService" value="Stop">
            <input type="submit" name="deleteCamera" value="Delete">
        </form>
    </div>
<?php endforeach; ?>
</body>
</html>
