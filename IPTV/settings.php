<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Define base directory
$BASE_DIR = "/IPTV";

// Start session (menu.php also calls this, so you may skip if menu.php already does)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// File paths
$settingsFile = $BASE_DIR . "/includes/server.ini";
$feedback = "";
$hash = "";

// =======================
// FORM HANDLING - MUST BE BEFORE ANY HTML OUTPUT
// =======================

// Theme change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['theme'])) {
    $_SESSION['theme'] = $_POST['theme'];
    $feedback = "Theme changed to " . htmlspecialchars($_POST['theme']);
}

// Password hash
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hashPassword'])) {
    $plainPassword = $_POST['plainPassword'] ?? '';
    if (!empty($plainPassword)) {
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    } else {
        $feedback = "Please enter a password to hash.";
    }
}

// Settings save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['saveSettings'])) {
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

// Load current content
$currentContent = file_exists($settingsFile) ? file_get_contents($settingsFile) : '';
$ini = parse_ini_file($settingsFile, true);
if ($ini === false) {
    $ini = [];
    $feedback = "Warning: Failed to parse settings.ini.";
}

// =======================
// HTML STARTS HERE
// =======================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Settings</title>
</head>
<body>
<?php include $BASE_DIR . "/includes/menu.php"; ?>

<h2>Settings Editor</h2>

<?php if (!empty($feedback)): ?>
    <p><strong><?= htmlspecialchars($feedback) ?></strong></p>
<?php endif; ?>

<!-- Theme -->
<form method="post">
    <label for="theme">Choose Theme:</label>
    <select name="theme" id="theme" onchange="this.form.submit()">
        <option value="theme-light" <?= ($_SESSION['theme'] ?? '') === 'theme-light' ? 'selected' : '' ?>>Light</option>
        <option value="theme-dark" <?= ($_SESSION['theme'] ?? '') === 'theme-dark' ? 'selected' : '' ?>>Dark</option>
        <option value="theme-blue" <?= ($_SESSION['theme'] ?? '') === 'theme-blue' ? 'selected' : '' ?>>Blue</option>
        <option value="theme-hotdog" <?= ($_SESSION['theme'] ?? '') === 'theme-hotdog' ? 'selected' : '' ?>>HotDog</option>
    </select>
</form>

<br>

<!-- Password hash -->
<form method="post">
    <label for="plainPassword">Generate Password Hash:</label>
    <input type="text" name="plainPassword" required>
    <input type="submit" name="hashPassword" value="Hash Password">
</form>

<?php if (!empty($hash)): ?>
    <p><strong>Hash:</strong> <?= htmlspecialchars($hash) ?></p>
<?php endif; ?>

<br>

<!-- Settings editor -->
<form method="post">
    <textarea name="iniContent" rows="20" cols="100" style="font-family: monospace;"><?= htmlspecialchars($currentContent) ?></textarea>
    <br><br>
    <input type="submit" name="saveSettings" value="Save Settings">
</form>

</body>
</html>
