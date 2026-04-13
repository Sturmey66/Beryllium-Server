<?php
// Shared functions for IPTV

// ------------------ General Helpers ------------------
function build_ini_string(array $ini): string
{
    $out = '';
    foreach ($ini as $section => $values) {
        $out .= "[$section]\n";
        if (is_array($values)) {
            foreach ($values as $k => $v) {
                $v = str_replace('"', '\"', (string)$v);
                $out .= "$k = \"$v\"\n";
            }
        }
        $out .= "\n";
    }
    return $out;
}

function sanitize($text): string
{
    return htmlspecialchars(trim($text), ENT_QUOTES);
}

// ------------------ Supervisor Helpers ------------------
function getSupervisorStatus($name): string
{
    $output = shell_exec("supervisorctl status iptv-$name 2>/dev/null");
    if (!$output) return "unknown";
    return (strpos($output, 'RUNNING') !== false) ? 'running' : 'stopped';
}

function createSupervisorService($name, $url, $serverIP, $serverPort, $supervisorConfDir, $OUTPUT_DIR)
{
    $targetDir = $OUTPUT_DIR . "/$name";
    @mkdir($targetDir, 0777, true);

    // Encode special characters in username/password for RTSP
    $url = preg_replace_callback(
        '/(rtsp:\/\/)([^:]+):([^@]+)@/',
        function($m){ return $m[1] . $m[2] . ':' . rawurlencode($m[3]) . '@'; },
        $url
    );

    $streamURL = "http://$serverIP:$serverPort/$name/";
    $quotedURL = escapeshellarg($url);

    $cmd = "/usr/bin/ffmpeg -hide_banner -rtsp_transport tcp -rtbufsize 1G -i $quotedURL " .
           "-fps_mode cfr -c:v libx264 -b:v 1000k -minrate:v 1000k -maxrate:v 1000k " .
           "-c:a copy -f hls -r 30 -vlevel 3.0 -x264-params keyint=15:min-keyint=15 " .
           "-hls_time 2 -hls_list_size 20 -http_persistent 0 -hls_flags delete_segments " .
           "-hls_start_number_source datetime -preset ultrafast -hls_base_url \"$streamURL\" " .
           "-hls_segment_type mpegts -hls_segment_filename $targetDir/fileSequence%%d.ts " .
           "$targetDir/index.m3u8";

    $conf = "[program:iptv-$name]
command=$cmd
autostart=true
autorestart=true
stderr_logfile=/var/log/iptv-$name.err.log
stdout_logfile=/var/log/iptv-$name.out.log
";

    file_put_contents("$supervisorConfDir/iptv-$name.conf", $conf);
    shell_exec("supervisorctl reread");
    shell_exec("supervisorctl update");
}

// ------------------ Camera XML Helpers ------------------
function addCamera($name, $url, $logo, $channelsFile)
{
    $xml = simplexml_load_file($channelsFile);
    $newChannel = $xml->addChild('channel');
    $newChannel->addChild('name', $name);
    $newChannel->addChild('url', $url);
    $newChannel->addChild('tv-logo', $logo);
    $xml->asXML($channelsFile);
}

function deleteCamera($name, $channelsFile)
{
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->load($channelsFile);

    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//channel') as $channel) {
        $channelName = $channel->getElementsByTagName('name')->item(0)->nodeValue;
        if (strcasecmp($channelName, $name) === 0) {
            $channel->parentNode->removeChild($channel);
            break;
        }
    }

    $dom->save($channelsFile);
}

// ------------------ Announce Channel Helpers ------------------
function addAnnounceChannelToChannelsXml(string $name, string $serverIP, int $serverPort): bool
{
    $streamURL = "http://$serverIP:$serverPort/$name/";
    $channelsFile = '/IPTV/channels.xml';
    if (!file_exists($channelsFile)) return false;

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->load($channelsFile);

    $xpath = new DOMXPath($dom);

    // Remove existing announce channel
    foreach ($xpath->query('//channel') as $ch) {
        $nameNode = $ch->getElementsByTagName('name')->item(0);
        if ($nameNode && strcasecmp(trim($nameNode->nodeValue), $name) === 0) {
            $ch->parentNode->removeChild($ch);
            break;
        }
    }

    // Create announce channel
    $channel = $dom->createElement('channel');
    $channel->appendChild($dom->createElement('name', $name));
    $channel->appendChild($dom->createElement('url', "{$streamURL}index.m3u8"));
    $channel->appendChild($dom->createElement('tv-logo', ''));
    $channel->appendChild($dom->createElement('enabled', 'false'));

    $dom->documentElement->insertBefore($channel, $dom->documentElement->firstChild);
    $dom->save($channelsFile);

    return true;
}

function removeAnnounceChannelFromChannelsXml(): bool
{
    $channelsFile = '/IPTV/channels.xml';
    if (!file_exists($channelsFile)) return false;

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->load($channelsFile);

    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//channel') as $ch) {
        $nameNode = $ch->getElementsByTagName('name')->item(0);
        if ($nameNode && strcasecmp(trim($nameNode->nodeValue), 'announce') === 0) {
            $ch->parentNode->removeChild($ch);
            $dom->save($channelsFile);
            return true;
        }
    }

    return true;
}

// ------------------ Announce Supervisor ------------------
function createAnnounceSupervisorService(
    string $name,
    string $serverIP,
    int $serverPort,
    string $supervisorConfFile,
    string $streamDir,
    string $tmpDir,
    string $fontFile,
    string $announceFile
): void {
    $streamURL = "http://$serverIP:$serverPort/$name/";

    $ini = parse_ini_file('/IPTV/includes/server.ini', true, INI_SCANNER_TYPED);
    $cfg = $ini['announce'] ?? [];
    $enabled = !empty($cfg['enabled']) && $cfg['enabled'] === 'true';

    // --- If not enabled, stop service and remove conf ---
    if (!$enabled) {
        shell_exec("supervisorctl stop iptv-announce 2>&1");
        shell_exec("supervisorctl stop iptv-announce-rotator 2>&1");
        if (file_exists($supervisorConfFile)) unlink($supervisorConfFile);
        shell_exec("supervisorctl reread 2>&1");
        shell_exec("supervisorctl update 2>&1");
        return;
    }

    // --- Ensure directories exist ---
    @mkdir($streamDir, 0777, true);
    @mkdir($tmpDir, 0777, true);

    // --- Ensure current.txt exists ---
    $currentText = "$tmpDir/current.txt";
    if (!file_exists($currentText) || filesize($currentText) === 0) {
        file_put_contents($currentText, " "); // placeholder
    }

    $bg = $cfg['background'] ?? '#000000';
    $fg = $cfg['foreground'] ?? '#ffffff';
    $pagespeed = max(1, (int)($cfg['pagespeed'] ?? 10));
    $segments  = ($pagespeed * 15) + 30;

    // drawtext filter for ffmpeg
    $vfFilter = "drawtext=fontfile={$fontFile}:textfile={$currentText}:reload=1:fix_bounds=1:expansion=none:text_shaping=0:fontcolor={$fg}:fontsize=84:x=40:y=40:line_spacing=-46";

    // ffmpeg command using static color input and dynamic text
    $ffmpegCmd = "/usr/bin/ffmpeg -hide_banner -f lavfi -i color=c={$bg}:s=1920x1080:r=30 " .
                 "-vf {$vfFilter} -c:v libx264 -profile:v baseline -level 3.0 " .
                 "-b:v 5000k -maxrate 5000k -bufsize 600k -r 30 " .
                 "-g 60 -sc_threshold 0 -force_key_frames \"expr:gte(t,n_forced*2)\" -preset ultrafast " .
                 "-c:a aac -ac 2 -ar 44100 -b:a 96k -strict -2 " .
                 "-f hls -hls_time 2 -hls_list_size {$segments} " .
                 "-hls_flags independent_segments+delete_segments+split_by_time " .
                 "-hls_segment_type mpegts " .
                 "-hls_base_url {$streamURL} " .
                 "-hls_segment_filename {$streamDir}/fileSequence%%05d.ts {$streamDir}/index.m3u8";

    // rotator command to update current.txt
    $rotatorCmd = "/usr/bin/php83 $announceFile";

    // Supervisor configuration
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

    file_put_contents($supervisorConfFile, $conf);

    // Reload Supervisor to pick up changes
    shell_exec("supervisorctl reread 2>&1");
    shell_exec("supervisorctl update 2>&1");
}
