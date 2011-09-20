<?php
/*
#===================================================================================
# BAM Manager (Bugzilla Automated Metrics Manager): functions.php
#
# Copyright 2011, Comarch SA
# Maintainers: 	Krystian Jedrzejowski <krystian.jedrzejowski@comarch.com>,
# 				Kamil Marek <kamil.marek@comarch.com>
# Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
#
# Date: Thu Jul 13 11:56:00 EET 2011
#===================================================================================
*/

function loadFileContentToVariable($path_to_file) {
	$file_content = fread(fopen($path_to_file, "r"), filesize($path_to_file));
	return $file_content;
}

function clipPathToFileName($path_to_file, $base_path) {
	$clipped_path_to_file = str_replace($base_path, '',$path_to_file);
	return $clipped_path_to_file;
}

function saveToFile($path_to_file, $content) {
	define('FILE', $path_to_file);
	$file = fopen(FILE, 'w');
	fwrite($file, $content);
	fclose($file);
}

function saveToFileArray($path_to_file, $content) {
	logToSyslog('Function: saveToFileNew, file:' . $path_to_file);
	$content = explode(PHP_EOL, $content);
	define('FILE', $path_to_file);
	$file = fopen(FILE, 'w+');
	foreach ($content as $line) {
		fwrite($file, PHP_EOL . trim($line));
	}
	fclose($file);
}

function displayMessageFieldset($legend, $body, $return_link) {
	echo '
	<fieldset id="message_fieldset"><legend>' . $legend . '</legend>';
	echo '<p>' . $body . '</p>';	
	echo '
		<p><a href="' . $return_link . '">Back</a></p>
	</fieldset>';
}

function displayArray($your_array) {
	foreach ($your_array as $element) {
		echo $element . "<br>";
	}
}

function searchString($file, $string){
	$file_content = fread(fopen($file, "rw"), filesize($file));
	$file_content_array = explode("\n",$file_content);
	
	$search_string;
	foreach($file_content_array  as $i => $value) {
		$file_content_array[$i]=trim($file_content_array[$i]);
		$check_string[$k]=strstr($file_content_array[$i], "=");
		$find[$i]=strpos($file_content_array[$i], "=");
		$check_string[$i]=substr($file_content_array[$i], 0, $find[$i]-1);
		$field_value[$i]=substr($file_content_array[$i], $find[$i]+2);
		
		if($check_string[$i] == $string){
			$search_string = $field_value[$i];
		}
	}
	return $search_string;
}

// debug functions
function logToSyslog($log_info) {
	define('SYSLOG', 'log/syslog');
	$syslog = fopen(SYSLOG, 'a');
	$log_info =  date('Y-m-d-H-i-s') . ': PHP: ' . $log_info . PHP_EOL;
	fwrite($syslog, $log_info);
	fclose($syslog);
}
?>