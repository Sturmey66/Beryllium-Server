<?php
function getSupervisorStatus($name) {
    $output = shell_exec("supervisorctl status iptv-$name 2>/dev/null");
    if (!$output) return "unknown";
    return (strpos($output, 'RUNNING') !== false) ? 'running' : 'stopped';
}

// You can move other shared logic here too.
