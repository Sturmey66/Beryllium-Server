<html>
<!-- BEGIN menu.php INCLUDE -->
<?php include "./includes/menu.php"; ?>
<!-- END menu.php INCLUDE -->
<body>
  <form action="submit.php" method="post">
  Please enter the information for the channel you want to add.
  <table>
    <tr><td>Camera:</td><td> <input name="var_camera"/> </td></tr>
  </table>
  <input type="submit" name="my_form_submit_button" 
           value="submit"/>
    </form>
</body>
</html>