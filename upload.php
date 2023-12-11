<html>
<!-- BEGIN menu.php INCLUDE -->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="./includes/mycss.css" type="text/css">
    <header>
        <figure>
            
            <h1>SimpleIPTV Server</h1>
            <figcaption>
                <p>making IPTV broadcasting accessible</p>
            </figcaption>
        </figure>
    <nav>
        <ul>
            <li class="menu"><a href="runningservices.php">Running Services</a></li>
            <li class="menu"><a href="addstream.php">Add Stream</a></li>
            <li class="menu"><a href="upload.php">Upload File</a></li>
            <li class="menu"><a href="showchannels.php">Show Channels</a></li>
			<li class="menu"><a href="listfiles.php">List Files</a></li>
        </ul>
    </nav>
	</header><!-- END menu.php INCLUDE -->
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