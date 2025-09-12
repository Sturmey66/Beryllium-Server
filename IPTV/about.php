<?php 
include "./includes/auth.php"; // Session and login check
require_once __DIR__ . "/includes/functions.php";
?>
<?php
$settingsFile = __DIR__ . "/includes/server.ini";
$serverIni = parse_ini_file(__DIR__ . "/includes/server.ini");
$serverIP = $serverIni['serverIP'] ?? '127.0.0.1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Settings</title>
</head>
<body>
<?php include "./includes/menu.php"; ?>
<h2>About Beryllium Server</h2>

<p>
Beryllium server was created in response to the need for a simple way to create a closed circuit camera for IPTV.
<br>
Live cameras for TV are useful in community centers, retirement communities, churchs, and wherever a live feed would be useful for people who aren't able to attend in person, or where extending the live space is useful.
<br>
This verion is meant for local access and is not hardened for public access. It is recommended that this server be kept behind a firewall and not published to the interet. 
<br>
<a href="license.txt">The license for use can be found here</a>

</body>
</html>

