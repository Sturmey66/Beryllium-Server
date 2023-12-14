<html>
<!-- BEGIN menu.php INCLUDE -->
<?php include "./includes/menu.php"; ?>
<!-- END menu.php INCLUDE -->

<?php
echo "<h2>Channel List</h2>";
$xml=simplexml_load_file("channels.xml") or die("Error: Cannot create object");

 
foreach($xml as $entry) {
echo "ID: " . $entry->attributes()['id'] . "<br>";
echo "Title: " . $entry->title . "<br>";
echo "URL: " . $entry->url . "<br>";
echo "Logo: " . $entry->logo . "<br><br><br>";
}


?> 
</html>