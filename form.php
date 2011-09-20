<html>

<head>
	<link rel="Stylesheet" type="text/css" href="style/style.css">
	<title>BAM Manager</title>
</head>

<body>

	<div align="center" style="width: 700px; margin: auto;">
			<fieldset id="login_form">
				<legend>Log in</legend>
				<br>
				
		<h1>
			<?php
			if (isSet($_SESSION['notification']))
				echo $_SESSION['notification'];
			else
				echo "Enter your username and password:";
			?>
		</h1>
		
		<br><br>
		
		<form name="login_form" action="login.php" method="POST">
		<table style="width: 330px;">
		<tr>
		<td class="right150p"><p><b>Username</b></td><td><p><input style="width: 100%;" type="text" name="login"></p></td>
		</tr>
		<tr>
		<td class="right150p"><p><b>Password</b></p></td><td><p><input type="password" style="width: 100%;" name="password"></td>
		</tr>
		<tr>
		<td class="right150p"></td><td align="right"><p><br><input type="submit" value="Log in!"></p></td>
		</tr>
		</table>	
		</form>

		</fieldset>
	</div>
</body>

</html>