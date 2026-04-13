<?php
// /IPTV/services.php
// Lightweight service actions endpoint; can be used by scripts or form posts.
// Supports: start, stop, restart, enable, disable for a channel name.

error_reporting(E_ALL);
ini_set('display_errors', 1);

$BASE_DIR = '/IPTV';
require_once $BASE_DIR . '/includes/init.php';      // defines $channelsFile, $OUTPUT_DIR, $serverIP, $serverPort
require_once $BASE_DIR . '/includes/functions.php'; // existing helpers

// Simple auth check if you have it in auth.php - optional
// include $BASE_DIR . '/includes/auth.php';

$action = $_REQUEST['action'] ?? null;
$name   = $_REQUEST['name'] ?? null;
if (!$action || !$name) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'action and name required']);
    exit;
}

$safeName = preg_replace('/[^A-Za-z0-9_-]/', '', $name);

// ensure channels.xml exists
if (!file_exists($channelsFile)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => "channels file missing"]);
    exit;
}

// helper to update <enabled> for a channel (true/false)
function setChannelEnabled($channelsFile, $channelName, $enabled) {
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->load($channelsFile);
    $xpath = new DOMXPath($dom);

    foreach ($xpath->query('//channel') as $ch) {
        $nameNode = $ch->getElementsByTagName('name')->item(0);
        if (!$nameNode) continue;
        if (strcasecmp($nameNode->nodeValue, $channelName) === 0) {
            $enabledNodes = $ch->getElementsByTagName('enabled');
            if ($enabledNodes->length) {
                $enabledNodes->item(0)->nodeValue = $enabled ? 'true' : 'false';
            } else {
                $el = $dom->createElement('enabled', $enabled ? 'true' : 'false');
                $ch->appendChild($el);
            }
            $dom->save($channelsFile);
            return true;
        }
    }
    return false;
}

// perform action
$result = ['ok' => true, 'action' => $action, 'name' => $safeName];

switch ($action) {
    case 'start':
        // set enabled true in channels.xml
        setChannelEnabled($channelsFile, $name, true);
        // (re)create supervisor conf and start
        createSupervisorService($safeName, $_REQUEST['url'] ?? '', $serverIP, $serverPort, $BASE_DIR . '/supervisor', $OUTPUT_DIR);
        shell_exec("supervisorctl start iptv-$safeName 2>&1");
        $result['msg'] = "Started iptv-$safeName";
        break;

    case 'stop':
        // stop and mark disabled
        shell_exec("supervisorctl stop iptv-$safeName 2>&1");
        setChannelEnabled($channelsFile, $name, false);
        $result['msg'] = "Stopped iptv-$safeName and marked disabled";
        break;

    case 'restart':
        shell_exec("supervisorctl stop iptv-$safeName 2>&1");
        sleep(1);
        createSupervisorService($safeName, $_REQUEST['url'] ?? '', $serverIP, $serverPort, $BASE_DIR . '/supervisor', $OUTPUT_DIR);
        shell_exec("supervisorctl start iptv-$safeName 2>&1");
        $result['msg'] = "Restarted iptv-$safeName";
        break;

    case 'enable':
        setChannelEnabled($channelsFile, $name, true);
        $result['msg'] = "Enabled $name in channels.xml";
        break;

    case 'disable':
        setChannelEnabled($channelsFile, $name, false);
        $result['msg'] = "Disabled $name in channels.xml";
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'unknown action']);
        exit;
}

header('Content-Type: application/json');
echo json_encode($result);
