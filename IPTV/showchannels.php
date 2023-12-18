<html>
<!-- BEGIN menu.php INCLUDE -->
<?php include "./includes/menu.php"; ?>
<!-- END menu.php INCLUDE -->

<?php
echo "<h2>Channel List</h2>";
$xml=simplexml_load_file("channels.xml") or die("Error: Cannot create object");

 
foreach($xml as $entry) {
$cid = $entry->attributes()['id'];
echo "ID: " . $cid . "<br>";
echo "Title: " . $entry->title . "<br>";
echo "URL: " . $entry->url . "<br>";
echo "Logo: " . $entry->logo ;
echo "&nbsp<form method=\"post\"><input type=\"hidden\" name=\"id\" value=" . $cid . "><input type=\"submit\" name=\"deleteButton\" value=\"Delete Channel\"></form>";
echo "<br><br>";
}





// Check if the form is submitted and the delete button is clicked
if (isset($_POST['deleteButton'])) {
    // Get the ID from the form submission
    $idToDelete = $_POST['id'];

    // Execute the shell script with the provided ID
    $output = shell_exec("/IPTV/scripts/delete.sh $idToDelete");
    echo $output;
	sleep(4);
    echo "<script>";
    echo "location.replace(\"showchannels.php\")";
    echo "</script>";
    exit();
}
?>

</html>

