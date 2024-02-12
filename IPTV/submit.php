<?php
header( "refresh:0;url=showchannels.php" );

  $name=$_POST['var_name'];
  $url=$_POST['var_url'];
  echo "<br><br>";
  echo "<br><br>";
  echo "The title is $name";
  echo "<br>";
  echo "The url is $url";
  echo "<br><br>";
  echo "<br><br>";
  chdir("/IPTV/scripts/");
  $command = "/IPTV/scripts/insert.sh $name $url";
  echo $command . " - This is the command Im trying to run";
  $output = shell_exec($command . " 2>&1");
  echo "<pre>$output</pre>";
  // pause to finish write before continuing. just need a second.
  sleep(1);
  // cleanup after run.
  $name = null;
  unset($name);
  $url = null;
  unset($url);

 ?>
 
