<?php
/************************************************************
 * Announce Channel - single file (Web UI + CLI rotator)
 * Requires php-cli for the rotator process.
 ************************************************************/

// --- Paths ---
$BASE_DIR           = __DIR__;
$settingsFile       = $BASE_DIR . "/includes/server.ini";
$announceFile       = $BASE_DIR . "/includes/announce.txt";
$fontFile           = $BASE_DIR . "/includes/font/DejaVuSansMono.ttf";
$supervisorConfDir  = "/IPTV/supervisor";
$supervisorConfFile = $supervisorConfDir . "/iptv-announce.conf";
$streamDir          = "/LIVE/announce";
$tmpDir             = "/tmp/announce";
$currentTxt         = $tmpDir . "/current.txt";

// Include helpers / session if not CLI
if (php_sapi_name() !== 'cli') {
    @include_once $BASE_DIR . "/includes/session.php";
    @include_once $BASE_DIR . "/includes/config.php"; // saveConfigSection()
}

// ---------------- CLI ROTATOR ----------------
if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === '--rotate') {
    @mkdir($tmpDir, 0777, true);
    $log = function ($msg) { fwrite(STDOUT, "[announce-rotator] " . date('c') . " - $msg\n"); };

    while (true) {
        $ini = parse_ini_file($settingsFile, true, INI_SCANNER_TYPED) ?: [];
        $pagespeed = max(1, (int)($ini['announce']['pagespeed'] ?? 5));
        $raw = is_file($announceFile) ? file_get_contents($announceFile) : '';
        $parts = array_values(array_filter(array_map('trim', preg_split('/\[===\]/', $raw))));
        if (empty($parts)) { $parts = [" "]; }

        foreach ($parts as $idx => $page) {
            @file_put_contents($currentTxt, $page);
            $log("Wrote page " . ($idx + 1) . "/" . count($parts) . " (sleep {$pagespeed}s)");
            sleep($pagespeed);
        }
    }
    exit(0);
}

// ---------------- WEB MODE ----------------
function ini_read_sections($file) {
    return parse_ini_file($file, true, INI_SCANNER_TYPED) ?: [];
}

function ini_write_section($file, $section, array $data) {
    if (function_exists('saveConfigSection')) {
        return saveConfigSection($file, $section, $data);
    }
    $ini = ini_read_sections($file);
    $ini[$section] = $data;
    $out = '';
    foreach ($ini as $sec => $vals) {
        $out .= "[$sec]\n";
        foreach ($vals as $k => $v) {
            if (is_bool($v)) { $v = $v ? 1 : 0; }
            $out .= "{$k} = {$v}\n";
        }
        $out .= "\n";
    }
    return file_put_contents($file, $out) !== false;
}

function write_supervisor_conf(array $cfg, string $serverIP, string $fontFile, string $confFile, string $streamDir, string $tmpDir) {
    // Ensure directories exist
    @mkdir(dirname($confFile), 0777, true);
    @mkdir($streamDir, 0777, true);
    @mkdir($tmpDir, 0777, true);

    $bg = $cfg['background'] ?? '#000000';
    $fg = $cfg['foreground'] ?? '#ffffff';

    // --- FFmpeg Command ---
    // Plain string for drawtext (do NOT use escapeshellarg)
    $vfFilter = "drawtext=fontfile={$fontFile}:textfile={$tmpDir}/current.txt:reload=1:fontcolor={$fg}:fontsize=84:x=40:y=40:line_spacing=-46";

    $ffmpegCmd = "/usr/bin/ffmpeg -hide_banner " .
                 "-f lavfi -i color=c={$bg}:s=1920x1080:r=30 " .
                 "-vf {$vfFilter} " .
                 "-c:v libx264 -profile:v baseline -level 3.0 " .
                 "-b:v 5000k -maxrate 5000k -bufsize 600k -r 30 " .
                 "-g 60 -sc_threshold 0 -force_key_frames \"expr:gte(t,n_forced*2)\" -preset ultrafast " .
                 "-c:a aac -ac 2 -ar 44100 -b:a 96k -strict -2 " .
                 "-f hls -hls_time 2 -hls_list_size 20 " .
                 "-hls_flags independent_segments+delete_segments+split_by_time " .
                 "-hls_segment_type mpegts " .
                 "-hls_base_url http://{$serverIP}:8080/announce/ " .
                 "-hls_segment_filename {$streamDir}/fileSequence%%05d.ts {$streamDir}/index.m3u8";

    // Rotator command
    $rotatorCmd = "/usr/bin/php " . __FILE__ . " --rotate";

    // --- Supervisor Config ---
    $conf = <<<CONF
[program:iptv-announce]
command=$ffmpegCmd
autostart=true
autorestart=true
stderr_logfile=/var/log/announce.err.log
stdout_logfile=/var/log/announce.out.log
startsecs=0
startretries=999

[program:iptv-announce-rotator]
command=$rotatorCmd
autostart=true
autorestart=true
stderr_logfile=/var/log/announce-rotator.err.log
stdout_logfile=/var/log/announce-rotator.out.log
startsecs=0
startretries=999

CONF;

    // Write Supervisor file
    if (file_put_contents($confFile, $conf) === false) {
        return false;
    }

    // Reload Supervisor
    shell_exec("supervisorctl reread 2>&1");
    shell_exec("supervisorctl update 2>&1");

    return true;
}


// Load config
$ini         = ini_read_sections($settingsFile);
$announceCfg = $ini['announce'] ?? [];
$theme       = $ini['theme'] ?? 'default';
$serverIni   = parse_ini_file($settingsFile);
$serverIP    = $serverIni['serverIP'] ?? '127.0.0.1';

// Defaults
$enabled   = isset($announceCfg['enabled']) ? (int)$announceCfg['enabled'] : 0;
$bg        = $announceCfg['background'] ?? '#000000';
$fg        = $announceCfg['foreground'] ?? '#ffffff';
$pagespeed = isset($announceCfg['pagespeed']) ? (int)$announceCfg['pagespeed'] : 5;

// Handle POST
$feedback = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled   = isset($_POST['enabled']) ? 1 : 0;
    $bg        = $_POST['background'] ?? '#000000';
    $fg        = $_POST['foreground'] ?? '#ffffff';
    $pagespeed = max(1, (int)($_POST['pagespeed'] ?? 5));
    $announceText = $_POST['announce'] ?? '';

    if (file_put_contents($announceFile, $announceText) === false) {
        http_response_code(500);
        $feedback = "Failed to write announce.txt";
    } else {
        $newAnnounce = [
            'enabled'    => $enabled,
            'background' => $bg,
            'foreground' => $fg,
            'pagespeed'  => $pagespeed,
        ];
        if (!ini_write_section($settingsFile, 'announce', $newAnnounce)) {
            $feedback = "Failed to update settings.ini.";
        } else {
            $feedback = "Announce settings saved.";
        }

        @mkdir($tmpDir, 0777, true);
        $parts = array_values(array_filter(array_map('trim', preg_split('/\[===\]/', $announceText))));
        $firstPage = $parts[0] ?? ' ';
        @file_put_contents($currentTxt, $firstPage);

        if (write_supervisor_conf($newAnnounce, $serverIP, $fontFile, $supervisorConfFile, $streamDir, $tmpDir)) {
            if ($enabled) {
                shell_exec("supervisorctl start iptv-announce iptv-announce-rotator 2>&1");
                shell_exec("supervisorctl restart iptv-announce iptv-announce-rotator 2>&1");
                $feedback .= " Services started.";
            } else {
                // Stop services
                shell_exec("supervisorctl stop iptv-announce iptv-announce-rotator 2>&1");

                // --- AUTOMATIC CLEANUP: Clear announce folder ---
                if (is_dir($streamDir)) {
                    foreach (glob("$streamDir/*") as $file) {
                        if (is_file($file) || is_link($file)) {
                            @unlink($file);
                        }
                    }
                }
                // --- END CLEANUP ---

                $feedback .= " Services stopped.";
            }
        } else {
            $feedback .= " Failed to write supervisor config.";
        }
    }
}

// Service status
$statusOut = shell_exec("supervisorctl status iptv-announce iptv-announce-rotator 2>&1");
$running = (strpos($statusOut ?? '', "RUNNING") !== false);
$textContent = is_file($announceFile) ? file_get_contents($announceFile) : "";

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Announce Channel</title>
    <link rel="stylesheet" href="includes/css/<?= htmlspecialchars($theme) ?>.css">
    <style>
        textarea { font-family: monospace; width: 37ch; height: 420px; }
        .feedback { color: #0a0; font-weight: bold; margin-top: 10px; }
        .status { font-weight: bold; color: <?= $running ? 'limegreen' : 'red' ?>; }
        .form-row { margin: 8px 0; }
        label.inline { display:inline-block; min-width: 220px; }
        #warning { color:red; font-weight:bold; display:none; margin-top:5px; }
    </style>
</head>
<body>
<?php @include $BASE_DIR . "/includes/menu.php"; ?>

<h1>Announce Channel</h1>
<p class="status">Service Status: <?= $running ? "Running" : "Stopped" ?></p>

<form method="POST">
    <div class="form-row">
        <label><input type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?>> Enable Announce Stream</label>
    </div>

    <div class="form-row">
        <label class="inline">Background Color:</label>
        <input type="color" name="background" value="<?= htmlspecialchars($bg) ?>">
    </div>

    <div class="form-row">
        <label class="inline">Text Color:</label>
        <input type="color" name="foreground" value="<?= htmlspecialchars($fg) ?>">
    </div>

    <div class="form-row">
        <label class="inline">Page Display Time -use even numbers (seconds) 10sec minimum:</label>
        <input type="number" name="pagespeed" value="<?= (int)$pagespeed ?>" min="1">
    </div>

    <div class="form-row">
        <label>Announce Text <br>
        37 characters max per line <br>
        percent symbol must be escaped with a backslash \% <br>
        separate pages using [===]:</label><br>
        10 lines maximum<br>
        <textarea id="announceText" name="announce"><?= htmlspecialchars($textContent) ?></textarea>
        <p id="warning">One or more lines exceed 37 characters!</p>
    </div>

    <div class="form-row">
        <input type="submit" value="Save and Apply">
    </div>
</form>

<?php if (!empty($feedback)): ?>
    <p class="feedback"><?= htmlspecialchars($feedback) ?></p>
<?php endif; ?>

<pre style="background:#111;color:#ddd;padding:10px;white-space:pre-wrap;border-radius:8px;max-height:240px;overflow:auto;">
<?= htmlspecialchars($statusOut ?? '') ?>
</pre>

<script>
const textarea = document.getElementById('announceText');
const warning = document.getElementById('warning');

textarea.addEventListener('input', () => {
    const pages = textarea.value.split(/\[===\]/);
    let tooLong = false;
    pages.forEach(page => {
        const lines = page.split(/\r?\n/);
        if (lines.some(line => line.length > 37)) tooLong = true;
    });
    warning.style.display = tooLong ? 'block' : 'none';
});
</script>

</body>
</html>
