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

session_start();

if(!isSet($_SESSION['logged in'])) {
	$_SESSION['notification'] = "BAM Manager";
	include('form.php');
	exit();
}

require('lib/functions.php');
require('lib/user_settings.php');

$selected_tab = '';

if (isset($_GET['tab']) && $_GET['tab'] != '') 
{
	$selected_tab = $_GET['tab'];
}

?>
<html>
<head>
	<title>BAM Manager</title>
	<link rel="Stylesheet" type="text/css" href="style/style.css" />
	<link href="style/jqueryFileTree.css" rel="stylesheet" type="text/css" media="screen" />
	<script type="text/javascript" src="lib/scripts.js"></script>
	<script src="lib/file_tree/jquery.js" type="text/javascript"></script>
	<script src="lib/file_tree/jquery.easing.js" type="text/javascript"></script>
	<script src="lib/file_tree/jqueryFileTree.js" type="text/javascript"></script>
</head>
<body>
	<div id="content">
		<div id="log_details">
		<p style="text-align: right;">You are logged in as: '<?php echo $_SESSION['logged in'] ?>' <a href="logout.php">Log out</a> or <a href="change_password.php">Change password</a></p>
		</div>
	
		<ul class="main_menu" >
			<li class=<?php if ($selected_tab == 'create_new_stats' || $selected_tab == '') echo 'active';?>><a href='?tab=create_new_stats'>Create new statistic</a></li>
			<li class=<?php if ($selected_tab == 'edit_existing_statistics') echo 'active';?>><a href='?tab=edit_existing_statistics'>Edit existing statistics</a></li>
			<li class=<?php if ($selected_tab == 'list_of_products') echo 'active';?>><a href='?tab=list_of_products'>Lists of products</a></li>
			<li class=<?php if ($selected_tab == 'common_parameters') echo 'active';?>><a href='?tab=common_parameters'>Common parameters</a></li>
			<li class=<?php if ($selected_tab == 'refresh_statistics') echo 'active';?>><a href='?tab=refresh_statistics'>Refresh statistics</a></li>
			<?php
			$login = $_SESSION['logged in'];
			
			if ($login == 'admin') {
				echo '
			<li class='; if ($selected_tab == 'settings') echo 'active'; echo '><a href=\'?tab=settings\'>Settings</a></li>
			<li class='; if ($selected_tab == 'users') echo 'active'; echo '><a href=\'?tab=users\'>Users</a></li>
				';
			}
			?>
		</ul>
	<div id="container">
		<div id="container_body">
			<div id="spinner_bg">
				<img src="lib/loader.gif" id="spinner"/>
				<div id="progress_info">
					Work in progress ...
				</div>
			</div>
			<script type="text/javascript">
			function showSpinner() {
				document.getElementById('spinner').style.display = "block";
				document.getElementById('spinner_bg').style.display = "block";
				document.getElementById('interior').style.display = "none";
				document.getElementById('submit_button_table').style.display = "none";
			}
			</script>
			<?php 	
			if ($selected_tab != '') 
			{  
				$subpage_file = $selected_tab . '.php';
				$subpage = loadFileContentToVariable($subpage_file);
				eval("?>".$subpage."<?php "); 
			} 
			else
			{
				$subpage = loadFileContentToVariable('create_new_stats.php');
				eval("?>".$subpage."<?php ");
			}
			?>
		</div>
	</div>
	</div>
</body>
</html>