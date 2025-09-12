<?php 
include "./includes/auth.php"; // Session and login check
require_once __DIR__ . "/includes/functions.php";
?>
<?php
$vodDir = __DIR__ . "/LIVE/VOD";
$feedback = "";

// Ensure VOD directory exists
if (!is_dir($vodDir)) {
    mkdir($vodDir, 0777, true);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fileToUpload'])) {
    $file = $_FILES['fileToUpload'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'mp4') {
        $feedback .= "Only MP4 files are allowed.<br>";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $feedback .= "Upload error: " . $file['error'] . "<br>";
    } else {
        $targetPath = "$vodDir/" . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
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

// Get current VOD files
$files = array_diff(scandir($vodDir), ['.', '..']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VOD File Manager</title>
</head>
<body>
<?php include "./includes/menu.php"; ?>
<h2>Upload MP4 File</h2>
<p>Only MP4 files are supported.</p>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="fileToUpload" accept=".mp4" required />
    <br><br>
    <input type="submit" value="Upload File" />
</form>

<?php if (!empty($feedback)): ?>
    <p style="color: green;"><strong><?= $feedback ?></strong></p>
<?php endif; ?>

<h2>Available VOD Files</h2>
<?php if (!empty($files)): ?>
    <form method="post">
        <?php foreach ($files as $file): ?>
            <a href="VOD/<?= htmlspecialchars($file) ?>" download><?= htmlspecialchars($file) ?></a>
            <input type="submit" name="delete[<?= htmlspecialchars($file) ?>]" value="Delete" />
            <br><br>
        <?php endforeach; ?>
    </form>
<?php else: ?>
    <p>There are currently no files.</p>
<?php endif; ?>

</body>
</html>
