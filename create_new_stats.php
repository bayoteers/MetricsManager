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

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['path'])) {
	
	system("sudo lib/libcontentaction.pl --start $login 'Create statistic'");
	
	//New products variables (create_list_of_products.php file)
	$list_name = $_POST['list_name'];
	$add_products = $_POST['add_products'];
	//Common variables
	$path_of_conf_file= trim($_POST['path']);
	$file_check = file_exists($path_of_conf_file);
	$common_params = $common_parameters_file;
	$bz_search = trim($_POST['bz_search']);
	$name_of_stats = trim($_POST['name_of_stats']);
	$existing_list = $_POST['existing_list'];
	$subset_from_date = trim($_POST['subset_from_date']);
	$classification = $_POST['classification'];
	$lunch_method = $_POST['lunch_method'];
	$subset_path = $_POST['subset_path'];
	
	//Searching BZ search parameter in subset of file.
	if($subset_path){
		$subset_bz_search =  searchString($subset_path, "BUGZILLA_URL_COMMON_PARAMS");
	}
	//Searching name of statistic in subset of file.
	if($subset_path){
		$subset_statistic_name = searchString($subset_path, "STATISTICS");
	}
	
	//Defining form array
	if (!$file_check){
		$list_name_empty = $list_name;
		if($list_name_empty){
			$products_file_check = file_exists($list_path =  $path_to_products_directory . $list_name);
		}
		else{
			$products_file_check = 0;
		}
		if(!$products_file_check){
			$k = 0;
			if($common_params){
				$field_arr[$k] = "COMMON_PARAMS_FILE = ".$common_params;
				$k++;
			}
			if($bz_search){
				$field_arr[$k] = "BUGZILLA_URL_COMMON_PARAMS = ".$bz_search;
				$k++;
			}
			elseif($subset_path){
				$field_arr[$k] = "BUGZILLA_URL_COMMON_PARAMS = ".$subset_bz_search;
				$k++;
			}
			if($name_of_stats){
				$field_arr[$k] = "STATISTICS = ".$name_of_stats;
				$k++;
			}
			if($existing_list){
				$field_arr[$k] = "PRODUCTS_CONFIG_FILE = ".$existing_list;
				$k++;
			}
			else{
				//Creating list of products if chosen
				$list_temp_file = 'tmp/'.rand();
				saveToFile($list_temp_file, $add_products);
				system("sudo lib/libcontentaction.pl --move $list_temp_file $list_path");
				
				$field_arr[$k] = "PRODUCTS_CONFIG_FILE = ".$list_path;
				$k++;
			}
			if($subset_path){
				$field_arr[$k] = "SUBSET_OF = ".$subset_statistic_name;
				$k++;
			}
			if($classification){
				$field_arr[$k] = "INCOMPLETE_CLASSIFICATION = ".$classification;
				$k++;
			}
			//Creating conf file
			$create_config_temp_file = 'tmp/' . rand();
			$complete_create_config_file = implode("\n", $field_arr);
			file_put_contents($create_config_temp_file, $complete_create_config_file);
			system("sudo lib/libcontentaction.pl --move $create_config_temp_file $path_of_conf_file");

			//Adding lunch line to fetch_statistics.sh
			$fetch = 'fetch_statistics_from_bugzilla.pl '.$path_of_conf_file;
			$fetch=str_replace(' ', '__space__',$fetch);
			system("sudo lib/libcontentaction.pl --save $fetch_statistics_file $fetch");
					
			//Decide when statistic runs.
			if($lunch_method == 'run_now' && !$subset_path){
				//Lunch statistic now
				$command = "lib/libcontentaction.pl --fetch $statistics_user $fetch_statistics_from_bugzilla_file $path_of_conf_file";
				exec("sudo $command", &$output, &$return_var);
				/* 
				// check whether fetching was successful
				if ($return_var == 9) {
					echo 'OK';
				}
			
				else {
					echo 'ERROR';
				}
				*/
	echo'
					<fieldset id="interior" style="width:700px; margin: auto; padding: 20px;">
						<legend>
							Statistic has been successfully created
						</legend> 
							Statistic has been created: <a href="http://milosz-pc.research.nokia.com/bugzilla_statistics/?s='.$name_of_stats.'" target="_blank">
								http://milosz-pc.research.nokia.com/bugzilla_statistics/?s='.$name_of_stats.'.</a>
							&nbspCheck <a href="#" onclick="handleWantsList(false)">syslog</a> for details.
							<br><br>
							<a href="?tab=create_new_stats">Back to Create Form</a>
					</fieldset>
	';
	echo'
					<br>
					<div id="list_false" style="display:none; width:100%; height: 310px; margin: auto;">
						<fieldset id="interior" style="width:99%; height: 300px; margin:auto;"><legend>Syslog</legend>
							<div class="scroll">
	';
					$syslog = loadFileContentToVariable('log/syslog');
					$syslog = explode("\n",$syslog);
	echo'
					<p>' . displayArray($syslog) . '</p>
	';
	echo'
							</div>
						</fieldset><br>
					</div>
					<br><br>
	';
			}
			elseif($lunch_method == 'run_now' && $subset_path){
				//Lunch subset of
				$command_subset = "/var/www/bugzilla_statistics/manager/lib/libcontentaction.pl --subset /home/btests-www/bin/create_subset.pl $path_of_conf_file $subset_from_date $statistics_user";
				exec("sudo $command_subset", &$output, &$return_var);
				/* 
				// check whether fetching was successful
				if ($return_var == 9) {
					echo 'OK';
					}
				else {
					echo 'ERROR';
				}
				*/
	echo'
						<fieldset id="interior" style="width:700px; margin: auto; padding: 20px;">
							<legend>
								Statistic has been successfully created
							</legend>
							Statistic has been created: 
							<a href="http://milosz-pc.research.nokia.com/bugzilla_statistics/?s='.$name_of_stats.'" target="_blank">
								http://milosz-pc.research.nokia.com/bugzilla_statistics/?s='.$name_of_stats.'.
							</a>
							&nbspCheck <a href="#" onclick="handleWantsList(false)">syslog</a> for details.
							<br><br>
							<a href="?tab=create_new_stats">Back to Create Form</a>
						</fieldset>
	';
	echo'
					<br>
					<div id="list_false" style="display:none; width:100%; height: 310px; margin: auto;">
						<fieldset id="interior" style="width:99%; height: 300px; margin:auto;"><legend>Syslog</legend>
							<div class="scroll">
	';
					$syslog = loadFileContentToVariable('log/syslog');
					$syslog = explode("\n",$syslog);
	echo'
					<p>' . displayArray($syslog) . '</p>
	';
			}
			elseif($lunch_method == 'run_auto'){
	echo'
					<fieldset id="interior" style="width:700px; margin: auto; padding: 20px;">
						<legend>
							Files have been successfully created
						</legend>
						Files have been created. Wait for next automatic run to see metrics under
						<a href="http://milosz-pc.research.nokia.com/bugzilla_statistics/?s='.$name_of_stats.'" target="_blank">
							http://milosz-pc.research.nokia.com/bugzilla_statistics/?s='.$name_of_stats.'</a>
						<br><br>
						<a href="?tab=create_new_stats">
								Back to Create Form
						</a>
					</fieldset>
	';
			}
			system("sudo lib/libcontentaction.pl --end $login 'Create statistic'");
		}
		else{
	echo'		 
				<fieldset id="interior" style="width:700px; margin: auto; padding: 20px;">
					<legend>
						Error
					</legend>
					Products file with specified name already exists. Please change name of products file.
					<br><br>
					<b>Press browser back button to correct file name.</b>
				</fieldset>
	';
		}
	}
	else{
	echo'		
				<fieldset id="interior" style="width:700px; margin: auto; padding: 20px;">
					<legend>
						Error
					</legend>
					File with specified name already exists. Please change name of file.
					<br><br>
					<b>Press browser back button to correct file name.</b>
				</fieldset>
	';
	}
}
else {
	echo'
			<fieldset id="interior">
			<legend>Create new statistics</legend>
				<form name="create_form" action="index.php?tab=create_new_stats" method="POST"><br>
		<!--			
					<table width="100%">
						<tr>
							<td class="left">
								Common Parameters File:
							</td>
							<td>
								<input type="radio" checked name="common_params" value="/usr/local/etc/bugzilla_statistics/common_parameters.conf" onChange="javascript:document.create_form.custom_params.disabled=true"> Default (/usr/local/etc/bugzilla_statistics/common_parameters.conf)<br>
								<input type="radio" name="common_params" value="" onChange="javascript:document.create_form.custom_params.disabled=false"> Custom <input disabled size="50" type="text" name="custom_params" value="" placeholder="DO NOT USE - under construction"/><br>
							</td>
						</tr>
					</table><br>
				<hr>
		-->
					<table class="create" border="0"><br>
						<tr>
							<td class="left">
								*Name of configuration file:
							</td>
							<td class="center_create">
								<input type="button" name="browse" value="Browse" onclick="showFileTree()" />
								<span id="inactive_path" style="padding: 2px"></span>
								<input class="input_text" type="hidden" name="path" />
							</td>
						</tr>
						<tr>
							<td class="left">
								*Bugzilla search:
							</td>
							<td class="center_create">
								<input class="input_text"  type="text" name="bz_search" />
								<input class="input_text"  type="hidden" name="bz_search_hidden" />
							</td>				
							<td class="help">
								<ul>?
									<li>
										Please provide bugzilla search parameters:<br> e.g. "bug_severity=blocker,critical,major,normal,minor,task&classification=Harmattan"
									</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td class="left">
								*Name of statistic:
							</td>
							<td class="center_create">
								<input class="input_text" type="text" name="name_of_stats"/>
							</td>
							<td class="help">
								<ul>?
									<li>
										Please specify name of statistic. Name has an affect on displaying statistics in subgroups.<br>Signs "_-_" divides into a subgroups e.g. Harmattan_with_PR1.2_flag_-_OS_-_Adaptation
									</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td class="left">
								*List of products:
							</td>
							<td class="center_create">
								<input type="button" name="browse" value="Browse" id="browseProducts" onclick="showProductsTree()" />
								<span id="inactive_products" style="padding: 2px"></span>
								<input class="input_text" type="hidden" name="existing_list" />
							</td>
						</tr>
					</table><br>
	';
						//Create list of products
						$subpage = loadFileContentToVariable('create_list_of_products.php');
						eval("?>".$subpage."<?php ");
	echo'
				<hr>
					<table class="create"><br>
						<tr>
							<td rowspan="2" style="width:75px" align="right">
								<input type="checkbox" name="subset_check" value="custom" onChange="enableField()">
							</td>
							<td style="width:200px" align="right">
								Subset of:
							</td>
							<td>
								<input id="subsetBrowse" type="button" disabled name="browse" value="Browse" onclick="showSubsetTree()" />
								<span id="inactive_subset" style="padding: 2px"></span>
								<input class="input_text" type="hidden" name="subset_path">
								<input class="input_text" type="hidden" name="subset_path_hidden" />
							</td>
						</tr>	
						<tr>
							<td style="width:150px" align="right">
								Retrieve history from date:
							</td>
							<td class="center_create">
								<input class="input_text" disabled type="text" name="subset_from_date" placeholder="YYYY-MM-DD"/>
								<input class="input_text"  type="hidden" name="subset_from_date_hidden" />
							</td>
							<td class="help">
								<ul>?
									<li>
										Help text
									</li>
								</ul>
							</td>
						</tr>
					</table><br>
				<hr>
					<table class="create"><br>
						<tr>
							<td class="left">
								Incomplete classification:
								<br>
								<a href="#" class="classification"  onClick="uncheck()" style="margin-left:20px;">>Uncheck all<</a>
							</td>
							<td>
								<input type="radio" name="classification" value="true"> True
								<br>
								<input type="radio" name="classification" value="false"> False
							</td>
						</tr>
					</table><br>
				<hr>
					<table class="create"><br>
						<tr>
							<td class="left">
								*Define run method:
							</td>
							<td>
								<input type="radio" id="run_now" name="lunch_method" value="run_now"> Run statistics now
								<br>
								<input type="radio" id="run_auto" name="lunch_method" value="run_auto"> Wait for automatic run (time of automatic run defined in settings)
							</td>
						</tr>
					</table><br>
		</fieldset>
			<table id="submit_button_table" align="right" style="overflow: auto; width: 100%">
				<tr>
					<td style="width: 100px;">
						<p align="right">
						<input style="margin-top: 1px; margin-right: 0px;" valign="right" type="button" name="create_new_statistics" value="Create statistics" onclick="createCheckMandatoryFields()" />
					</td>
				</tr>
			</table>
			<br><br>
		</form>
	';
}
?>

<script type="text/javascript">
	/*Shows whole statistic tree (statistics || subsets)*/
	$(document).ready( function() {
				
		$('#fileTree').fileTree({
			root: '<?php echo $path_to_statistics_directory; ?>',
			script: 'lib/file_tree/jqueryCreateFileTree.php'
		},
		function(file) { 
			createFile(file);
		});				
	});
	/*Shows products tree*/
	$(document).ready( function() {
				
		$('#productsFileTree').fileTree({
			root: '<?php echo $path_to_products_directory; ?>',
			script: 'lib/file_tree/jqueryProductsFileTree.php'
		},
		function(file) { 
			chooseProducts(file);
		});				
	});
	/*Shows subset tree*/
	$(document).ready( function() {
				
		$('#subsetFileTree').fileTree({
			root: '<?php echo $path_to_statistics_directory; ?>',
			script: 'lib/file_tree/jquerySubsetsFileTree.php'
		},
		function(file) { 
			chooseSubset(file);
		});				
	});

function createCheckMandatoryFields(){
	var nameFormat = /^\s*\S+\s*$/;
	var fileFormat = /^\s*\S+\.conf\s*$/;
	var dateFormat = /^20\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/;
	
	var emptyFields = (document.create_form.bz_search.value || document.create_form.subset_path.value)
		&& document.create_form.name_of_stats.value 
		&& (document.create_form.existing_list.value || document.create_form.list_name.value) 
		&& document.create_form.path.value 
		&& (document.getElementById('run_now').checked || document.getElementById('run_auto').checked);
	
	var wrongFormatMain = nameFormat.test(document.create_form.name_of_stats.value);
	
	var wrongFormatSubset = document.create_form.subset_path.value
		&& !dateFormat.test(document.create_form.subset_from_date.value);
	
	var wrongFormatProducts = !document.create_form.existing_list.value
		&& document.create_form.list_name.value
		&& (!fileFormat.test(document.create_form.list_name.value) || !document.create_form.add_products.value);
		//to define - checking of products format
	
	
	var alertString = "";
	
	if(!emptyFields){
		if(!document.create_form.path.value){
			alertString += "\"Name and path of configuration file\" field is mandatory." + '\n';
		}
		if(!document.create_form.bz_search.value && !document.create_form.subset_check.checked){
			alertString += "\"Bugzilla search\" field is mandatory." + '\n';
		}
		if(document.create_form.subset_check.checked && !document.create_form.subset_path.value){
			alertString += "\"Subset of\" field is mandatory." + '\n';
		}
		if(document.create_form.subset_check.checked && !document.create_form.subset_from_date.value){
			alertString += "\"Retrieve history from date\" field is mandatory." + '\n';
		}
		if(!document.create_form.name_of_stats.value){
			alertString += "\"Name of statistics\" field is mandatory" + '\n';
		}
		if(!document.create_form.existing_list.value
			&& !document.create_form.list_name.value){
			alertString += "Please specify \"List of products\" file or create new list of products." + '\n';
		}
		if(!document.getElementById('run_now').checked
			&& !document.getElementById('run_auto').checked){
			alertString += "Please \"Define run method\"." + '\n';
		}
		alert (alertString);
	}
	else if(!wrongFormatMain){
		if(!nameFormat.test(document.create_form.name_of_stats.value)){
			alertString += "\"Name of statistics\" field cannot contain white spaces." + '\n';
		}
		alert (alertString);
	}
	else if(wrongFormatSubset){
		alertString += "\"Retrieve history from date\" field: date  format is wrong (YYYY-MM-DD)." + '\n';
		alert (alertString);
	}
	else if(wrongFormatProducts){
		if(!fileFormat.test(document.create_form.list_name.value)){
			alertString += "\"Products list file name\" must have a format: file_name.conf" + '\n';
		}
		if(!document.create_form.add_products.value){
			alertString += "\"Specify products\" field is mandatory" + '\n';
		}
		alert (alertString);
	}
	else{
		document.create_form.submit();
		showSpinner();
	}
}
</script>