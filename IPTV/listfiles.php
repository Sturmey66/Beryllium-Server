<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Project base directory
$BASE_DIR = "/IPTV";
$vodDir = "/LIVE/VOD"; // Absolute path in container

// Include core files
include $BASE_DIR . "/includes/menu.php";       // menu must come first
require_once $BASE_DIR . "/includes/init.php";

// Load server config
$ini_array = parse_ini_file("/IPTV/includes/server.ini", true);
$serverIP   = $ini_array['first_section']['serverIP'] ?? '127.0.0.1';
$serverPort = $ini_array['first_section']['port'] ?? '8085';
$publicBase = "http://$serverIP:$serverPort/VOD";

// Initialize feedback
$feedback = "";

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $file = $_FILES['fileToUpload'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['mp4', 'jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowed)) {
        $feedback .= "Only MP4, JPG, and PNG files are allowed.<br>";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $feedback .= "Upload error: " . $file['error'] . "<br>";
    } else {
        $targetPath = "$vodDir/" . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $size = getimagesize($targetPath);
                if ($size && ($size[0] > 400 || $size[1] > 400)) {
                    $feedback .= "Warning: Image is larger than 400x400 pixels.<br>";
                }
            }
            $feedback .= "Uploaded file: " . htmlspecialchars($file['name']) . "<br>";
        } else {
            $feedback .= "Failed to move uploaded file.<br>";
        }
    }
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $file => $value) {
        $safeFile = basename($file);
        $targetPath = "$vodDir/$safeFile";
        if (file_exists($targetPath)) {
            unlink($targetPath);
            $feedback .= "Deleted file: $safeFile<br>";
        } else {
            $feedback .= "File not found: $safeFile<br>";
        }
    }
}

// Get current files
$allFiles = array_diff(scandir($vodDir), ['.', '..']);
$mp4Files = [];
$imgFiles = [];

foreach ($allFiles as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'mp4') {
        $mp4Files[] = $file;
    } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $imgFiles[] = $file;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VOD File Manager</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 2px solid black; }
        th, td { padding: 8px; vertical-align: top; }
        th { background-color: #f2f2f2; }
        a { text-decoration: none; }
        img.thumbnail { width: 100px; height: 100px; object-fit: contain; display: block; margin-bottom: 4px; }
    </style>
</head>
<body>
<h2>Upload File</h2>
<p>Supported formats: MP4, JPG, PNG. Images should be 400x400 pixels or smaller.</p>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="fileToUpload" accept=".mp4,.jpg,.jpeg,.png" required />
    <br><br>
    <input type="submit" value="Upload File" />
</form>

<?php if (!empty($feedback)): ?>
    <p style="color: green;"><strong><?= $feedback ?></strong></p>
<?php endif; ?>

<h2>Available Files</h2>
<form method="post">
    <table>
        <tr>
            <th>MP4 Files</th>
            <th>LOGOS</th>
        </tr>
        <tr>
            <td>
                <?php if (!empty($mp4Files)): ?>
                    <?php foreach ($mp4Files as $file): ?>
                        <a href="<?= $publicBase ?>/<?= htmlspecialchars($file) ?>" download><?= htmlspecialchars($file) ?></a>
                        <input type="submit" name="delete[<?= htmlspecialchars($file) ?>]" value="Delete" /><br><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    No MP4 files available.
                <?php endif; ?>
            </td>
            <td>
                <?php if (!empty($imgFiles)): ?>
                    <?php foreach ($imgFiles as $file): ?>
                        <img class="thumbnail" src="<?= $publicBase ?>/<?= htmlspecialchars($file) ?>" alt="<?= htmlspecialchars($file) ?>">
                        <a href="<?= $publicBase ?>/<?= htmlspecialchars($file) ?>" download><?= htmlspecialchars($file) ?></a>
                        <input type="submit" name="delete[<?= htmlspecialchars($file) ?>]" value="Delete" /><br><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    No LOGO files available.
                <?php endif; ?>
            </td>
        </tr>
    </table>
</form>

</body>
</html>
