<?php 
include "./includes/auth.php"; // Session and login check
require_once __DIR__ . "/includes/functions.php";
?>
<?php
$settingsFile = __DIR__ . "/includes/server.ini";
$feedback = "";

// Save updated settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newContent = $_POST['iniContent'] ?? '';
    if (!empty($newContent)) {
        if (file_put_contents($settingsFile, $newContent) !== false) {
            $feedback = "Settings updated successfully.";
        } else {
            $feedback = "Failed to write to settings.ini.";
        }
    } else {
        $feedback = "Submitted content is empty. Nothing saved.";
    }
}

// Load current INI file content
$currentContent = file_exists($settingsFile) ? file_get_contents($settingsFile) : '';
$ini = parse_ini_file($settingsFile, true);
if ($ini === false) {
    $ini = [];
    $feedback = "Warning: Failed to parse settings.ini. File may be empty or malformed.";
}
?>
<?php
$hash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plainPassword'])) {
    $plainPassword = $_POST['plainPassword'];
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Settings</title>
</head>
<body>
<?php include "./includes/menu.php"; ?>
<h2>Settings Editor</h2>

<?php if (!empty($feedback)): ?>
    <p><strong><?= htmlspecialchars($feedback) ?></strong></p>
<?php endif; ?>

<form method="POST" action="set-theme.php">
    <label for="theme">Choose Theme:</label>
    <select name="theme" id="theme" onchange="this.form.submit()">
        <option value="theme-light" <?= ($_SESSION['theme'] ?? '') == 'theme-light' ? 'selected' : '' ?>>Light</option>
        <option value="theme-dark" <?= ($_SESSION['theme'] ?? '') == 'theme-dark' ? 'selected' : '' ?>>Dark</option>
        <option value="theme-blue" <?= ($_SESSION['theme'] ?? '') == 'theme-blue' ? 'selected' : '' ?>>Blue</option>
        <option value="theme-hotdog" <?= ($_SESSION['theme'] ?? '') == 'theme-hotdog' ? 'selected' : '' ?>>HotDog</option>
    </select>
</form>
<br>
<form method="post">
    <label for="plainPassword">Generate Password Hash:</label>
    <input type="text" name="plainPassword" required>
    <input type="submit" value="Hash Password">
</form>

<?php if ($hash): ?>
    <p><strong>Hash:</strong> <?= htmlspecialchars($hash) ?></p>
<?php endif; ?>
<br>
<form method="post">
    <textarea name="iniContent" rows="20" cols="100" style="font-family: monospace;"><?= htmlspecialchars($currentContent) ?></textarea>
    <br><br>
    <input type="submit" value="Save Settings">
</form>


</body>
</html>
