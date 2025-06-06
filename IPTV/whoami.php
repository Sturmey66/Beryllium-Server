<?php
echo 'User: ' . get_current_user() . "<br>";
echo 'Effective User ID: ' . posix_geteuid() . "<br>";
echo 'Running as user: ' . exec('whoami') . "<br>";
?>
