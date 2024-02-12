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
		<?php
	// get name and variables from ini file.
	$ini_array = parse_ini_file("server.ini");
	$server = ($ini_array["server"]);
	echo '<div><h2>'.$server.'</h2></div>';
	// print_r($ini_array);
	?>
    <nav>
        <ul>
            <!-- <li class="menu"><a href="addcamera.php">Add Camera</a></li> -->
			<li class="menu"><a href="runningservices.php">Services</a></li>
            <li class="menu"><a href="cameras.php">cameras</a></li>
			<li class="menu"><a href="listfiles.php">Files</a></li>
        </ul>
    </nav>

	</header>