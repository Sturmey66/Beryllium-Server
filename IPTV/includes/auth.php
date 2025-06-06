<?php
session_start();
if (!isset($_SESSION['authenticated']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: /login.php");
    exit;
}
?>
