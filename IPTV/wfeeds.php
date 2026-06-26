<?php 
include "./includes/auth.php"; // Session and login check
require_once __DIR__ . "/includes/functions.php";
?>
<?php
$xmlFile = __DIR__ . "/wfeeds.xml";

// Create empty XML if it doesn't exist or is empty
if (!file_exists($xmlFile) || filesize($xmlFile) === 0) {
    $empty = new SimpleXMLElement('<wfeeds></wfeeds>');
    $empty->asXML($xmlFile);
}

$xml = simplexml_load_file($xmlFile);
$feeds = [];
foreach ($xml->feed as $f) {
    $feeds[] = [
        'tv-logo' => (string)$f->{'tv-logo'},
        'group-title' => (string)$f->{'group-title'},
        'name' => (string)$f->name,
        'url' => (string)$f->url
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $feed = $xml->addChild('feed');
        $feed->addChild('tv-logo', $_POST['tv-logo']);
        $feed->addChild('group-title', $_POST['group-title']);
        $feed->addChild('name', $_POST['name']);
        $feed->addChild('url', $_POST['url']);
        $xml->asXML($xmlFile);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['delete'])) {
        $index = (int)$_POST['index'];
        unset($feeds[$index]);
    }

    if (isset($_POST['move_up'])) {
        $index = (int)$_POST['index'];
        if ($index > 0 && $index < count($feeds)) {
            $temp = $feeds[$index];
            $feeds[$index] = $feeds[$index - 1];
            $feeds[$index - 1] = $temp;
        }
    }

    if (isset($_POST['move_down'])) {
        $index = (int)$_POST['index'];
        if ($index < count($feeds) - 1) {
            $temp = $feeds[$index];
            $feeds[$index] = $feeds[$index + 1];
            $feeds[$index + 1] = $temp;
        }
    }

    // Rewrite the file after delete or reorder
    if (isset($_POST['delete']) || isset($_POST['move_up']) || isset($_POST['move_down'])) {
        $xml = new SimpleXMLElement('<wfeeds></wfeeds>');
        foreach ($feeds as $feed) {
            $entry = $xml->addChild('feed');
            $entry->addChild('tv-logo', $feed['tv-logo']);
            $entry->addChild('group-title', $feed['group-title']);
            $entry->addChild('name', $feed['name']);
            $entry->addChild('url', $feed['url']);
        }
        $xml->asXML($xmlFile);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>WFeeds Manager</title>
</head>
<body>
<?php include "./includes/menu.php"; ?>
<h2>Manage WFeeds</h2>
<form method="post">
    <input type="text" name="tv-logo" placeholder="tv-logo" required>
    <input type="text" name="group-title" placeholder="group-title" required>
    <input type="text" name="name" placeholder="name" required>
    <input type="text" name="url" placeholder="url" required>
    <input type="submit" name="add" value="Add">
</form>
<hr>
<h3>Current Feeds</h3>
<table border="1" cellpadding="6" cellspacing="0">
    <tr>
        <th>Name</th>
        <th>Logo</th>
        <th>Group</th>
        <th>URL</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($feeds as $i => $feed): ?>
        <tr>
            <td><?= htmlspecialchars($feed['name']) ?></td>
            <td><?= htmlspecialchars($feed['tv-logo']) ?></td>
            <td><?= htmlspecialchars($feed['group-title']) ?></td>
            <td><?= htmlspecialchars($feed['url']) ?></td>
            <td>
                <form method="post" class="inline">
                    <input type="hidden" name="index" value="<?= $i ?>">
                    <input type="submit" name="move_up" value="↑">
                    <input type="submit" name="move_down" value="↓">
                    <input type="submit" name="delete" value="Delete">
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
