<?php
// /IPTV/includes/snapshot.php

error_reporting(E_ALL);

$BASE_DIR = '/IPTV';
$channelsFile = $BASE_DIR . '/channels.xml';
$snapshotDir = $BASE_DIR . '/snapshots';

if (!file_exists($channelsFile)) {
    die("channels.xml not found\n");
}

if (!is_dir($snapshotDir)) {
    mkdir($snapshotDir, 0755, true);
}

$xml = simplexml_load_file($channelsFile);

foreach ($xml->channel as $channel) {

    $name = (string)$channel->name;
    $url  = (string)$channel->url;

    if (!$name || !$url) {
        continue;
    }

    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '', $name);

    $outputFile = $snapshotDir . '/' . $safeName . '.jpg';

    $cmd = sprintf(
        'ffmpeg -rtsp_transport tcp -i %s -frames:v 1 -vf scale=100:-1 -q:v 5 -y %s 2>/dev/null',
        escapeshellarg($url),
        escapeshellarg($outputFile)
    );

    echo "Generating snapshot for {$name}\n";

    exec($cmd, $output, $returnCode);

    if ($returnCode === 0 && file_exists($outputFile)) {
        echo "SUCCESS: {$outputFile}\n";
    } else {
        echo "FAILED: {$name}\n";
    }
}
