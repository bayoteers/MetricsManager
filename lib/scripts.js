/*
 * ===================================================================================
 * BAM Manager (Bugzilla Automated Metrics Manager): scripts.js
 *
 * Copyright 2011, Comarch SA
 * Maintainers: 	Krystian Jedrzejowski <krystian.jedrzejowski@comarch.com>,
 * 					Kamil Marek <kamil.marek@comarch.com>
 * Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 *
 * Date: Thu Sep 15 13:30:00 EET 2011
 * ===================================================================================
*/

function handleWantsList(wants_list){
	if (wants_list){
		document.getElementById('list_false').style.display = 'none';
		document.getElementById('list_true').style.display = 'block';
		document.create_form.existing_list.disabled = true;
	}
	else{
		document.getElementById('list_false').style.display = 'block';
		document.getElementById('list_true').style.display = 'none';
		document.getElementById('list_name').value = '';
		document.getElementById('add_products').value= '';
		document.create_form.existing_list.disabled = false;
	}
}

function enableSettings(enableCondition){
	if(enableCondition) {
		document.BAM_settings.common_parameters_file.disabled = false;
		document.BAM_settings.crontab_file.disabled = false;
		document.BAM_settings.fetch_statistics_from_bugzilla_file.disabled = false;
		document.BAM_settings.fetch_statistics_file.disabled = false;
		document.BAM_settings.path_to_statistics_directory.disabled = false;
		document.BAM_settings.path_to_subsets_directory.disabled = false;
		document.BAM_settings.path_to_products_directory.disabled = false;
		document.BAM_settings.statistics_user.disabled = false;
		document.BAM_settings.edit_settings.disabled = false;
		document.BAM_settings.enable.style.display = 'none';
		document.BAM_settings.disable.style.display = 'inline';
		document.BAM_settings.edit_settings.style.display = 'inline';
		
		document.getElementById('edit_cron_enabled').style.display = 'none';
		document.getElementById('edit_cron_disabled').style.display = 'inline';
	}
}
//Tab - create statistics======================================================================================
function createFile(file) {
	var folderFormat = /^newFolder/;

	if (folderFormat.test(file)){
		var basePath =  file.replace("newFolder", "");
		document.getElementById("newFolder").style.display = "block";
		document.getElementById("typeOverlay").style.display = "block";
	}
	else {
		var basePath =  file.replace("newFile", "");
		document.getElementById("newFile").style.display = "block";
		document.getElementById("typeOverlay").style.display = "block";
	}
	
	document.create_form.path.value = basePath;
}
/***** Name and path of conf file tree *****/	
function showFileTree() {
	document.getElementById("overlay").style.display = "block";
	document.getElementById("showFileTree").style.display = "block";
}
function cancelPath() {
	document.getElementById("overlay").style.display = "none";
	document.getElementById("showFileTree").style.display = "none";
	document.create_form.path.value = "";
	document.getElementById('inactive_path').innerHTML = "";
	document.getElementById('inactive_path').style.border = "none";
}
function cancelFolderPath() {
	document.getElementById("newFolder").style.display = "none";
	document.getElementById("typeOverlay").style.display = "none";
	document.getElementById("folderName").value = "";
	document.getElementById("fileName").value = "";
	document.getElementById('inactive_path').innerHTML = "";
	document.getElementById('inactive_path').style.border = "none";
}
function cancelFilePath() {
	document.getElementById("newFile").style.display = "none";
	document.getElementById("typeOverlay").style.display = "none";
	document.getElementById("fileFileName").value = "";
	document.getElementById('inactive_path').innerHTML = "";
	document.getElementById('inactive_path').style.border = "none";
}
function confirmFolderPath() {
	var folderName = document.getElementById("folderName").value.replace(/^\s+|\s+$/g, '');
	var fileName = document.getElementById("fileName").value.replace(/^\s+|\s+$/g, '');
	
	var folderFormat = /^\S+$/;
	var fileFormat = /^\S+\.conf$/;
	
	var wrongInputFormat = folderFormat.test(folderName) && fileFormat.test(fileName);

	var emptyValues = folderName && fileName;
	var alertStringValues = "";
	
	if (!emptyValues) {
		if (!folderName) {
			alertStringValues += "Folder name field cannot be empty." + '\n';
		}
		if (!fileName) {
			alertStringValues += "File name field cannot be empty." + '\n';
		}
		alert(alertStringValues);
	}
	else if(!wrongInputFormat) {
		if (!folderFormat.test(folderName)){
			alertStringValues += "Folder name cannot contain white spaces." + '\n';
		}
		if (!fileFormat.test(fileName)){
			alertStringValues += "File name must have a format: file_name.conf" + '\n';
		}		
		alert(alertStringValues);	
	}
		

	if(emptyValues && wrongInputFormat) {
		document.getElementById("newFolder").style.display = "none";
		document.getElementById("typeOverlay").style.display = "none";
		document.getElementById("showFileTree").style.display = "none";
		document.getElementById("overlay").style.display = "none";
		
		document.create_form.path.value = document.create_form.path.value+folderName+'/'+fileName;
		document.getElementById('inactive_path').innerHTML = "path: "+document.create_form.path.value;
		document.getElementById('inactive_path').style.border = "1px solid #68b3e3";
	}
}
function confirmFilePath() {
	var fileName = document.getElementById("fileFileName").value.replace(/^\s+|\s+$/g, '');
	
	var fileFormat = /^\S+\.conf$/;
	
	if (!fileName) {
		alert("File name field cannot be empty.");
	}
	else if(!fileFormat.test(fileName)) {	
		alert("File name must have a format: file_name.conf");	
	}


	if(fileName && fileFormat.test(fileName)) {
		document.getElementById("newFile").style.display = "none";
		document.getElementById("typeOverlay").style.display = "none";
		document.getElementById("showFileTree").style.display = "none";
		document.getElementById("overlay").style.display = "none";
		
		document.create_form.path.value = document.create_form.path.value+fileName;
		document.getElementById('inactive_path').innerHTML = "path: "+document.create_form.path.value;
		document.getElementById('inactive_path').style.border = "1px solid #68b3e3";
	}
}

/***** Products file tree *****/
function chooseProducts(file) {
	var prevValue = document.create_form.existing_list.value;
    document.create_form.existing_list.value = file;

	document.getElementById(file).style.background = '#68b3e3';
	if (prevValue) {
		if (prevValue != document.create_form.existing_list.value){
			document.getElementById(prevValue).style.background = 'none';
		}
		else {
			document.create_form.existing_list.value = "";
			document.getElementById(file).style.background = 'none';	
		}
	}
	if (document.create_form.existing_list.value){
		document.getElementById("confirm_products_path").disabled = false;
	}
	else {
		document.getElementById("confirm_products_path").disabled = true;
	}
}
function showProductsTree() {
	document.getElementById("overlay").style.display = "block";
	document.getElementById("showProductsTree").style.display = "block";
	document.getElementById("list_false").style.display = "none";
}
function cancelProductsPath() {
	if (document.getElementById(document.create_form.existing_list.value) && 
		document.getElementById(document.create_form.existing_list.value).style.background != 'none') {
			document.getElementById(document.create_form.existing_list.value).style.background = 'none';
	}
	document.create_form.existing_list.value = "";
	document.getElementById("overlay").style.display = "none";
	document.getElementById("showProductsTree").style.display = "none";
	document.getElementById('inactive_products').innerHTML = "";
	document.getElementById('inactive_products').style.border = "none";
	if (!document.create_form.existing_list.value){
		document.getElementById("confirm_products_path").disabled = true;
	}
	document.getElementById("list_false").style.display = "block";
}
function confirmProductsPath() {
	document.getElementById("overlay").style.display = "none";
	document.getElementById("showProductsTree").style.display = "none";
	document.getElementById('inactive_products').innerHTML = "chosen file: "+document.create_form.existing_list.value;
	document.getElementById('inactive_products').style.border = "1px solid #68b3e3";
}

/***** Subset file tree *****/
function chooseSubset(file) {
	var prevValue = document.create_form.subset_path.value;
    document.create_form.subset_path.value = file;
	
	document.getElementById('subsets' + file).style.background = '#68b3e3';
	if (prevValue) {
		if (prevValue != document.create_form.subset_path.value){
			document.getElementById('subsets' + prevValue).style.background = 'none';
		}
		else {
			document.create_form.subset_path.value = "";
			document.getElementById('subsets' + file).style.background = 'none';	
		}
	}
	if (document.create_form.subset_path.value){
		document.getElementById("confirm_subset_path").disabled = false;
	}
	else {
		document.getElementById("confirm_subset_path").disabled = true;
	}
}

function showSubsetTree() {
	document.getElementById("overlay").style.display = "block";
	document.getElementById("showSubsetTree").style.display = "block";
}
function cancelSubsetPath() {
	if (document.getElementById('subsets' + document.create_form.subset_path.value) &&
		document.getElementById('subsets' + document.create_form.subset_path.value).style.background != 'none') {
			document.getElementById('subsets' + document.create_form.subset_path.value).style.background = 'none';
	}
	document.create_form.subset_path.value = "";
	document.getElementById("overlay").style.display = "none";
	document.getElementById("showSubsetTree").style.display = "none";
	document.getElementById('inactive_subset').innerHTML = "";
	document.getElementById('inactive_subset').style.border = "none";
	if (!document.create_form.subset_path.value){
		document.getElementById("confirm_subset_path").disabled = true;
	}
}
function confirmSubsetPath() {
	document.getElementById("overlay").style.display = "none";
	document.getElementById("showSubsetTree").style.display = "none";
	document.getElementById('inactive_subset').innerHTML = "chosen file: "+document.create_form.subset_path.value;
	document.getElementById('inactive_subset').style.border = "1px solid #68b3e3";
}
	
function enableField(){
	if(document.create_form.subset_check.checked){
		document.create_form.subset_path.value = document.create_form.subset_path_hidden.value;
		document.getElementById("subsetBrowse").disabled = false;
		document.create_form.subset_from_date.disabled = false;
		document.create_form.subset_from_date.value = document.create_form.subset_from_date_hidden.value;
		document.getElementById('run_now').checked = true;
		document.getElementById('run_auto').disabled = true;
		document.create_form.bz_search_hidden.value = document.create_form.bz_search.value;
		document.create_form.bz_search.value = "";
		document.create_form.bz_search.placeholder = "Bugzilla search parameter will be taken from subset configuration file";
		document.create_form.bz_search.disabled = true;
	}
	else{
		document.create_form.subset_path_hidden.value = document.create_form.subset_path.value;
		document.create_form.subset_path.value = "";
		document.create_form.subset_from_date_hidden.value = document.create_form.subset_from_date.value;
		document.getElementById("subsetBrowse").disabled = true;
		document.create_form.subset_from_date.value = "";
		document.create_form.subset_from_date.disabled = true;
		document.getElementById('run_now').checked = false;
		document.getElementById('run_auto').disabled = false;
		document.create_form.bz_search.value = document.create_form.bz_search_hidden.value;
		document.create_form.bz_search.placeholder = "";
		document.create_form.bz_search.disabled = false;
	}
}

/*****Rest functions******/
function uncheck(){
	for (var i=0; i<document.create_form.classification.length; i++){
		document.create_form.classification[i].checked = false;
	}
}

//===============================================================================================================

//Tab - existing statistics======================================================================================
function removeFile(file) {
	var prevValue = document.form_existing_statisctics.existing_statistics.value;
    document.form_existing_statisctics.existing_statistics.value = file;
	
	document.getElementById(file).style.background = '#68b3e3';
	if (prevValue){
		if (prevValue != document.form_existing_statisctics.existing_statistics.value){
			document.getElementById(prevValue).style.background = 'none';
		}
		else {
			document.form_existing_statisctics.existing_statistics.value = ""
			document.getElementById(file).style.background = 'none';	
		}
	}

	if (document.form_existing_statisctics.existing_statistics.value){
		document.form_existing_statisctics.remove_statistic.disabled = false;
		document.form_existing_statisctics.remove_statistic.focus();
	}
	else {
		document.form_existing_statisctics.remove_statistic.disabled = true;
	}
}
//================================================================================================================

//Tab - lists of products======================================================================================
function chooseElement(file) {
	var prevValue = document.form_existing_lists_of_products.existing_list.value;
    document.form_existing_lists_of_products.existing_list.value = file;
	
	document.getElementById(file).style.background = '#68b3e3';
	enableButtons(true);
	if (prevValue) {
		if (prevValue != document.form_existing_lists_of_products.existing_list.value){
			document.getElementById(prevValue).style.background = 'none';
			enableButtons(true);
		}
		else {
			document.form_existing_lists_of_products.existing_list.value = "";
			document.getElementById(file).style.background = 'none';
			enableButtons(false);			
		}
	}
}

function enableButtons(condition) {
	if (condition) {
		document.form_existing_lists_of_products.edit_list.disabled=false;
		document.form_existing_lists_of_products.remove_list.disabled=false;
		document.form_existing_lists_of_products.edit_list.focus();
	}
	else {
		document.form_existing_lists_of_products.edit_list.disabled=true;
		document.form_existing_lists_of_products.remove_list.disabled=true;
	}
}
//================================================================================================================