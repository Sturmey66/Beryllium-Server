<?php
// /IPTV/live-cameras.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$BASE_DIR = '/IPTV';
include $BASE_DIR . '/includes/menu.php';
require_once $BASE_DIR . '/includes/init.php';
require_once $BASE_DIR . '/includes/functions.php';

$channelsFile = $BASE_DIR . '/channels.xml';
$healthFile   = $BASE_DIR . '/includes/health.json';
$lastRestartFile = $BASE_DIR . '/includes/last_restart.json';
$supervisorConfDir = $BASE_DIR . '/supervisor';

// init files if missing
if (!file_exists($lastRestartFile)) file_put_contents($lastRestartFile, json_encode([]));
if (!file_exists($healthFile)) file_put_contents($healthFile, json_encode([]));

// load data
$lastRestartData = json_decode(file_get_contents($lastRestartFile), true) ?: [];
$healthData = json_decode(file_get_contents($healthFile), true) ?: [];

// POST handling (buttons POST to same page)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? null;
    $url  = $_POST['url']  ?? null;
    $safeName = $name ? preg_replace('/[^A-Za-z0-9_-]/', '', $name) : null;

    // Start service
    if ($name && isset($_POST['startService'])) {
        // ensure enabled true in XML
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->load($channelsFile);
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//channel') as $ch) {
            $n = $ch->getElementsByTagName('name')->item(0);
            if ($n && strcasecmp($n->nodeValue, $name) === 0) {
                $enabledNodes = $ch->getElementsByTagName('enabled');
                if ($enabledNodes->length) {
                    $enabledNodes->item(0)->nodeValue = 'true';
                } else {
                    $ch->appendChild($dom->createElement('enabled', 'true'));
                }
                break;
            }
        }
        $dom->save($channelsFile);

        // create conf and start supervisor
        createSupervisorService($safeName, $url ?? '', $serverIP, $serverPort, $supervisorConfDir, $OUTPUT_DIR);
        shell_exec("supervisorctl start iptv-$safeName 2>&1");

        // update last_restart
        $data = json_decode(file_get_contents($lastRestartFile), true);
        $data[$safeName] = date('Y-m-d H:i:s');
        file_put_contents($lastRestartFile, json_encode($data));

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Stop service
    if ($name && isset($_POST['stopService'])) {
        shell_exec("supervisorctl stop iptv-$safeName 2>&1");

        // set enabled=false in XML
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->load($channelsFile);
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//channel') as $ch) {
            $n = $ch->getElementsByTagName('name')->item(0);
            if ($n && strcasecmp($n->nodeValue, $name) === 0) {
                $enabledNodes = $ch->getElementsByTagName('enabled');
                if ($enabledNodes->length) {
                    $enabledNodes->item(0)->nodeValue = 'false';
                } else {
                    $ch->appendChild($dom->createElement('enabled', 'false'));
                }
                break;
            }
        }
        $dom->save($channelsFile);

        // remove stream directory
        $streamDir = rtrim($OUTPUT_DIR, '/') . "/$safeName";
        if (is_dir($streamDir)) {
            $files = glob($streamDir . '/*');
            foreach ($files as $f) if (is_file($f)) unlink($f);
            rmdir($streamDir);
        }

        // update last_restart
        $data = json_decode(file_get_contents($lastRestartFile), true);
        $data[$safeName] = date('Y-m-d H:i:s');
        file_put_contents($lastRestartFile, json_encode($data));

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Add new camera
    if ($name && isset($_POST['addCamera'])) {
        addCamera($name, $url, $_POST['tv-logo'] ?? '', $channelsFile);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Restart service
    if ($name && isset($_POST['restartService'])) {
        shell_exec("supervisorctl stop iptv-$safeName 2>&1");
        sleep(1);
        createSupervisorService($safeName, $url ?? '', $serverIP, $serverPort, $supervisorConfDir, $OUTPUT_DIR);
        shell_exec("supervisorctl reread && supervisorctl update");
        shell_exec("supervisorctl start iptv-$safeName 2>&1");

        $data = json_decode(file_get_contents($lastRestartFile), true);
        $data[$safeName] = date('Y-m-d H:i:s');
        file_put_contents($lastRestartFile, json_encode($data));

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // Delete camera
    if ($name && isset($_POST['deleteCamera'])) {
        // remove from channels.xml
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->load($channelsFile);
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//channel') as $ch) {
            $n = $ch->getElementsByTagName('name')->item(0);
            if ($n && strcasecmp($n->nodeValue, $name) === 0) {
                $ch->parentNode->removeChild($ch);
                break;
            }
        }
        $dom->save($channelsFile);

        // stop supervisor and remove conf
        shell_exec("supervisorctl stop iptv-$safeName 2>&1");
        $confFile = $supervisorConfDir . "/iptv-$safeName.conf";
        if (file_exists($confFile)) unlink($confFile);
        shell_exec("supervisorctl reread && supervisorctl update");

        // remove stream directory
        $streamDir = rtrim($OUTPUT_DIR, '/') . "/$safeName";
        if (is_dir($streamDir)) {
            array_map('unlink', glob("$streamDir/*"));
            rmdir($streamDir);
        }

        // cleanup health and last_restart
        $h = json_decode(file_get_contents($healthFile), true) ?: [];
        unset($h[$safeName]);
        file_put_contents($healthFile, json_encode($h));

        $lr = json_decode(file_get_contents($lastRestartFile), true) ?: [];
        unset($lr[$safeName]);
        file_put_contents($lastRestartFile, json_encode($lr));

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load channels xml for display
$xml = file_exists($channelsFile) ? simplexml_load_file($channelsFile) : null;

// --- Run health-check once on page load ---
$healthCheckFile = $BASE_DIR . '/health-check.php';
if (file_exists($healthCheckFile)) {
    ob_start();
    include $healthCheckFile;
    ob_end_clean();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Live Camera Services</title>
<style>
table { border-collapse: collapse; width: 100%; }
th, td { border: 2px solid black; padding: 6px; text-align: left; }
.status-dot { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; vertical-align: middle; }
.green { background: green; }
.red   { background: red; }
.yellow { background: yellow; }
</style>
<script>
const STATUS_URL = '/includes/get-status.php';
function updateStatuses() {
    fetch(STATUS_URL + '?_=' + Date.now())
        .then(res => res.json())
        .then(data => {
            data.forEach(item => {
                const serviceDot = document.getElementById('status-' + item.id);
                const serviceText = document.getElementById('status-text-' + item.id);
                const healthDot = document.getElementById('health-' + item.id);
                const startCell = document.getElementById('start-' + item.id);

                if (serviceDot) serviceDot.className = 'status-dot ' + (item.status === 'running' ? 'green' : 'red');
                if (serviceText) serviceText.textContent = item.label;

                if (healthDot) {
                    let cls = 'red';
                    if (!item.enabled) cls = 'red';
                    else if (item.enabled && item.status === 'running' && item.health === 'good') cls = 'green';
                    else if (item.enabled && item.status === 'running' && item.health === 'bad') cls = 'yellow';
                    healthDot.className = 'status-dot ' + cls;
                }

                if (startCell) startCell.textContent = item.last_restart ?? 'Never';
            });
        })
        .catch(e => console.error('status fetch error', e));
}
document.addEventListener('DOMContentLoaded', () => {
    updateStatuses();
    setInterval(updateStatuses, 5000);
});
</script>
</head>
<body>
<h2>Live Camera Services</h2>

<?php if (!empty($feedback)): ?>
<p><strong><?= htmlspecialchars($feedback) ?></strong></p>
<?php endif; ?>

<h3>Add New Camera</h3>
<form method="post">
    <label>Camera Name:</label>
    <input type="text" name="name" required>
    <label>RTSP URL:</label>
    <input type="text" name="url" required>
    <label>TV Logo URL:</label>
    <input type="text" name="tv-logo">
    <input type="submit" name="addCamera" value="Add Camera">
</form>

<h3>Existing Cameras</h3>

<?php if ($xml && count($xml->channel) > 0): ?>
<table>
<thead>
<tr>
    <th>Name</th>
    <th>URL</th>
    <th>Service Status</th>
    <th>Start Time</th>
    <th>Health</th>
    <th>Control</th>
</tr>
</thead>
<tbody>
<?php foreach ($xml->channel as $channel):
    $name = (string)$channel->name;
    $url  = (string)$channel->url;
    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '', $name);
    $startTime = htmlspecialchars($lastRestartData[$safeName] ?? 'Never');
?>
<tr>
    <td><?= htmlspecialchars($name) ?></td>
    <td><?= htmlspecialchars($url) ?></td>
    <td>
        <span id="status-<?= $safeName ?>" class="status-dot red"></span>
        (<span id="status-text-<?= $safeName ?>">unknown</span>)
    </td>
    <td id="start-<?= $safeName ?>"><?= $startTime ?></td>
    <td><span id="health-<?= $safeName ?>" class="status-dot red"></span></td>
    <td>
        <form method="post" style="display:inline;">
            <input type="hidden" name="name" value="<?= htmlspecialchars($name) ?>">
            <input type="hidden" name="url" value="<?= htmlspecialchars($url) ?>">
            <input type="submit" name="restartService" value="Restart">
            <input type="submit" name="startService" value="Start">
            <input type="submit" name="stopService" value="Stop">
            <input type="submit" name="deleteCamera" value="Delete">
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p>No cameras found.</p>
<?php endif; ?>

</body>
</html>
