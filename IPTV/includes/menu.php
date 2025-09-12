<?php
$ini_array = parse_ini_file(__DIR__ . "/server.ini", true);
$server = $ini_array["server"] ?? "IPTV Server";
?>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <?php
	if (!isset($_SESSION['theme']) && isset($_COOKIE['theme'])) {
	    $_SESSION['theme'] = $_COOKIE['theme'];
	}

$theme = $_SESSION['theme'] ?? 'theme-light';
?>
<link rel="stylesheet" href="./includes/css/<?= htmlspecialchars($theme) ?>.css">
</head>

<header>
    <figure>
        <h1>Beryllium Server</h1>
        <figcaption>
            <p>making IPTV broadcasting accessible</p>
        </figcaption>
    </figure>
    

<script>
function updateTime() {
    const now = new Date();
    const formatted = now.toISOString().slice(0, 16).replace('T', ' ');
    document.getElementById('system-time').textContent = 'System Time: ' + formatted;
}
setInterval(updateTime, 1000);
updateTime();
</script>

<div class="header-bar">
<h2><?= htmlspecialchars($server) ?></h2>
<div id="system-time" class="clock"></div>
</div>

    <nav>
        <ul>
            <li class="menu"><a href="live-cameras.php">Live Cameras</a></li>
            <li class="menu"><a href="listfiles.php">Files</a></li>
            <li class="menu"><a href="settings.php">Settings</a></li>
            <li class="menu"><a href="wfeeds.php">Web-Feeds</a></li>
            <li class="menu"><a href="announce.php">Announcements</a></li>
            <li class="menu"><a href="connect.php">Connect</a></li>
            <li class="menu"><a href="backup.php">Backup</a></li>
            <li class="menu"><a href="about.php">About</a></li>
            <li class="menu"><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>
