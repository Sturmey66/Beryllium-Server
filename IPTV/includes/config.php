<?php
function loadConfig($file) {
    return file_exists($file) ? parse_ini_file($file, true) : [];
}

function saveConfigSection($file, $section, $newValues) {
    $config = loadConfig($file);
    $config[$section] = array_merge($config[$section] ?? [], $newValues);
    return writeIniFile($file, $config);
}

function writeIniFile($file, $array) {
    $output = '';
    foreach ($array as $section => $settings) {
        $output .= "[$section]\n";
        foreach ($settings as $key => $value) {
            $output .= "$key = " . (is_numeric($value) ? $value : "\"$value\"") . "\n";
        }
        $output .= "\n";
    }
    return file_put_contents($file, $output) !== false;
}
?>
