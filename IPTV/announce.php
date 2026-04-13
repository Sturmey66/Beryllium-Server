<?php
declare(strict_types=1);

$BASE_DIR = '/IPTV';

/* ---- CORE ---- */
require_once $BASE_DIR . '/includes/init.php';
require_once $BASE_DIR . '/includes/functions.php';

/* ---- VERIFY ---- */
if (!function_exists('build_ini_string')) {
    die('FATAL: build_ini_string() not loaded');
}

/* ---- UI LAST ---- */
include $BASE_DIR . '/includes/menu.php';

// ---------------- PATHS ----------------
$ANNOUNCE_NAME   = 'announce';
$ANNOUNCE_DIR    = "$BASE_DIR/LIVE/announce";
$TMP_DIR         = "$ANNOUNCE_DIR/tmp";
$ANNOUNCE_TEXT   = "$BASE_DIR/includes/announce.txt";
$FONT_FILE       = "$BASE_DIR/includes/font/DejaVuSansMono.ttf";
$SUPERVISOR_CONF = "$BASE_DIR/supervisor/announce.conf";
$ROTATOR_SCRIPT  = "$BASE_DIR/announce-rotator.php";

@mkdir($ANNOUNCE_DIR, 0777, true);
@mkdir($TMP_DIR, 0777, true);

// ---------------- LOAD CONFIG ----------------
$serverPath = "$BASE_DIR/includes/server.ini";
$ini = parse_ini_file($serverPath, true, INI_SCANNER_TYPED);
$announceCfg = $ini['announce'] ?? [];
$theme = $ini['theme'] ?? 'default';

$serverIP   = $ini['server']['ip'] ?? '127.0.0.1';
$serverPort = (int)($ini['server']['port'] ?? 8080);

$bg        = $announceCfg['background'] ?? '#000000';
$fg        = $announceCfg['foreground'] ?? '#ffffff';
$pagespeed = isset($announceCfg['pagespeed']) ? (int)$announceCfg['pagespeed'] : 10;
$enabled   = !empty($announceCfg['enabled']) && $announceCfg['enabled'] === 'true';

$feedback = "";

// ---------------- HANDLE POST ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Enable/Disable toggle
    if (isset($_POST['toggleEnable'])) {
        $enabled = $_POST['enabled'] === 'true';

        if ($enabled) {
            addAnnounceChannelToChannelsXml(
                $ANNOUNCE_NAME,
                $serverIP,
                $serverPort
            );
            $feedback = "Announce channel enabled and added to Live Cameras.";
        } else {
            removeAnnounceChannelFromChannelsXml();
            $feedback = "Announce channel disabled and removed from Live Cameras.";
        }

        // Update server.ini
        $ini['announce']['enabled'] = $enabled ? 'true' : 'false';
        file_put_contents($serverPath, build_ini_string($ini));

        // Update Supervisor service immediately
        createAnnounceSupervisorService(
            $ANNOUNCE_NAME,
            $serverIP,
            $serverPort,
            $SUPERVISOR_CONF,
            $ANNOUNCE_DIR,
            $TMP_DIR,
            $FONT_FILE,
            $ROTATOR_SCRIPT
        );
    }

    // Save announce text + colors/pagespeed
    if (isset($_POST['save'])) {
        $text = $_POST['announce'] ?? '';
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = rtrim($text, "\n");

        $postedBg = $_POST['background'] ?? $bg;
        $postedFg = $_POST['foreground'] ?? $fg;
        $postedSpeed = max(1, (int)($_POST['pagespeed'] ?? $pagespeed));

        if (file_put_contents($ANNOUNCE_TEXT, $text) === false) {
            $feedback = "Failed to save announce text.";
        } else {
            $ini['announce']['background'] = $postedBg;
            $ini['announce']['foreground'] = $postedFg;
            $ini['announce']['pagespeed']  = $postedSpeed;
            file_put_contents($serverPath, build_ini_string($ini));
            $feedback = "Announce text saved.";

            $bg = $postedBg;
            $fg = $postedFg;
            $pagespeed = $postedSpeed;
        }

        if ($enabled) {
            createAnnounceSupervisorService(
                $ANNOUNCE_NAME,
                $serverIP,
                $serverPort,
                $SUPERVISOR_CONF,
                $ANNOUNCE_DIR,
                $TMP_DIR,
                $FONT_FILE,
                $ROTATOR_SCRIPT
            );
        }
    }
}

// ---------------- LOAD ANNOUNCE TEXT ----------------
$textContent = is_file($ANNOUNCE_TEXT) ? file_get_contents($ANNOUNCE_TEXT) : "";
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Announce Channel</title>
<link rel="stylesheet" href="includes/css/<?= htmlspecialchars($theme) ?>.css">
<style>
textarea { font-family: monospace; width: 37ch; height: 420px; }
.feedback { color: #0a0; font-weight: bold; margin-top: 10px; }
.form-row { margin-bottom: 10px; }
.inline { display:inline-block; width: 180px; }
</style>
</head>
<body>
<h1>Announce Channel</h1>

<form method="POST">
    <div class="form-row">
        <label class="inline">Enable Announce Channel:</label>
        <select name="enabled" onchange="this.form.submit()">
            <option value="true" <?= $enabled ? 'selected' : '' ?>>Enable</option>
            <option value="false" <?= !$enabled ? 'selected' : '' ?>>Disable</option>
        </select>
        <input type="hidden" name="toggleEnable">
    </div>
</form>

<form method="POST">
    <div class="form-row">
        <label class="inline">Background Color:</label>
        <input type="color" name="background" value="<?= htmlspecialchars($bg) ?>">
    </div>

    <div class="form-row">
        <label class="inline">Text Color:</label>
        <input type="color" name="foreground" value="<?= htmlspecialchars($fg) ?>">
    </div>

    <div class="form-row">
        <label class="inline">Page Display Time (seconds, min 1):</label>
        <input type="number" name="pagespeed" value="<?= (int)$pagespeed ?>" min="1">
    </div>

    <div class="form-row">
        <label>Announce Text (separate pages using [===]):</label><br>
        <textarea id="announceText" name="announce"><?= htmlspecialchars($textContent) ?></textarea>
    </div>

    <div class="form-row">
        <button type="submit" name="save">Save</button>
    </div>
</form>

<?php if (!empty($feedback)): ?>
<p class="feedback"><?= htmlspecialchars($feedback) ?></p>
<?php endif; ?>

</body>
</html>
