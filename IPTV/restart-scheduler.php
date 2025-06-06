<?php
$settings = parse_ini_file(__DIR__ . '/includes/server.ini');
$targetTime = isset($settings['restart_time']) ? $settings['restart_time'] : null;

$current = date('H:i');
$datestamp = date('Y-m-d H:i');

// Only run at the exact scheduled time
if ($current === $targetTime) {
    // Get list of supervisor services
    exec("supervisorctl status", $output);

    foreach ($output as $line) {
        // Only restart services that start with 'iptv-'
        if (preg_match('/^iptv\-([\w\-]+)/', $line, $matches)) {
            $service = $matches[0]; // full name like 'iptv-camera1cc'

            // Restart the service
            exec("supervisorctl stop $service");
            sleep(1); // short delay to avoid overlap issues
            exec("supervisorctl start $service");
            file_put_contents("/var/log/restart.log", "Restart process initiated for $service at $datestamp\n", FILE_APPEND);

        }
    }
}
