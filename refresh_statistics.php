<?php

/*
#===================================================================================
# BAM Manager (Bugzilla Automated Metrics Manager): index.php
#
# Copyright 2011, Comarch SA
# Maintainers: 	Krystian Jedrzejowski <krystian.jedrzejowski@comarch.com>,
# 				Kamil Marek <kamil.marek@comarch.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Jul 13 11:56:00 EET 2011
#===================================================================================
*/

/*
# Action: Refresh selected statistics
# 1. Remove present-day raw_data files for selected statistics (one-by-one)
# 2. Fetch data for selected statistics (one-by-one)
*/
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['refresh_selected_statistics'])) {
	$login = $_SESSION['logged in'];
	system("sudo lib/libcontentaction.pl --start $login Refresh_statistics");
	$list_of_statistics_to_refresh = $_POST['existing_list'];
	$number_of_selected_statistics_to_refresh = count($list_of_statistics_to_refresh);
	
	if ($number_of_selected_statistics_to_refresh == 0) {
		$log_info = 'You apparently didn\'t choose any statistics to refresh.';
		logToSyslog($log_info);
		displayMessageFieldset('ERROR!', $log_info, '?tab=refresh_statistics');
	}
	
	else {
		$body = 'Status: <br>';
		$success = true;
		
		for($i=0; $i < $number_of_selected_statistics_to_refresh; $i++) {
			//Remove present-day raw_data files for selected statistics
			system("sudo lib/libcontentaction.pl --refresh $list_of_statistics_to_refresh[$i]"); 
			
			//$command = "/var/www/bugzilla_statistics/manager/lib/libcontentaction.pl --fetch $fetch_statistics_from_bugzilla_file $list_of_statistics_to_refresh[$i]";
			
			exec("sudo lib/libcontentaction.pl --fetch $statistics_user $fetch_statistics_from_bugzilla_file $list_of_statistics_to_refresh[$i]", &$output, &$return_var);
			if ($return_var == 9) {
				$body .= '<p>Updating' . $list_of_statistics_to_refresh[$i];
				$body .= '<br>Success! Selected statistics has been updated<br>';
				$path = searchString($common_parameters_file, "STATS_URL_BASE") . '?s=' . searchString($list_of_statistics_to_refresh[$i], "STATISTICS") . '&p=all';
				$body .= '<a href = "' . $path . '" target="_blank">' . searchString($list_of_statistics_to_refresh[$i], "STATISTICS") . '</a></p>';
			}
			
			else {
				$success = false;
				$body .= 'Updating' . $list_of_statistics_to_refresh[$i];
				$body .= '<br>ERROR occured while updating statistics.<br><br>';
			}
		}
	
		if ($success == true) {
			$body .= 'All selected statistics have been updated.';
			displayMessageFieldset('Success!', $body, '?tab=refresh_statistics');
		}
		
		else {
			$body .= 'Refresh of some statistics listed above has failed. Check <a href="#" onclick="handleWantsList(false)">syslog</a> for details.';
			displayMessageFieldset('Status', $body, '?tab=refresh_statistics');
			
			echo '
			<br>
			<div id="list_false" style="display:none; width:100%; height: 310px; margin: auto;">
				<fieldset id="message_fieldset" style="width:99%; height: 300px;"><legend>Syslog</legend>
					<div class="scroll">
			';
			$syslog = loadFileContentToVariable('log/syslog');
			$syslog = explode("\n",$syslog);
			echo '
						<p>' . displayArray($syslog) . '</p>';
			echo '
					</div>
				</fieldset><br>
			</div>
			<br><br>
			';
		}
	}
	system("sudo lib/libcontentaction.pl --end $login 'Refresh statistics'");
}

/*
# Action: Refresh ALL statistics
# 1. Remove present-day raw_data files for ALL statistics (at once)
# 2. Fetch data for ALL statistics (at once)
*/ 
else if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['refresh_all'])) {
	system("sudo lib/libcontentaction.pl --start $login 'Refresh all statistics'");
	
	//Remove present-day raw_data files for ALL statistics
	// and then
	//Fetch data for ALL statistics
	exec("sudo lib/libcontentaction.pl --refresh-all $common_parameters_file $statistics_user $fetch_statistics_file", &$output, &$return_var);
	
	// check whether refreshing was successfull
	if ($return_var == 9) {
		system("sudo lib/libcontentaction.pl --end $login 'Refresh all statistics'");
		$path = searchString($common_parameters_file, "STATS_URL_BASE");
		$body = 'All statistics have been succesfully refreshed. <br> <a href="' . $path . '?s="target="_blank">' . $path . '</a>';
		displayMessageFieldset('Success!', $body, '?tab=refresh_statistics');
	}
	
	else {
		$success = false;
		$body = 'Refresh of some statistics has failed. Check <a href="#" onclick="handleWantsList(false)">syslog</a> for details.';
		displayMessageFieldset('Status', $body, '?tab=refresh_statistics');
		echo '
		<br>
		<div id="list_false" style="display:none; width:100%; height: 310px; margin: auto;">
			<fieldset id="message_fieldset" style="width:99%; height: 300px;"><legend>Syslog</legend>
				<div class="scroll">
		';
		system("sudo lib/libcontentaction.pl --end $login 'Refresh all statistics'");
		$syslog = loadFileContentToVariable('log/syslog');
		$syslog = explode("\n",$syslog);
		echo '
					<p>' . displayArray($syslog) . '</p>';
		echo '
				</div>
			</fieldset><br>
		</div>
		<br><br>
		';
	}
}

/*
# Initial view of 'Refresh statistics tab'
# List of existing statistics
# And two buttons: Refres all & Refresh selected statistics
*/
else {
	echo '
	<form name="refresh_statistics" action="index.php?tab=refresh_statistics" method="POST">
	<fieldset id="interior">
		<legend>Statistics directory: ' . $path_to_statistics_directory . '</legend>
	';
		$file_list = glob($path_to_statistics_directory . '*.conf');
		foreach ($file_list as $file) {
			if ($file != $common_parameters_file) {
				$file_name = clipPathToFileName($file, $path_to_statistics_directory);
				echo '
				<input type="checkbox" id="checkbox_refresh" name="existing_list[]" value="' . $file . '"> ' . $file_name . '<br>';
			}
		}
	echo '
	</fieldset>
	<table align="right" style="overflow: auto; width: 100%" id="submit_button_table">
		<tr>
			<td style="width: 100px;">
			<p align="right">
			<input style="margin-top: 1px; margin-right: 0px;" type="submit" name="refresh_all" value="Refresh all" onClick="showSpinner()">
			<input style="margin-top: 1px; margin-right: 0px;" type="submit" name="refresh_selected_statistics" value="Refresh selected statistics" onClick="showSpinner()">
			</td>
		</tr>
	</table>
	</form>
	<br><br>
	';
}
?>