<?php

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_settings'])) {
	$common_parameters_file = $_POST['common_parameters_file'];
	$statistics_user = $_POST['statistics_user'];
	$crontab_file = $_POST['crontab_file'];
	$fetch_statistics_file = $_POST['fetch_statistics_file'];
	$fetch_statistics_from_bugzilla_file = $_POST['fetch_statistics_from_bugzilla_file'];
	$path_to_products_directory = $_POST['path_to_products_directory'];
	$path_to_statistics_directory = $_POST['path_to_statistics_directory'];
		
	$common_parameters_file_check = file_exists($common_parameters_file);
	$fetch_statistics_file_check = file_exists($fetch_statistics_file);
	$fetch_statistics_from_bugzilla_file_check = file_exists($fetch_statistics_from_bugzilla_file);
	
	if  ($common_parameters_file_check && $fetch_statistics_file_check && $fetch_statistics_from_bugzilla_file_check) {
		$content = '
<?php
$common_parameters_file="' . $common_parameters_file . '";
$statistics_user="' . $statistics_user . '";
$crontab_file="' . $crontab_file . '";
$fetch_statistics_file="' . $fetch_statistics_file . '";
$fetch_statistics_from_bugzilla_file="' . $fetch_statistics_from_bugzilla_file . '";
$path_to_products_directory="' . $path_to_products_directory . '";
$path_to_statistics_directory="' . $path_to_statistics_directory . '";
?>
		';			
		$user_settings_file = 'lib/user_settings.php';
		saveToFile($user_settings_file, $content);		
		displayMessageFieldset('Success!', 'Chages have been saved.', '?tab=settings');
	}
	
	elseif ($common_parameters_file == '') {
		$body = 'Please specify common parameters file!';
		displayMessageFieldset('ERROR!', $body, '?tab=settings');
	}
	
	else {
		$body = 'File "' . $common_parameters_file . '" does not exist.';
		displayMessageFieldset('ERROR!', $body, '?tab=settings');
	}
}
elseif($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_crontab_file'])) {
	$crontab_file_content = $_POST['crontab_file_content'];
	system("sudo lib/libcontentaction.pl --start $login 'Settings: change_crontab_file'");
	$temp_file = 'tmp/' . rand();
	
	saveToFileArray($temp_file, stripslashes($crontab_file_content));
	system("sudo lib/libcontentaction.pl --move $temp_file $crontab_file");
	system("sudo lib/libcontentaction.pl --end $login 'Settings:change_crontab_file'");
	
	displayMessageFieldset('Success!', 'Crontab file "' . $crontab_file . '" has been changed.', '?tab=settings');
}

else {
	$user_settings = 'lib/user_settings.php';
	
	echo '
	<div>
		<form name="BAM_settings" action="index.php?tab=settings" method="POST">
		<fieldset id="interior">
		<legend>BAM Manager settings</legend>
		<table style="width: 80%;">
			<tr>
				<td class="right" style="width: 240px;"><p>Location of \'Common parameters\' file:</p></td>
				<td class="center"><input disabled type="text" size="80" name="common_parameters_file" value=' . $common_parameters_file . '></td>
				<td></td>
			</tr>
			<tr>
				<td class="right"><p>Location of \'crontab\' file:</p></td>
				<td class="center"><input disabled type="text" size="80" name="crontab_file" value=' . $crontab_file . '></td>
				<td></td>
			</tr>
			<tr>
				<td class="right"><p>Location of \'fetch_statistics_from_bugzilla.pl\' file:</p></td>
				<td class="center"><input disabled type="text" size="80" name="fetch_statistics_from_bugzilla_file" value=' . $fetch_statistics_from_bugzilla_file . '></td>
				<td></td>
			</tr>
			<tr>
				<td class="right"><p>Location of \'fetch_statistics.sh\' file:</p></td>
				<td class="center"><input disabled type="text" size="80" name="fetch_statistics_file" value=' . $fetch_statistics_file . '></td>
				<td></td>
			</tr>
			<tr>
				<td class="right"><p>Path to \'statistics\' directory:</p></td>
				<td class="center"><input disabled type="text" size="80" name="path_to_statistics_directory" value=' . $path_to_statistics_directory . '></td>
				<td></td>
			</tr>
			<tr>
				<td class="right"><p>Path to \'products\' directory:</p></td>
				<td class="center"><input disabled type="text" size="80" name="path_to_products_directory" value=' . $path_to_products_directory . '></td>
				<td></td>
			</tr>
			<tr>
				<td class="right"><p>Name of statistics user:</p></td>
				<td class="center"><input disabled type="text" size="80" name="statistics_user" value=' . $statistics_user . '></td>
				<td></td>
			</tr>
		</table>
		</fieldset>
		<table align="right" style="overflow: auto; width: 100%">
			<tr>
				<td style="width: 100px;">
				<p align="right">
				<input style="margin-top: 1px; margin-right: 0px;" type="button" name="enable" value="Edit settings" OnClick="enableSettings(true)">
				<input style="display: none; margin-top: 1px; margin-right: 0px;" type="button" name="disable" value="Dismiss changes" OnClick="window.location=\'index.php?tab=settings\'">
				<input style="display: none; margin-top: 1px; margin-right: 0px;" type="submit" name="edit_settings" value="Save changes">
				</td>
			</tr>
		</table>
		</form>
	</div>
	';
	
	if (isset($_GET['sub']) && $_GET['sub'] == 'edit') {
		echo '
		<script type="text/javascript">
		document.BAM_settings.enable.disabled = true;
		</script>
		
		<div style="overflow: auto; width: 100%">
			<form name="cron_settings" action="index.php?tab=settings" method="POST">
			<fieldset id="interior">
			<legend>Edit: BAM Schedule - ' . $crontab_file . '</legend>
				<div>
				';
				$crontab_file_content = fread(fopen($crontab_file, "rw"), filesize($crontab_file));
				
				echo '		
				<textarea rows="20" cols="100" style="width: 100%; height: 30%;" name="crontab_file_content">' . $crontab_file_content . '</textarea><br>
				</div>
			</fieldset>
					';
				echo '
			<table align="right" style="overflow: auto; width: 100%">
				<tr>
					<td style="width: 100px;">
					<p align="right">
					<input type="button" OnClick="window.location=\'index.php?tab=settings\'" value="Dismiss changes">
					<input type="submit" name="change_crontab_file" value="Save changes">
					</td>
				</tr>
			</table>
			</form>
		</div>
		';
	}
	
	else {
		echo '
			<fieldset id="interior">
			<legend>BAM Schedule</legend>
				<div>';
				$file_check = file_exists($crontab_file);
				if  ($file_check) {
					$crontab_file_content = fread(fopen($crontab_file, "rw"), filesize($crontab_file));
					$crontab_file_content = explode("\n",$crontab_file_content);
					echo '		
					<p class="syslog">'; 
					displayArray($crontab_file_content);
					echo '
					</p>
					</div>
				</fieldset>
					';
					echo '
				<table align="right" style="overflow: auto; width: 100%">
					<tr>
						<td style="width: 100px;">
						<p align="right">
						<input disabled style="display: none; margin-top: 1px; margin-right: 0px;" type="button" id="edit_cron_disabled" value="Edit crontab file">
						<input style="margin-top: 1px; margin-right: 0px;" type="button" id="edit_cron_enabled" OnClick="window.location=\'index.php?tab=settings&sub=edit\'" value="Edit crontab file">
						</td>
					</tr>
				</table>
				<br><br>
					';
				}
				
				else {
					$body = 'Crontab file is not specified!';
					displayMessageFieldset('Warning!', $body, '?tab=settings');					
				}
	}
}
?>