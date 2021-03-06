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

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_selected_list'])) {
	$list_to_edit = $_GET['list'];
	$list_to_edit_content = $_POST['list_to_edit_content'];
	$products_array = explode("\n",$list_to_edit_content);
	
	$temp_file = 'tmp/' . rand();
	saveToFile($temp_file, $list_to_edit_content);
	system("sudo lib/libcontentaction.pl --move $temp_file $list_to_edit");
	
	$body = 'Selected list "' . $list_to_edit . '" has been changed.';
	displayMessageFieldset('Success!', $body, '?tab=list_of_products');
}

else if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_list'])) {
	$list_to_edit = $_POST['existing_list'];
	
	$list_to_edit_content = fread(fopen($list_to_edit, "rw"), filesize($list_to_edit));
	echo 
	'
	<form name="edit_selected_lists_of_products" action="index.php?tab=list_of_products&list=' . $list_to_edit . '" method="POST">
	<fieldset id="interior">
		<legend>Edit: ' . $list_to_edit . '</legend>
		<textarea rows="15" cols="75" style="width: 100%; height: 80%;" name="list_to_edit_content">' .$list_to_edit_content. '</textarea>
	</fieldset>
	<table align="right" style="overflow: auto; width: 100%">
		<tr>
			<td style="width: 100px;">
			<p align="right">
			<input type="button" OnClick="window.location=\'index.php?tab=list_of_products\'" value="Dismiss changes">
			<input type="submit" name="edit_selected_list" value="Save changes">
			</td>
		</tr>
	</table>
	</form>
	<br><br>
	';
}

else if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_list'])) {
	$list_to_remove = $_POST['existing_list'];
	
	system("sudo lib/libcontentaction.pl --remove-file $list_to_remove");
	echo '
	<fieldset id="interior" style="width:700px; margin: auto; padding: 20px;">
		<legend>Success!</legend>
		<p>Selected list "' . $list_to_remove . ' has been removed.</p>
		<p>Return to <a href="?tab=list_of_products">List of products</a> tab</p>
	</fieldset>';
}

else if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_list'])) {
	$list_name = $_POST['list_name'];
	$add_products = $_POST['add_products'];
	
	$list_path = $path_to_products_directory . $list_name;
	
	if (! file_exists($list_path)) {	
		$temp_file = 'tmp/' . rand();
		saveToFile($temp_file, $add_products);
		system("sudo lib/libcontentaction.pl --move $temp_file $list_path");
		
		$body = 'New list "' . $list_name . '" has been created.';
		displayMessageFieldset('Success!', $body, '?tab=list_of_products');
	}
	
	else {
		$body = 'List "' . $list_name . '" already exists.';
		displayMessageFieldset('ERROR!', $body, '?tab=list_of_products');
	
	}
}

else {
	if ($_GET['list'] == '') {
		echo '		
		<form name="form_existing_lists_of_products" action="index.php?tab=list_of_products" method="POST">
		<fieldset id="interior">
			<legend>Edit existing lists of products</legend>
		
		<div id="editProductTree" class="tree"></div>
		<input type="hidden" class="input_text" name="existing_list"/>
		
		
		';
		/*
			$file_list = glob($path_to_products_directory . '*.conf');
			foreach ($file_list as $file) {
				$file_name = clipPathToFileName($file, $path_to_products_directory);
				echo '<input type="radio" name="existing_list" onclick="enableButton();" value="' . $file . '"> ' . $file_name . '<br>';
			}
		*/
		echo '
		</fieldset>
		<table align="right" style="overflow: auto; width: 100%">
				<tr>
					<td style="width: 100px;">
					<p align="right">
					<input disabled style="margin-top: 1px; margin-right: 0px;" type="submit" name="remove_list" value="Remove selected list of products">
					<input disabled style="margin-top: 1px; margin-right: 0px;" type="submit" name="edit_list" value="Edit selected list of products">
					</td>
				</tr>
			</table>		
		</form>
		<br><br>
		';
		
		echo '
		<form name="form_create_list_of_products" action="index.php?tab=list_of_products" method="POST">
		<fieldset id="interior">
			<legend>Create new list of products</legend>
			<table class="create">
				<tr>
					<td class="left"><p>*Products list file name:</p></td>
					<td><input type="text" style="width:650px; height:22px;" name="list_name" id="list_name"></td>
					<td class="help"><ul>?
						<li>Name of list with .conf
						</li></ul></td>
				</tr>
				<tr>
					<td class="left" style="vertical-align:top;">*Specify products:</td>
					<td>
						<textarea style="width:650px; height:250px;" name="add_products" id="add_products">
#FORMAT: component name;bugzilla_search_params, i.e.:<br>
#MeeGo_Quality_Assurance;product=MeeGo%20Quality%20Assurance</textarea>
					</td>
					<td class="help" vlign="top"><ul>?
						<li>
							<p>
								FORMAT: component name;bugzilla_search_params, i.e.:<br>
								MeeGo_Quality_Assurance;product=MeeGo%20Quality%20Assurance
								<br>
								<br>To avoid problems:
								<br>- do not use white spaces nor special characters in component name - this parameter
								<br>is used to create a folder so special characters can case unexpected issues.
								<br>When displaying data on the WWW page:
								<br>- _ will be replaced with space
								<br>- and will be replaced with & sign
								<br>- convert special characters in bugzilla search params to URL standard, e.g:
								<br>- change spaces to %20
								<br>- change & character to %26
								<br>- change ( character to %28
								<br>- change ) character to %29
								<br>- change spaces to %20
							</p>
						</li></ul>
					</td>		
				</tr>
			</table>
		<br>
		</fieldset>
		<table align="right" style="overflow: auto; width: 100%">
			<tr>
				<td style="width: 100px;">
				<p align="right">
				<input style="margin-top: 1px; margin-right: 0px;" type="submit" name="create_list" value="Create new list of products">
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
			
	$('#editProductTree').fileTree({
		root: '<?php echo $path_to_products_directory; ?>',
		script: 'lib/file_tree/jqueryFileTree.php'
	},
	function(file) { 
		chooseElement(file);
	});	
});

</script>
