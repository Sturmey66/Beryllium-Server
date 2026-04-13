<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Project base directory
$BASE_DIR = "/IPTV";

// Include core files
include $BASE_DIR . "/includes/menu.php";
require_once $BASE_DIR . "/includes/init.php";

// Handle restart time POST (optional, kept for compatibility)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restart-time'])) {
    $_SESSION['restart_time'] = $_POST['restart-time'];
}

// Handle uploaded files
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Restore server.ini
    if (isset($_FILES['upload_server']) && $_FILES['upload_server']['error'] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($_FILES['upload_server']['tmp_name'], $serverPath)) {
            chmod($serverPath, 0644);
            $messages[] = "server.ini restored successfully.";
        } else {
            $messages[] = "Failed to restore server.ini.";
        }
    }

    // Restore channels.xml
    if (isset($_FILES['upload_channels']) && $_FILES['upload_channels']['error'] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($_FILES['upload_channels']['tmp_name'], $channelsFile)) {
            chmod($channelsFile, 0644);
            $messages[] = "channels.xml restored successfully.";
        } else {
            $messages[] = "Failed to restore channels.xml.";
        }
    }

    // Restore wfeeds.xml
    if (isset($_FILES['upload_wfeeds']) && $_FILES['upload_wfeeds']['error'] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($_FILES['upload_wfeeds']['tmp_name'], $wfeedsPath)) {
            chmod($wfeedsPath, 0644);
            $messages[] = "wfeeds.xml restored successfully.";
        } else {
            $messages[] = "Failed to restore wfeeds.xml.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backup & Restore</title>
</head>
<body>
<h2>Backup & Restore</h2>

<?php if (!empty($messages)): ?>
    <?php foreach ($messages as $msg): ?>
        <p style="color: green;"><?= htmlspecialchars($msg) ?></p>
    <?php endforeach; ?>
<?php endif; ?>

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
