﻿<?php$path_to_users_directory = 'users/';if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_selected_user'])) {	$user_to_remove = $_POST['user_name'];		if ($user_to_remove == "") {		$log_info = 'You apparently didn\'t choose any user.';		logToSyslog($log_info);		displayMessageFieldset('ERROR!', $log_info, '?tab=users');	}		else {		$user = $user_to_remove;		$user_to_remove = $path_to_users_directory . $user_to_remove . '.cl50cp1eoq9zj3scotij1a84';		unlink($user_to_remove);		$log_info = 'User "' . $user . '" has been removed.';		displayMessageFieldset('Success!', $log_info, '?tab=users');	}}else if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_new_user'])) {	$login = $_POST['login'];	$password = $_POST['password'];	$password2 = $_POST['password_confirm'];	$file = ("users/$login.cl50cp1eoq9zj3scotij1a84");		if(file_exists($file)) {		echo 'login' . $login . 'already exists';	}		else if($password != $password2) {		echo 'Passwords do not match!';	}		else {		$ciag = "$login:$password";		$zapis = str_replace("$ "," $",$ciag);		$open = fopen("$file", "w+");		fputs($open, $zapis);		fclose($open);	}	$legend = 'User '. $login .' has been created';	$log_info = 'User: '. $login .' <br>Password: '. $password .'<br>has been created.';	displayMessageFieldset($legend, $log_info, '?tab=users');}else {		echo '		<div>		<form name="form_existing_users" action="index.php?tab=users" method="POST">		<fieldset id="interior">			<legend>Existing users list</legend>		';			system("sudo lib/libcontentaction.pl --get-users");						$file_list = glob('/tmp/bammanager_tmp/*.cl50cp1eoq9zj3scotij1a84');			foreach ($file_list as $file) {				$file_name = clipPathToFileName($file, '.cl50cp1eoq9zj3scotij1a84');				$file_name = clipPathToFileName($file_name, '/tmp/bammanager_tmp/');				if ($file_name != 'admin') {					echo '<input type="radio" name="user_name" value="' . $file_name . '"> ' . $file_name . '<br>';				}			}						system('rm -r /tmp/bammanager_tmp');			logToSyslog("PHP: system('rm -r /tmp/bammanager_tmp')");		echo '		</fieldset>		<table align="right" style="overflow: auto; width: 100%">				<tr>					<td style="width: 100px;">					<p align="right">					<input style="margin-top: 1px; margin-right: 0px;" type="submit" name="remove_selected_user" value="Remove selected user">					</td>				</tr>			</table>				</form>		<br clear=all>		</div>		<form name="login_form" action="index.php?tab=users" method="POST">		<fieldset id="interior">			<legend>Create new user</legend>			<table style="width: 330px;">			<tr>				<td align="right"><p><b>Username</b></td><td><p><input style="width: 100%;" type="text" name="login" value=""></p></td>			</tr>			<tr>				<td class="right150p"><p><b>Password</b></td><td><p><input style="width: 100%;" type="password" name="password"></p></td>			</tr>			<tr>				<td class="right150p"><p><b>Confirm password</b></td><td><p><input style="width: 100%;" type="password" name="password_confirm"></p></td>			</tr>									</table>		</fieldset>			<table align="right" style="overflow: auto; width: 100%">			<tr>				<td style="width: 100px;">				<p align="right">				<input style="margin-top: 1px; margin-right: 0px;" type="submit" name="create_new_user" value="Create new user">				</td>			</tr>		</table>				</form>		<br><br>		';}?>