<?php
// Include init.php to get variables like $OUTPUT_DIR
include __DIR__ . '/includes/init.php';

$healthFile = __DIR__ . '/includes/health.json';
$healthData = [];

// Get list of supervisor services
exec("supervisorctl status", $services);

foreach ($services as $line) {
    if (preg_match('/^iptv\-([\w\-]+)/', $line, $matches)) {
        $safeName = $matches[1];
        $targetDir = "$OUTPUT_DIR/$safeName"; // index.m3u8 path
        $m3u8File = "$targetDir/index.m3u8";

        if (file_exists($m3u8File)) {
            $fileModTime = filemtime($m3u8File);
            $ageSeconds = time() - $fileModTime;

            if ($ageSeconds < 60) {
                $status = 'running';
            } else {
                $status = 'stopped';
            }

            $healthData[$safeName] = [
                'status' => $status,
                'last_update' => date('Y-m-d H:i:s', $fileModTime)
            ];
        } else {
            $healthData[$safeName] = [
                'status' => 'stopped',
                'last_update' => 'never'
            ];
        }
    }
}

// Save health info without touching last_restart.json
file_put_contents($healthFile, json_encode($healthData, JSON_PRETTY_PRINT));

// get the date and time
$dateTime = new DateTime();
$time = $dateTime->format(DateTime::ATOM);

echo "restart-scheduler has run at $time";

