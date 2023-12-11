<pre>
<?php
 
// output xml file. <pre> preserves the whitespace. htmlspecialchars preserves the xml tags. 
  $file = file_get_contents(__DIR__ . '/../channels.xml');
  echo htmlspecialchars($file);
?>
</pre>