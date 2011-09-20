<?php
/*
#===================================================================================
# BAM Manager (Bugzilla Automated Metrics Manager): change_password.php
#
# Copyright 2011, Comarch SA
# Maintainers: 	Krystian Jedrzejowski <krystian.jedrzejowski@comarch.com>,
# 				Kamil Marek <kamil.marek@comarch.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Jul 13 11:56:00 EET 2011
#===================================================================================
*/

session_start();
if(!isSet($_SESSION['logged in'])){
	$_SESSION['notification'] = "BAM Manager";
	include('form.php');
	exit();
}
echo '
<html>
<head>
	<link rel="Stylesheet" type="text/css" href="style/style.css">
	<title>Change password - BAM Manager</title>
</head>
<body>
';
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
	$login = $_SESSION['logged in'];
	$password_old = $_POST['password_old'];
	$password_new = $_POST['password_new'];
	$password_confirm = $_POST['password_confirm'];
	
	$path_to_file = ("users/$login.cl50cp1eoq9zj3scotij1a84");
	
	if(file_exists($path_to_file)) {	
		$file_content = fread(fopen($path_to_file, "r"), filesize($path_to_file));
		$pass = explode(":", $file_content);
		$passold = trim($pass[1]);
		if ($passold == $password_old) {
			if($password_new == $password_confirm) {
				if ($password_old != $password_confirm) {
					$ciag = "$login:$password_new";
					$zapis = str_replace("$ "," $",$ciag);
					$open = fopen("$path_to_file", "w+");
					fputs($open, $zapis);
					fclose($open);
					
					unset($_SESSION['logged in']);
					echo 'Your password has been changed. <br>Click <a class="notification-link" href="index.php">here</a> to log in again!';
					session_destroy();
				}
				
				else {
					$error = "New password cannot be the same as the old password";
				}
			}
			
			else {
				$error = "The passwords you typed do not match.";
			}
		}
		
		else {
			$error = "Wrong password!";
		}
	}
	
	if ($error != '') {
		echo '
		<div align="center" style="width: 700px; margin: auto;">
			<fieldset id="login_form">
				<legend>Error</legend>
				<br><br>
				<h2>ERROR: ' . $error . '</h2>
				<br>
				<h2><a class="notification-link" href="change_password.php">Try again!</a> or
				<a class="notification-link" href="index.php">return to BAM Manager</h2>
			</fieldset>
		</div>
		';
	}
}	

else {
	echo '
	<div align="center" style="width: 700px; margin: auto;">
		<fieldset id="login_form">
			<legend>Change password</legend>
		<br><br><br>
		<form name="login_form" action="change_password.php" method="POST">
			<table style="width: 330px;">
			<tr>
				<td align="right"><p><b>Username</b></td><td><p><input style="width: 100%;" disabled type="text" name="login" value="' .$_SESSION['logged in']. '"></p></td>
			</tr>
			<tr>
				<td class="right150p"><p><b>Current password</b></td><td><p><input style="width: 100%;" type="password" name="password_old"></p></td>
			</tr>
			<tr>
				<td class="right150p"><p><b>New password</b></td><td><p><input style="width: 100%;" type="password" name="password_new"></p></td>
			</tr>
			<tr>
				<td class="right150p"><p><b>Confirm new password</b></td><td><p><input style="width: 100%;" type="password" name="password_confirm"></p></td>
			</tr>
			<tr>
				<td class="right150p"></td><td align="right"><p><br><a class="" href="index.php">Cancel</a> <input style="margin-right: 0px;" type="submit" name="change_password" value="Save changes"></td>
			</tr>
			
			</table>
		</fieldset>		
		</form>
	';
}
echo '
	</div>
</body>
</html>
';
?>