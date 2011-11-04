/*
 * ===================================================================================
 * BAM Manager (Bugzilla Automated Metrics Manager): scripts.js
 *
 * Copyright 2011, Comarch SA
 * Maintainers: 	Krystian Jedrzejowski <krystian.jedrzejowski@comarch.com>,
 * 				Kamil Marek <kamil.marek@comarch.com>
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

function enableButton() {
	var i = 0;
	for ( i = 0; i < document.form_existing_lists_of_products.existing_list.length; i++ ) {
		if( document.form_existing_lists_of_products.existing_list[i].checked == true ) {
			document.form_existing_lists_of_products.edit_list.disabled=false;
			document.form_existing_lists_of_products.remove_list.disabled=false;
			document.form_existing_lists_of_products.edit_list.focus();
		}
	}
}

function enableSettings(enableCondition){
	if(enableCondition) {
		document.BAM_settings.common_parameters_file.disabled = false;
		document.BAM_settings.crontab_file.disabled = false;
		document.BAM_settings.fetch_statistics_from_bugzilla_file.disabled = false;
		document.BAM_settings.fetch_statistics_file.disabled = false;
		document.BAM_settings.path_to_statistics_directory.disabled = false;
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
//Tab - existing statistics======================================================================================
function openFile(file) {
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
		document.form_existing_statisctics.read_statistic_content.disabled = false;
		document.form_existing_statisctics.remove_statistic.disabled = false;
		document.form_existing_statisctics.read_statistic_content.focus();
	}
	else {
		document.form_existing_statisctics.read_statistic_content.disabled = true;
		document.form_existing_statisctics.remove_statistic.disabled = true;
	}
}
function existingSubmitFields() {
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
//================================================================================================================

//Tab - Refresh===================================================================================================
function openFileToRefresh(file) {
	var prevValue = document.refresh_statistics.statistics_to_refresh.value;
    document.refresh_statistics.statistics_to_refresh.value = file;
	
	document.getElementById(file).style.background = '#68b3e3';
	if (prevValue){
		if (prevValue != document.refresh_statistics.statistics_to_refresh.value){
			document.getElementById(prevValue).style.background = 'none';
		}
		else {
			document.refresh_statistics.statistics_to_refresh.value = "";
			document.getElementById(file).style.background = 'none';
		}
	}
/*	
	if (document.form_existing_statisctics.existing_statistics.value){
		document.form_existing_statisctics.read_statistic_content.disabled=false;
		document.form_existing_statisctics.remove_statistic.disabled=false;
		document.form_existing_statisctics.read_statistic_content.focus();
	}
*/
}
//================================================================================================================