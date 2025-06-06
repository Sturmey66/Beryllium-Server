<?php
function getSupervisorStatus($name) {
    $output = shell_exec("supervisorctl status iptv-$name 2>/dev/null");
    if (!$output) return "unknown";
    return (strpos($output, 'RUNNING') !== false) ? 'running' : 'stopped';
}

$channelsFile = __DIR__ . "/../channels.xml";
$xml = simplexml_load_file($channelsFile);

$response = [];
foreach ($xml->channel as $channel) {
    $name = (string)$channel->name;
    $status = getSupervisorStatus(basename($name));
    $response[] = ['name' => $name, 'status' => $status];
}

header('Content-Type: application/json');
echo json_encode($response);
