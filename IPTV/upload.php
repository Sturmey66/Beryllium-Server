<html>
<!-- BEGIN menu.php INCLUDE -->
<?php include "./includes/menu.php"; ?>
<!-- END menu.php INCLUDE -->
<body>

<br />
<p>Please choose and MP4 file to upload.</p>
<p>Only MP4 files are supported.</p>

            <form action="/upload2.php" method="post" enctype="multipart/form-data">
            <input type="file" name="fileToUpload" id="fileToUpload" />
            <br />
            <input type="submit" value="Upload File" name="submit" />
            </form>

</body>
</html>
