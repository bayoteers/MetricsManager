<?php

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name_of_stats'])) {
	
	$statistic_to_edit = $_GET['statistic'];
	$edit_statistic_content = $_POST['edit_statistic_content'];
	$statistic_array = explode("\n",$edit_statistic_content);

	$bz_search = $_POST['bz_search_to_edit'];
	$common_params = $common_parameters_file;
	$name_of_stats = $_POST['name_of_stats'];
	$existing_list = $_POST['existing_list_to_edit'];
	$subset_list = $_POST['subset_list_to_edit'];
	$classification = $_POST['classification_to_edit'];
	$path_to_data_directory="/var/www/bugzilla_statistics/data/";

	if (!(is_dir($path_to_data_directory.$name_of_stats))){
	
		system("sudo lib/libcontentaction.pl --start $login 'Update statistic'");
	
		$k = 0;
		if($common_params != ''){
			$field_arr[$k] = "COMMON_PARAMS_FILE = ". $common_params;
			$k++;
		}
		if($bz_search != ''){
			$field_arr[$k] = "BUGZILLA_URL_COMMON_PARAMS = ". $bz_search;
			$k++;
		}
		if($name_of_stats != ''){
			$field_arr[$k] = "STATISTICS = " . $name_of_stats;
			$k++;
		}
		if($subset_list != ''){
			$field_arr[$k] = "SUBSET_OF = " . $subset_list;
			$k++;
		}
		if($classification != ''){
			$field_arr[$k] = "INCOMPLETE_CLASSIFICATION = " . $classification;
			$k++;
		}
		if($existing_list){
			$field_arr[$k] = "PRODUCTS_CONFIG_FILE = " . $existing_list;
			$k++;
		}
		//Rename raw data
		system("sudo lib/libcontentaction.pl --rename $statistic_to_edit $name_of_stats");

		//Updating config file
		$config_temp_file = 'tmp/' . rand();
		$complete_config_file = implode("\n", $field_arr);
		file_put_contents($config_temp_file, $complete_config_file);
		system("sudo lib/libcontentaction.pl --move $config_temp_file $statistic_to_edit");
		
		system("sudo lib/libcontentaction.pl --end $login 'Update statistic'");

		echo '<fieldset id="interior" style="width:700px; margin: auto; padding: 20px;"><legend>Statistic has been updated</legend>Selected statistic "' . $statistic_to_edit . '" has been changed. Check <a href="#" onclick="handleWantsList(false)">syslog</a> for details.';
		echo '<br><br>Return to <a href="?tab=edit_existing_statistics">Edit existing statistics</a> tab</fieldset>';
		
		echo '
			<br>
			<div id="list_false" style="display:none; width:100%; height: 310px; margin: auto;">
				<fieldset id="interior" style="width:99%; height: 300px; margin:auto;"><legend>Syslog</legend>
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
	else{
		echo'<fieldset id="interior" style="width:700px; margin: auto; padding: 20px;"><legend>Error</legend>Statistic with specified name already exists. Please change name of statistic.<br><br><b>Press browser back button to correct file name.</b></fieldset>';
	}
}
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['read_statistic_content'])) {
	$statistic_to_edit = $_POST['existing_statistics'];

	$edit_statistic_content = fread(fopen($statistic_to_edit, "rw"), filesize($statistic_to_edit));
	$existing_statistic_array = explode("\n",$edit_statistic_content);

	echo '<form name="edit_statistics" action="index.php?tab=edit_existing_statistics&statistic=' . $statistic_to_edit . '" method="POST">
			<fieldset id="interior">
				<legend>Edit: ' . $statistic_to_edit . '</legend>
					<table class="create">';
	foreach($existing_statistic_array  as $i => $value) {
		$existing_statistic_array[$i]=trim($existing_statistic_array[$i]);
		$check_string[$k]=strstr($existing_statistic_array[$i], "=");
		$find[$i]=strpos($existing_statistic_array[$i], "=");
		$check_string[$i]=substr($existing_statistic_array[$i], 0, $find[$i]-1);
		$field_value[$i]=substr($existing_statistic_array[$i], $find[$i]+2);

		if  (!empty($existing_statistic_array[$i])) {
			if($check_string[$i] == "COMMON_PARAMS_FILE"){
				echo '
					<tr>
						<td class="left">
							Common parameters:
						</td>
						<td class="center_create">
							<input disabled style="width:650px; height:22px;" value="'.$field_value[$i].'"/>
							<input type="hidden" style="width:650px; height:22px;" value="'.$field_value[$i].'"/>
						</td>
						<td class="help">
							<ul>?
								<li>
									Not able to edit
								</li>
							</ul>
						</td>
					</tr>';
			}
			elseif($check_string[$i] == "BUGZILLA_URL_COMMON_PARAMS"){
				echo '
					<tr>
						<td class="left">
							Bugzilla search:
						</td>
						<td class="center_create">
							<input disabled style="width:650px; height:22px;" name="bz_search" value="'.$field_value[$i].'"/>
							<input type="hidden" style="width:650px; height:22px;" name="bz_search_to_edit" value="'.$field_value[$i].'"/>
						</td>
						<td class="help">
							<ul>?
								<li>
									Not able to edit
								</li>
							</ul>
						</td>
					</tr>';
			}
			elseif($check_string[$i] == "PRODUCTS_CONFIG_FILE"){
				echo '
					<tr>
						<td class="left">
							List of products:
						</td>
						<td class="center_create">';
							echo '
								<input disabled type="text" style="width:650px; height:22px;" name="existing_list" value="'.$field_value[$i].'"/>
								<input type="hidden" style="width:650px; height:22px;" name="existing_list_to_edit" value="'.$field_value[$i].'"/>
						</td>
						<td class="help">
							<ul>?
								<li>
									Not able to edit
									<!--Please select existing list of products or create new list by click on link "Create new list of products".<br> To check or modify content of existing lists go to tab "List of products"-->
								</li>
							</ul>
						</td>
					</tr>';
			}
			elseif($check_string[$i] == "STATISTICS"){
				echo '
					<tr>
						<td class="left">
							Name of statistic:
						</td>
						<td class="center_create">
							<input type="text" style="width:650px; height:22px;" name="name_of_stats" value="'.$field_value[$i].'" />
							<input type="hidden" style="width:650px; height:22px;" name="name_of_stats_to_edit" value="'.$field_value[$i].'" />
						</td>
						<td class="help">
							<ul>?
								<li>
									Please specify name of statistic. Name has an affect on displaying statistics in subgroups.<br>Signs "_-_" divides into a subgroups e.g. Harmattan_with_PR1.2_flag_-_OS_-_Adaptation
								</li>
							</ul>
						</td>
					</tr>';
			}
			elseif($check_string[$i] == "SUBSET_OF"){
				echo '
					<tr>
						<td class="left">
							Subset of:
						</td>
						<td class="center_create">
							<input disabled style="width:650px; height:22px;" name="subset_list" value="'.$field_value[$i].'"/>
							<input type="hidden" style="width:650px; height:22px;" name="subset_list_to_edit" value="'.$field_value[$i].'"/>
						</td>
						<td class="help">
							<ul>?
								<li>
									Not able to edit
								</li>
							</ul>
						</td>
					</tr>';
			}
			elseif($check_string[$i] == "INCOMPLETE_CLASSIFICATION"){
				echo '
					<tr>
						<td class="left">
							Incomplete classification:
						</td>
						<td class="center_create">
							<input disabled style="width:650px; height:22px;" name="classification" value="'.$field_value[$i].'"/>
							<input type="hidden" style="width:650px; height:22px;" name="classification_to_edit" value="'.$field_value[$i].'"/>
						</td>
						<td class="help">
							<ul>?
								<li>
									Not able to edit
								</li>
							</ul>
						</td>
					</tr>';
			}
		}
	}
	echo'
				</table><br>';
	echo'		</fieldset>
				<table align="right" style="overflow: auto; width: 100%">
					<tr>
						<td style="width: 100px;">
						<p align="right">
						<input type="button" OnClick="window.location=\'index.php?tab=edit_existing_statistics\'" value="Dismiss changes">
						<input type="button" name="save_statistic_field" value="Save changes" onClick="submitFields()" onChange="showSpinner()">
						</td>
					</tr>
				</table>
			</form>';


	echo
	'<br><br>
	<form name="view_file">
	<fieldset id="interior">
		<legend>Preview of existing file</legend>
		<textarea disabled style="width:1000px; height:250px;" name="edit_statistic_content">' .$edit_statistic_content. '</textarea>
	</fieldset>
	</form>
	<br><br>
	';
}
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_statistic']) ){
	system("sudo lib/libcontentaction.pl --start $login 'Remove statistic'");
	
	$statistic_to_remove = $_POST['existing_statistics'];

	$edit_fetch_content = fread(fopen($fetch_statistics_file, "rw"), filesize($fetch_statistics_file));
	$fetch_file_array = explode("\n",$edit_fetch_content);

	foreach($fetch_file_array  as $i => $value) {
		$whole_line = 'fetch_statistics_from_bugzilla.pl ' . $statistic_to_remove;
			if (($fetch_file_array[$i] != $whole_line)) {
				$complete_fetch_array[$i] = $fetch_file_array[$i];
			}
	}
	
	$fetch_temp_file = 'tmp/' . rand();
	$complete_fetch_array_lines = implode("\n", $complete_fetch_array);
	file_put_contents($fetch_temp_file, $complete_fetch_array_lines);
	
	system("sudo lib/libcontentaction.pl --move $fetch_temp_file $fetch_statistics_file");
	system("sudo lib/libcontentaction.pl --remove $statistic_to_remove");
	
	system("sudo lib/libcontentaction.pl --end $login 'Remove statistic'");
	
	echo '	<fieldset id="interior" style="width:700px; margin: auto; padding: 20px;">
				<legend>Statistic has been removed</legend>
					Statistic "' . $statistic_to_remove . '" has been removed. Check <a href="#" onclick="handleWantsList(false)">syslog</a> for details.<br><br><a href="?tab=edit_existing_statistics">Back to \'Edit existing statistics\' tab. </a>
			</fieldset>
		';
	echo '
			<br>
			<div id="list_false" style="display:none; width:100%; height: 310px; margin: auto;">
				<fieldset id="interior" style="width:99%; height: 300px; margin:auto;"><legend>Syslog</legend>
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

else {
	if ($_GET['statistic'] == '') {
		echo '

		<form name="form_existing_statisctics" action="index.php?tab=edit_existing_statistics" method="POST">
		<fieldset id="interior">
			<legend>Statistics directory: ' . $path_to_statistics_directory . '</legend>
		';
			$file_list = glob($path_to_statistics_directory . '*.conf');
			foreach ($file_list as $file) {
				if ($file != $common_parameters_file) {
					$file_name = clipPathToFileName($file, $path_to_statistics_directory);
					echo '<input type="radio" name="existing_statistics" onclick="enableButton();" value="' . $file . '"> ' . $file_name . '<br>';
				}
			}
		echo '
		</fieldset>
		<table align="right" style="overflow: auto; width: 100%">
				<tr>
					<td style="width: 100px;">
					<p align="right">
					<input disabled type="submit" name="remove_statistic" value="Remove statistic" onClick="showSpinner()"/>
					<input disabled style="margin-top: 1px; margin-right: 0px;" type="submit" name="read_statistic_content" value="Edit selected list of products" onClick="showSpinner()"/>
					</td>
				</tr>
			</table>
		</form>
		<br><br>
		';
	}
}
?>

<script type="text/javascript">

function enableButton() {
	var val = 0;
	for ( i = 0; i < document.form_existing_statisctics.existing_statistics.length; i++ ) {
		if( document.form_existing_statisctics.existing_statistics[i].checked == true ) {
			document.form_existing_statisctics.read_statistic_content.disabled=false;
			document.form_existing_statisctics.remove_statistic.disabled=false;
			document.form_existing_statisctics.read_statistic_content.focus();
		}
	}
}
function submitFields() {
	var invalid = " "; 

	if (document.edit_statistics.name_of_stats_to_edit.value == document.edit_statistics.name_of_stats.value) {
		alert("Nothing has been changed");
	}
	else if (!document.edit_statistics.name_of_stats.value){
		alert("Please specify name of statistic");
	}
	else if (document.edit_statistics.name_of_stats.value.indexOf(invalid) > -1) {
		alert("Name of statistic field cannot contains spaces");
	}
	else {
		document.edit_statistics.submit();
	}
}

function handleWantsList(wants_list){
	if (wants_list){
		document.getElementById('list_false').style.display = 'none';
		document.getElementById('list_true').style.display = 'block';
	}
	else{
		document.getElementById('list_false').style.display = 'block';
		document.getElementById('list_true').style.display = 'none';
	}
}
</script>