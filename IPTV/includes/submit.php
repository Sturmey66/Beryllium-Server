<?php
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
  shell_exec(__DIR__ . "/../scripts/insert.sh $logo $title $url 2>&1 | tee -a /tmp/mylog 2>/dev/null >/dev/null &");
  // pause to finish write before continuing. just need a second.
  sleep(2);
  $title = null;
  $logo = null;
  $url = null;
  unset($title);
  unset($logo);
  unset($url);
  include 'readxml.php';
 ?>
 
