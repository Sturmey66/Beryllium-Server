<?php
// show error screen then go back to list the files.
echo "<h1><br><br><br>";
echo "Sorry, there was an error uploading your file.";
echo "</h1>";
echo "<form action=\"listfiles.php\">";
echo "<input type=\"submit\" value=\"Try Again\" />";
echo "</form>";

 ?>