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
	<script type="text/javascript" src="lib/file_tree/jquery.js"></script>
	<script type="text/javascript" src="lib/file_tree/jquery.easing.js"></script>
	<script type="text/javascript" src="lib/file_tree/jqueryFileTree.js"></script>
</head>
<body>
	<div id="content">
	
<!-- showTree -->
	<div id="overlay" style="opacity: 0.8; display: none">
			<img src="lib/spinner.gif" id="spinner"/>
			<div id="progress_info">
				Work in progress ...
			</div>
		<script type="text/javascript">
			function showSpinner() {
				document.getElementById('overlay').style.display = "block";
				document.getElementById('spinner').style.display = "block";
				document.getElementById('progress_info').style.display = "block";
			}
		</script>
	</div>
	<!-- Name and path of conf file -->	
	<div id="showFileTree" style="display: none;">
		<fieldset id="interior">
			<legend>Statistics directory: <?php echo $path_to_statistics_directory; ?></legend>
			<div id="fileTree" class="tree"></div>
		</fieldset>
		<div id="typeOverlay" style="opacity: 0.8; display: none"></div>
		<!-- New Folder -->	
		<div id="newFolder" style="display: none;">
			<fieldset id="interior">
				<legend>New Folder</legend>
					<table width="100%">
						<tr>
							<td align="right">Folder name:</td>
							<td>	
								<input id="folderName" type="text" value="" />
							</td>
						</tr>
						<tr>
							<td align="right">File name:</td>
							<td>	
								<input id="fileName" type="text" value="" />
							</td>
						</tr>							
					</table>
			</fieldset>
			<p align="right">
				<input id="cancel_folder_path" type="button" value="Cancel" onclick="cancelFolderPath()"/>
				<input id="confirm_folder_path" type="button" value="Confirm" onclick="confirmFolderPath()"/>
			</p>
		</div>
		<!-- New File -->	
		<div id="newFile" style="display: none;">
			<fieldset id="interior">
				<legend>New File</legend>
					<table width="100%">
						<tr>
							<td align="right">File name:</td>
							<td>	
								<input id="fileFileName" type="text" value="" />
							</td>
						</tr>							
					</table>
			</fieldset>
			<p align="right">
				<input id="cancel_file_path" type="button" value="Cancel" onclick="cancelFilePath()"/>
				<input id="confirm_file_path" type="button" value="Confirm" onclick="confirmFilePath()"/>
			</p>
		</div>
		<p align="right">
			<input id="cancel_choice" type="button" value="Cancel" onclick="cancelPath()"/>
		</p>
	</div>
	<!-- Products -->	
	<div id="showProductsTree" style="display: none;">
		<fieldset id="interior">
			<legend>Products directory: <?php echo $path_to_products_directory; ?></legend>
			<div id="productsFileTree" class="tree"></div>
		</fieldset>
		<p align="right">
			<input id="cancel_products_path" type="button" value="Cancel" onclick="cancelProductsPath()"/>
			<input disabled id="confirm_products_path" type="button" value="Confirm" onclick="confirmProductsPath()"/>
		</p>
	</div>
	<!-- Subset -->	
	<div id="showSubsetTree" style="display: none;">
		<fieldset id="interior">
			<legend>Subsets directory: <?php echo $path_to_subsets_directory; ?></legend>
			<div id="subsetFileTree" class="tree"></div>
		</fieldset>
		<p align="right">
			<input id="cancel_subset_path" type="button" value="Cancel" onclick="cancelSubsetPath()"/>
			<input disabled id="confirm_subset_path" type="button" value="Confirm" onclick="confirmSubsetPath()"/>
		</p>
	</div>
<!-- showTree -->	

		<div id="log_details">
		<p style="text-align: right;">You are logged in as: '<?php echo $_SESSION['logged in'] ?>' <a href="logout.php">Log out</a> or <a href="change_password.php">Change password</a></p>
		</div>
	
		<ul class="main_menu" >
			<li class=<?php if ($selected_tab == 'create_new_stats' || $selected_tab == '') echo 'active';?>><a href='?tab=create_new_stats'>Create new statistic</a></li>
			<li class=<?php if ($selected_tab == 'remove_statistics') echo 'active';?>><a href='?tab=remove_statistics'>Remove statistics</a></li>
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