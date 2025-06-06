<?php
session_start();

if (isset($_POST['theme'])) {
	$theme = $_POST['theme'];
    $_SESSION['theme'] = $_POST['theme'];
    setcookie('theme', $theme, time() + (365 * 24 * 60 * 60), "/"); // 1 year
}

// Optional: go back to the page that sent the form
$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $redirect");
exit;
