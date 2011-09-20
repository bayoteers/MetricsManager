<?php
session_start();
 if(!isSet($_SESSION['logged in'])) {
 $notification = "You haven't been logged in! <br>Click <a class=\"notification-link\" href=\"index.php\">here</a> to log in!";
}

 else{
 unset($_SESSION['logged in']);
 $notification = 'You have been logged out. <br>Click <a class="notification-link" href="index.php">here</a> to log in again!';
}

session_destroy();
?>

<html>

<head>
	<link rel="Stylesheet" type="text/css" href="style/style.css">	
	<title>BAM Manager</title>
</head>

<body>
	<div align="center" style="width: 700px; margin: auto;">
		<fieldset id="login_form">
			<legend>BAM Manager</legend>
			<br><br><br><br>
			<h2><?php echo $notification ?></h2>
		</fieldset>
	</div>
</body>
</html>