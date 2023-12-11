<html>
<!-- BEGIN menu.php INCLUDE -->
<?php include "./includes/menu.php"; ?>
<!-- END menu.php INCLUDE -->
<body>

<br />
<?php
$arrFiles = array();
$dirPath = "./VOD";


// Method 1: Using scandir()

$files = scandir($dirPath);
foreach ($files as $file) {
    $filePath = $dirPath . '/' . $file;
    if (is_file($filePath)) {
        echo $file . "<br>";
    }
}



?>
</body>
</html>
