<html>
<!-- BEGIN menu.php INCLUDE -->
<?php include "./includes/menu.php"; ?>
<!-- END menu.php INCLUDE -->
<body>

<br><br>
<p>Please choose and MP4 file to upload.</p>
<p>Only MP4 files are supported.</p>

            <form action="/upload2.php" method="post" enctype="multipart/form-data">
            <input type="file" name="fileToUpload" id="fileToUpload" />
            <br />
            <input type="submit" value="Upload File" name="submit" />
            </form>
<br><br>
<?php
echo "<form method=\"post\"><input type=\"submit\" name=\"M3Ubuild\" value=\"M3U build\"></form>";

echo '<a href="http://localhost:8080/live/eden.m3u">http://localhost:8080/live/eden.m3u</a>';
echo "<br><br><br><br>";

$arrFiles = array();
$dirPath = "./VOD";
// Using scandir()
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
		echo '<a href="VOD/'.$file.'" download>'.$file.'</a><input type="submit" name="delete[' . $file . ']" value="delete" /><br><br>';
    }
    echo '</form>';
}
if(empty($files)){ //check again, since $files may be empty after deletion
    echo 'There are currently no files.';
}

if (isset($_POST['M3Ubuild'])) {
    // Execute the shell script
    $output = shell_exec("/IPTV/scripts/m3u-creation.sh");
    // echo $output;
	sleep(2);
    echo "M3U file has been regenerated";
	echo '<a href=http://localhost:8080/live/eden.m3u> http://localhost:8080/live/eden.m3u';
    exit();
}

?>
</body>
</html>
