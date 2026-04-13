<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$ini_array = parse_ini_file(__DIR__ . "/server.ini", true);
$server = $ini_array["server"] ?? "IPTV Server";

if (!isset($_SESSION['theme']) && isset($_COOKIE['theme'])) {
    $_SESSION['theme'] = $_COOKIE['theme'];
}
$theme = $_SESSION['theme'] ?? 'theme-light';
?>

    <link rel="stylesheet" href="./includes/css/<?= htmlspecialchars($theme) ?>.css">

<header>
    <figure>
        <h1>Beryllium Server</h1>
        <figcaption>
            <p>making IPTV broadcasting accessible</p>
        </figcaption>
    </figure>

    <div class="header-bar">
        <h2><?= htmlspecialchars($server) ?></h2>
        <div id="system-time" class="clock"></div>
    </div>

    <nav id="menu">
        <span class="nav-toggle">&#9776;</span> <!-- hamburger -->
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

<script>
// Update system time
function updateTime() {
    const now = new Date();
    const formatted = now.toISOString().slice(0, 16).replace('T', ' ');
    document.getElementById('system-time').textContent = 'System Time: ' + formatted;
}
setInterval(updateTime, 1000);
updateTime();

</script>
