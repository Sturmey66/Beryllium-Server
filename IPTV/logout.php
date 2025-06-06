<?php
session_start();
session_destroy();
header("Location: live-cameras.php");
exit;
?>
