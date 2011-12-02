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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_statistic']) ){
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
		echo '
		
				<div id="editFileTree" class="tree"></div>
				<input type="hidden" class="input_text"  name="existing_statistics" />
			</fieldset>
			<table align="right" style="overflow: auto; width: 100%">
				<tr>
					<td style="width: 100px;">
						<p align="right">
						<input disabled type="submit" name="remove_statistic" value="Remove statistic" onClick="showSpinner()"/>
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
	$(document).ready( function() {
				
		$('#editFileTree').fileTree({
			root: '<?php echo $path_to_statistics_directory; ?>',
			script: 'lib/file_tree/jqueryFileTree.php'
		},
		function(file) { 
			removeFile(file);
		});	
	});
</script>