<?php include "./includes/menu.php"; ?>
<?php
$channelsFile = __DIR__ . "/channels.xml";
$serverIni = parse_ini_file(__DIR__ . "/includes/server.ini");
$serverIP = $serverIni['serverIP'] ?? '127.0.0.1';
$xml = simplexml_load_file($channelsFile);
$feedback = "";

// Paths
$supervisorConfDir = "/IPTV/supervisor";

function getSupervisorStatus($name) {
    $output = shell_exec("supervisorctl status iptv-$name 2>/dev/null");
    if (!$output) return "unknown";
    return (strpos($output, 'RUNNING') !== false) ? 'running' : 'stopped';
}

function createSupervisorService($name, $url, $serverIP, $supervisorConfDir) {
    $targetDir = "/IPTV/live/$name";
    @mkdir($targetDir, 0777, true);

    $streamURL = "http://$serverIP:8080/live/$name/";

    $cmd = "/usr/bin/ffmpeg -hide_banner -rtbufsize 1G -i \"$url\" " .
           "-vsync cfr -c:v libx264 -b:v 1000k -minrate:v 1000k -maxrate:v 1000k " .
           "-c:a copy -f hls -r 30 -vlevel 3.0 -x264-params keyint=15:min-keyint=15 " .
           "-hls_time 2 -hls_list_size 20 -http_persistent 0 -hls_flags delete_segments " .
           "-hls_start_number_source datetime -preset ultrafast -hls_base_url \"$streamURL\" " .
           "-hls_segment_type mpegts -hls_segment_filename $targetDir/fileSequence%%d.ts " .
           "$targetDir/index.m3u8";

    $conf = "[program:iptv-$name]
command=$cmd
autostart=true
autorestart=true
stderr_logfile=/var/log/iptv-$name.err.log
stdout_logfile=/var/log/iptv-$name.out.log
";

    file_put_contents("$supervisorConfDir/iptv-$name.conf", $conf);
    shell_exec("supervisorctl reread");
    shell_exec("supervisorctl update");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = basename($_POST['name']);
    $url = $_POST['url'];

    if (isset($_POST['startService'])) {
        createSupervisorService($name, $url, $serverIP, $supervisorConfDir);
        shell_exec("supervisorctl start iptv-$name");
        $feedback = "Started service iptv-$name.";
    }

    if (isset($_POST['stopService'])) {
    shell_exec("supervisorctl stop iptv-$name");
    $confFile = "$supervisorConfDir/iptv-$name.conf";
    if (file_exists($confFile)) {
        unlink($confFile); // Remove the config file
    }
    shell_exec("supervisorctl reread && supervisorctl update");
    $feedback = "Stopped and removed service iptv-$name.";
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5">
    <title>Service Manager</title>
    <style>
        .status-dot {
            height: 10px;
            width: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .green { background-color: green; }
        .red { background-color: red; }
    </style>
</head>
<body>
<h2>Channel Services</h2>

<?php if (!empty($feedback)): ?>
    <p><strong><?= htmlspecialchars($feedback) ?></strong></p>
<?php endif; ?>

<?php foreach ($xml->channel as $channel):
    $name = (string) $channel->name;
    $url = (string) $channel->url;
    $safeName = basename($name);
    $status = getSupervisorStatus($safeName);
    $dotClass = ($status === "running") ? "green" : "red";
$output = shell_exec("supervisorctl reread 2>&1");
error_log("REREAD OUTPUT: " . $output);
$output = shell_exec("supervisorctl update 2>&1");
error_log("UPDATE OUTPUT: " . $output);
$output = shell_exec("supervisorctl start iptv-$name 2>&1");
error_log("START OUTPUT: " . $output);
$feedback .= "<pre>" . htmlspecialchars(shell_exec("supervisorctl reread 2>&1")) . "</pre>";
$feedback .= "<pre>" . htmlspecialchars(shell_exec("supervisorctl start iptv-$name 2>&1")) . "</pre>";
?>
    <div>
        <strong><?= htmlspecialchars($name) ?></strong>
        <span class="status-dot <?= $dotClass ?>"></span>
        (<?= htmlspecialchars($status) ?>)
        <form method="post" style="display:inline;">
            <input type="hidden" name="name" value="<?= htmlspecialchars($name) ?>">
            <input type="hidden" name="url" value="<?= htmlspecialchars($url) ?>">
            <input type="submit" name="startService" value="Start">
            <input type="submit" name="stopService" value="Stop">
        </form>
        <br><br>
    </div>
<?php endforeach; ?>
</body>
</html>
