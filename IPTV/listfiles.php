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

$files = array_diff(scandir($dirPath), array('.', '..'));

if(!empty($files)){
    echo '<form action="" method="post">';
    foreach($files as $file){
        if(isset($_POST['delete'][$file])){
			echo $file;
            unlink('VOD/' . $file);
			echo "file was deleted";
            continue;
        }
//        echo '<a href="VOD/'.$file.'" download>'.$file.'</a><input type="submit" name="delete[VOD/'.$file. ']" value="Delete this file" /><br>';
		echo '<a href="VOD/'.$file.'" download>'.$file.'</a><input type="submit" name="delete[' . $file . ']" value="delete" /><br><br><br>';
    }
    echo '</form>';
}
if(empty($files)){ //check again, since $files may be empty after deletion
    echo 'There are currently no files.';
}


?>
</body>
</html>
