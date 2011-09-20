<?php

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_common_parameters_file'])) {
	$common_parameters_file_content = $_POST['common_parameters_file_content'];
	system("sudo lib/libcontentaction.pl --start $login 'change_common_parameters_file'");
	
	$temp_file = 'tmp/' . rand();
	saveToFileArray($temp_file, stripslashes($common_parameters_file_content));
	system("sudo lib/libcontentaction.pl --move $temp_file $common_parameters_file");
	
	system("sudo lib/libcontentaction.pl --end $login 'change_common_parameters_file'");
	
	displayMessageFieldset('Success!', 'Common parameters file "' . $common_parameters_file . '" has been changed.', '?tab=common_parameters');
}

else {		
	if ($common_parameters_file) {
		if (isset($_GET['sub']) && $_GET['sub'] == 'edit') {
			$common_parameters_file_content = fread(fopen($common_parameters_file, "r"), filesize($common_parameters_file));
			
			echo '
			<fieldset id="interior">
			<legend>Edit: ' . $common_parameters_file . '</legend>
			<div>
			<form name="form_new_list_of_products" action="index.php?tab=common_parameters" method="POST">
				<textarea rows="20" cols="100" style="width: 100%; height: 80%;" name="common_parameters_file_content">' . $common_parameters_file_content . '</textarea><br>
				</fieldset>
				<table align="right" style="overflow: auto; width: 100%">
				<tr>
					<td style="width: 100px;">
					<p align="right">
					<input type="button" OnClick="window.location=\'index.php?tab=common_parameters\'" value="Dismiss changes">
					<input style="margin-top: 1px; margin-right: 0px;" type="submit" name="change_common_parameters_file" value="Save changes">
					</td>
				</tr>
			</table>
			</form>
			<br><br>
			</div>';
		}
		
		else {
			echo '
			<fieldset id="interior">
			<legend>Common parameters file: ' . $common_parameters_file . '</legend>
			<div>';
			$common_parameters_file_content = fread(fopen($common_parameters_file, "r"), filesize($common_parameters_file));
			$common_parameters_file_content = explode("\n",$common_parameters_file_content);
			
			echo '		
				<p class="syslog">'; 
			displayArray($common_parameters_file_content);
			echo '</p>
				</div>
				</fieldset>';
			echo '
				<table align="right" style="overflow: auto; width: 100%">
				<tr>
					<td style="width: 100px;">
					<p align="right">
					<input type="button" OnClick="window.location=\'index.php?tab=common_parameters&sub=edit\'" value="Edit common parameters file">
				</td>
				</tr>
			</table>
			<br><br>';
		}
	}
	
	else {
		$body = 'Common parameters file "' . $common_parameters_file . '" is not specified. <br>Path to Common parameters file can be added in <a href="?tab=settings">settings tab';
		displayMessageFieldset('Common parameters file is not specified!', $body, '?tab=common_parameters');
	}
}
?>