<?php 
include "./includes/auth.php"; // Session and login check
require_once __DIR__ . "/includes/functions.php";

$serverPath = __DIR__ . "/includes/server.ini";
$channelsPath = __DIR__ . "/channels.xml";
$wfeedsPath = __DIR__ . "/wfeeds.xml";

// Handle uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['upload_server']) && $_FILES['upload_server']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['upload_server']['tmp_name'], $channelsPath);
        $message = "server.ini restored successfully.";
    }
    if (isset($_FILES['upload_channels']) && $_FILES['upload_channels']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['upload_channels']['tmp_name'], $channelsPath);
        $message = "channels.xml restored successfully.";
    }
    if (isset($_FILES['upload_wfeeds']) && $_FILES['upload_wfeeds']['error'] === UPLOAD_ERR_OK) {
        move_uploaded_file($_FILES['upload_wfeeds']['tmp_name'], $wfeedsPath);
        $message = "wfeeds.xml restored successfully.";
    }
}

?>
<?php
$settingsFile = __DIR__ . "/includes/server.ini";
$serverIni = parse_ini_file($settingsFile);
$serverIP = $serverIni['serverIP'] ?? '127.0.0.1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backup & Restore</title>
</head>
<body>
<?php include "./includes/menu.php"; ?>
<h2>Backup & Restore</h2>

<?php if (!empty($message)) echo "<p style='color: green;'>$message</p>"; ?>

<h3>Download Config Files</h3>
<ul>
    <li><a href="/includes/server.ini" download>Download server.ini</a></li>
    <li><a href="/channels.xml" download>Download channels.xml</a></li>
    <li><a href="/wfeeds.xml" download>Download wfeeds.xml</a></li>
</ul>

<h3>Restore Config Files</h3>
<form action="backup.php" method="post" enctype="multipart/form-data">
    <label for="upload_server">Upload server.ini:</label><br>
    <input type="file" name="upload_server" id="upload_server" accept=".ini"><br><br>

    <label for="upload_channels">Upload channels.xml:</label><br>
    <input type="file" name="upload_channels" id="upload_channels" accept=".xml"><br><br>

    <label for="upload_wfeeds">Upload wfeeds.xml:</label><br>
    <input type="file" name="upload_wfeeds" id="upload_wfeeds" accept=".xml"><br><br>

    <button type="submit">Upload Files</button>
</form>

</body>
</html>
