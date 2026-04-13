<?php
// /IPTV/includes/get-status.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$BASE_DIR = dirname(__DIR__); // /IPTV
$channelsFile = $BASE_DIR . '/channels.xml';
$healthFile   = $BASE_DIR . '/includes/health.json';
$lastRestartFile = $BASE_DIR . '/includes/last_restart.json';

// --- Run the health check first ---
$healthCheckFile = $BASE_DIR . '/includes/health-check.php';
if (file_exists($healthCheckFile)) {
    ob_start();
    include $healthCheckFile;
    ob_end_clean();
}

// load health and last_restart
$healthData = file_exists($healthFile) ? json_decode(file_get_contents($healthFile), true) : [];
$lastRestart = file_exists($lastRestartFile) ? json_decode(file_get_contents($lastRestartFile), true) : [];
$now = time();

// ensure channels.xml exists
if (!file_exists($channelsFile)) {
    echo json_encode([]);
    exit;
}

$xml = simplexml_load_file($channelsFile);
$out = [];

$logFile = '/var/log/gsrestart.log';

// ---------------------------
// Process ALL channels (incl. Announce)
// ---------------------------

// Existing channels from XML
foreach ($xml->channel as $channel) {

    $name = (string)$channel->name;
    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '', $name);

    // ensure enabled element exists; default true
    $enabled = isset($channel->enabled) ? strtolower((string)$channel->enabled) === 'true' : true;

    // supervisor status
    $supervisorOutput = @shell_exec("supervisorctl status iptv-$safeName 2>/dev/null");
    $running = (strpos($supervisorOutput, 'RUNNING') !== false);

    // health from health.json
    $health = $healthData[$safeName]['status'] ?? 'bad';
    $health = ($health === 'good') ? 'good' : 'bad';

    // last restart
    $lr = $lastRestart[$safeName] ?? 'never';

    // --------------------
    // Determine final state + label
    // --------------------
    if (!$enabled) {
        $state = 'off';
        $label = 'Disabled';
    } else {
        if ($running) {
            $state = 'running';
            $label = ($health === 'good') ? 'Running' : 'Running (no output)';
        } else {
            // not running and enabled
            $state = 'failed';
            $label = 'Failed';
        }
    }

    // --------------------
    // Restart Logic (Based on final state)
    // --------------------
    $stateLower = strtolower($state);

    if ($stateLower === 'off') {
        $shouldRestart = false;
    } elseif ($stateLower === 'running' && $health === 'good') {
        $shouldRestart = false;
    } elseif ($stateLower === 'stopped') {
        $shouldRestart = false;
    } else {
        // failed, unknown, running+bad, etc.
        $shouldRestart = true;
    }

    if ($shouldRestart) {
        $lastRestartTime = isset($lastRestart[$safeName]) ? strtotime($lastRestart[$safeName]) : 0;

        if (($now - $lastRestartTime) > 60) {

            @file_put_contents(
                $logFile,
                "[" . date('Y-m-d H:i:s') . "] Restarting $safeName (state=$stateLower, health=$health)\n",
                FILE_APPEND
            );

            @shell_exec("supervisorctl stop iptv-$safeName >> " . escapeshellarg($logFile) . " 2>&1");
            sleep(1);
            @shell_exec("supervisorctl start iptv-$safeName >> " . escapeshellarg($logFile) . " 2>&1");

            $lastRestart[$safeName] = date('Y-m-d H:i:s');
            file_put_contents($lastRestartFile, json_encode($lastRestart, JSON_PRETTY_PRINT));
            $lr = $lastRestart[$safeName];

        } else {
            @file_put_contents(
                $logFile,
                "[" . date('Y-m-d H:i:s') . "] Cooldown active, skip restart for $safeName\n",
                FILE_APPEND
            );
        }
    }

    // Output array
    $out[] = [
        'id'            => $safeName,
        'name'          => $name,
        'status'        => $running ? 'running' : 'stopped',
        'state'         => $state,
        'label'         => $label,
        'enabled'       => $enabled,
        'health'        => $health,
        'last_restart'  => $lr
    ];
}

// ----------------------------
// Add Announce Service (same rules)
// ----------------------------
$announceSafeName = 'announce';
$announceName = 'Announce Channel';

// determine if announce exists in supervisor
$announceOutput = @shell_exec("supervisorctl status iptv-announce 2>/dev/null");
$announceRunning = (strpos($announceOutput, 'RUNNING') !== false);

// compute health
$announceHealth = 'bad';
$file = '/LIVE/announce/index.m3u8';
if (is_file($file) && (time() - filemtime($file)) < 60) {
    $announceHealth = 'good';
}

$announceEnabled = true;
$announceState = $announceRunning ? 'running' : 'failed';
if ($announceState === 'running' && $announceHealth !== 'good') {
    $announceState = 'failed';
}

$announceLabel = $announceRunning
    ? ($announceHealth === 'good' ? 'Running' : 'Running (no output)')
    : 'Failed';

$announceLastRestart = $lastRestart[$announceSafeName] ?? 'never';

// ---- restart logic for announce ----
$stateLower = strtolower($announceState);

if ($stateLower === 'off') {
    $shouldRestart = false;
} elseif ($stateLower === 'running' && $announceHealth === 'good') {
    $shouldRestart = false;
} elseif ($stateLower === 'stopped') {
    $shouldRestart = false;
} else {
    $shouldRestart = true;
}

if ($shouldRestart) {

    $lastRestartTime = isset($lastRestart[$announceSafeName]) ? strtotime($lastRestart[$announceSafeName]) : 0;

    if (($now - $lastRestartTime) > 60) {

        @file_put_contents(
            $logFile,
            "[" . date('Y-m-d H:i:s') . "] Restarting announce (state=$stateLower, health=$announceHealth)\n",
            FILE_APPEND
        );

        @shell_exec("supervisorctl stop iptv-announce >> " . escapeshellarg($logFile) . " 2>&1");
        sleep(1);
        @shell_exec("supervisorctl start iptv-announce >> " . escapeshellarg($logFile) . " 2>&1");

        $lastRestart[$announceSafeName] = date('Y-m-d H:i:s');
        file_put_contents($lastRestartFile, json_encode($lastRestart, JSON_PRETTY_PRINT));
        $announceLastRestart = $lastRestart[$announceSafeName];

    } else {
        @file_put_contents(
            $logFile,
            "[" . date('Y-m-d H:i:s') . "] Cooldown active, skip restart for announce\n",
            FILE_APPEND
        );
    }
}

$out[] = [
    'id'            => $announceSafeName,
    'name'          => $announceName,
    'status'        => $announceRunning ? 'running' : 'stopped',
    'state'         => $announceState,
    'label'         => $announceLabel,
    'enabled'       => $announceEnabled,
    'health'        => $announceHealth,
    'last_restart'  => $announceLastRestart
];

echo json_encode($out, JSON_PRETTY_PRINT);
