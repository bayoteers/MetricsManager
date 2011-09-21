<?php
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['path'])) {
	
	system("sudo lib/libcontentaction.pl --start $login 'Create statistic'");
	
	//New products variables (create_list_of_products.php file)
	$list_name = $_POST['list_name'];
	$add_products = $_POST['add_products'];
	// divide text into lines -> only one line at once can be saved to file using console
	$products_array = explode("\n",$add_products);
	//Common variables
	$path = $_POST['path'];
	$path_of_conf_file = $path_to_statistics_directory . $path;
	$file_check = file_exists($path_of_conf_file);
	$common_params = $common_parameters_file;
	$bz_search = $_POST['bz_search'];
	$name_of_stats = $_POST['name_of_stats'];
	$existing_list = $_POST['existing_list'];
	$subset_from_date = $_POST['subset_from_date'];
	$classification = $_POST['classification'];
	$lunch_method = $_POST['lunch_method'];
	$subset_list = $_POST['subset_list'];

	//Searching name of statistic in subset of file.
	if($subset_list){
		$subset_statistic_name = searchString($subset_list, "STATISTICS");
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
			if($common_params != ''){
				$field_arr[$k] = "COMMON_PARAMS_FILE = ".$common_params;
				$k++;
			}
			if($bz_search != ''){
				$field_arr[$k] = "BUGZILLA_URL_COMMON_PARAMS = ".$bz_search;
				$k++;
			}
			if($name_of_stats != ''){
				$field_arr[$k] = "STATISTICS = ".$name_of_stats;
				$k++;
			}
			if($existing_list){
				$field_arr[$k] = "PRODUCTS_CONFIG_FILE = ".$existing_list;
				$k++;
			}
			else{
				//Creating list of products if chosen
				$list_temp_file = 'tmp/' . rand();
				saveToFile($list_temp_file, $add_products);
				system("sudo lib/libcontentaction.pl --move $list_temp_file $list_path");
				
				$field_arr[$k] = "PRODUCTS_CONFIG_FILE = ".$list_path;
				$k++;
			}
			if($subset_list != ''){
				$field_arr[$k] = "SUBSET_OF = ".$subset_statistic_name;
				$k++;
			}
			if($classification != ''){
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
			if($lunch_method == 'run_now' && !$subset_list){
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
								http://milosz-pc.research.nokia.com/bugzilla_statistics/?s='.$name_of_stats.'</a>
							Check <a href="#" onclick="handleWantsList(false)">syslog</a> for details.
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
			elseif($lunch_method == 'run_now' && $subset_list){
				if (!$subset_from_date){
					$error = 'Please fill in "Retrieve history from date" field.<br>';
	echo'
					<fieldset id="interior" style="width:700px; margin: auto; padding: 20px;">
						<legend>
							Error
						</legend>'.$error.'
					</fieldset>
	';
				}
				else{
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
								http://milosz-pc.research.nokia.com/bugzilla_statistics/?s='.$name_of_stats.'
							</a>
							Check <a href="#" onclick="handleWantsList(false)">syslog</a> for details.
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
							<td class="left">Common Parameters File:</td>
							<td><input type="radio" checked name="common_params" value="/usr/local/etc/bugzilla_statistics/common_parameters.conf" onChange="javascript:document.create_form.custom_params.disabled=true"> Default (/usr/local/etc/bugzilla_statistics/common_parameters.conf)<br>
								<input type="radio" name="common_params" value="" onChange="javascript:document.create_form.custom_params.disabled=false"> Custom <input disabled size="50" type="text" name="custom_params" value="" placeholder="DO NOT USE - under construction"/><br>
							</td>
						</tr>
					</table><br>
				<hr>
		-->
					<table class="create" border="0"><br>
						<tr>
							<td class="left">*Name of configuration file:</td>
							<td class="center_create"><input class="input_text" type="text" name="path" value="" /></td>
						<td class="help">
								<ul>?
									<li>
										Please specify name of configuration file ended with ".conf" <br>To avoid problems please name file similar as name of statistic e.g. harmattan_PR12_-_OS_-_adaptation.conf
									</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td class="left">*Bugzilla search:</td>
							<td class="center_create"><input class="input_text"  type="text" name="bz_search"" /></td>				
							<td class="help">
								<ul>?
									<li>
										Please provide bugzilla search parameters:<br> e.g. "bug_severity=blocker,critical,major,normal,minor,task&classification=Harmattan"
									</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td class="left">*Name of statistic:</td>
							<td class="center_create"><input class="input_text"  type="text" name="name_of_stats"/></td>
							<td class="help">
								<ul>?
									<li>
										Please specify name of statistic. Name has an affect on displaying statistics in subgroups.<br>Signs "_-_" divides into a subgroups e.g. Harmattan_with_PR1.2_flag_-_OS_-_Adaptation
									</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td class="left">*List of products:</td>
							<td class="center_create">
	';
	echo'
									<select class="input_text" name="existing_list" onchange="disLink();">
										<option name="existing_list"></option>';
										$file_list = glob($path_to_products_directory . '*.conf');
										foreach ($file_list as $file){
											echo '<option name="existing_list" value="' . $file . '"> ' . $file . '</option>
											';
										}
	echo'
									</select> 
							</td>
							<td class="help">
								<ul>?
									<li>
										Please select existing list of products or create new list by click on link "Create new list of products".<br> To check or modify content of existing lists go to tab "List of products"
									</li>
								</ul>
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
							<td rowspan="2" style="width:75px" align="right"><input type="checkbox" name="subset_check" value="custom" onChange="enableField()"></td>
							<td style="width:200px" align="right">Subset of:</td>
	';		
	echo'
							<td><select class="input_text" name="subset_list" disabled>
								<option name="subset_list" id="subset_none" value=""></option>
	';
								$searchString = 'SUBSET_OF';
								function searchTxt($file){
									global $searchString;
									if(strpos(file_get_contents($file), $searchString) == false){
										echo '<option name="subset_list" value="' . $file . '">' . $file . '</option>
										';
									}
								}
								$dir = opendir($path_to_statistics_directory);
								while($f = readdir($dir))
								{
								  if(!is_dir($path_to_statistics_directory.$f))
								  {
									 searchTxt($path_to_statistics_directory.$f);
								   }
								}
								closedir($dir);
	echo'
								</select>
							</td>
							<td class="help">
								<ul>?
									<li>
										Help text
									</li>
								</ul>
							</td>
						</tr>
						<tr>
							<td style="width:150px" align="right">
								Retrieve history from date:
							</td>
							<td>
								<input class="input_text" disabled type="text" name="subset_from_date" placeholder="YYYY-MM-DD"/>
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
							<td class="left">Incomplete classification:<br><a href="#" class="classification"  onClick="uncheck()" style="margin-left:20px;">>Uncheck all<</a></td>
							<td><input type="radio" name="classification" value="true"> True<br>
							<input type="radio" name="classification" value="false"> False</td>
						</tr>
					</table><br>
				<hr>
					<table class="create"><br>
						<tr>
							<td class="left">*Define run method:</td>
							<td><input type="radio" id="run_now" name="lunch_method" value="run_now"> Run statistics now<br>
							<input type="radio" id="run_auto" name="lunch_method" value="run_auto"> Wait for automatic run (time of automatic run defined in settings)</td>
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
	
function enableField(){
	if(document.create_form.subset_check.checked){
		document.create_form.subset_list.disabled = false;
		document.create_form.subset_from_date.disabled = false;
		document.getElementById('run_now').checked = true;
		document.getElementById('run_auto').disabled = true;
	}
	else{
		document.create_form.subset_list.disabled = true;
		document.getElementById("subset_none").value = "";
		document.create_form.subset_from_date.disabled = true;
		document.getElementById('run_now').checked = false;
		document.getElementById('run_auto').disabled = false;
	}
}
function uncheck(){
	for (var i=0; i<document.create_form.classification.length; i++){
		document.create_form.classification[i].checked = false;
	}
}
function disLink(){
	if(document.create_form.existing_list.value != ""){
		document.getElementById("list_false").style.display="none";
	}
	else{
		document.getElementById("list_false").style.display="inline";
	}
}
function createCheckMandatoryFields(){
	var emptyFields = document.create_form.bz_search.value 
		&& document.create_form.name_of_stats.value 
		&& (document.create_form.existing_list.value || document.create_form.list_name.value) 
		&& document.create_form.path.value 
		&& (document.getElementById('run_now').checked || document.getElementById('run_auto').checked);
	
	var alertString = "";
		
	if (!emptyFields){
		if (!document.create_form.path.value){
			alertString += "Field: \"Name and path of configuration file\" is mandatory" + '\n';
		}
		if (!document.create_form.bz_search.value){
			alertString += "Field: \"Bugzilla search\" is mandatory" + '\n';
		}
		if (!document.create_form.name_of_stats.value){
			alertString += "Field: \"Name of statistics\" is mandatory" + '\n';
		}
		if (!(document.create_form.existing_list.value || document.create_form.list_name.value) ){
			alertString += "Please specify \"List of products\" file or create new list of products" + '\n';
		}
		if (!(document.getElementById('run_now').checked || document.getElementById('run_auto').checked)){
			alertString += "Please \"Define run method\"" + '\n';
		}
		alert (alertString);
	}
	else{
		document.create_form.submit();
		showSpinner();
	}
}
</script>