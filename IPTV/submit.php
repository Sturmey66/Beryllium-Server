<?php
header( "refresh:5;url=showchannels.php" );

  $title=$_POST['var_title'];
  $logo=$_POST['var_logo'];
  $url=$_POST['var_url'];
  echo "<br><br>";
  echo "<br><br>";
  echo "The title is $title";
  echo "<br>";
  echo "The logo is $logo";
  echo "<br>";
  echo "The url is $url";
  echo "<br><br>";
  echo "<br><br>";
  chdir("/IPTV/scripts/");
  $command = "/IPTV/scripts/insert.sh $logo $title $url";
  echo $command . " - This is the command Im trying to run";
  $output = shell_exec($command . " 2>&1");
  echo "<pre>$output</pre>";
  //  2>&1 | tee -a /tmp/mylog 2>/dev/null >/dev/null &");
  // pause to finish write before continuing. just need a second.
  
  sleep(2);
  $title = null;
  $logo = null;
  unset($title);
  $url = null;
  unset($logo);
  unset($url);
  include 'includes/readxml.php';
 
 ?>
 
