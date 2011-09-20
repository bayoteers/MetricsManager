<?php

	echo '
		<div id="list_false">
			<table class="create">	
				<tr>
					<td class="left">
						<a href="#" onclick="handleWantsList(true)">Create new list of products</a>
					</td>
					<td></td>
				</tr>
			</table>
		</div>	
			
		<div id="list_true" style="display: none;">
			<table class="create">
				<tr>
					<td class="left">
						<a href="#" onclick="handleWantsList(false)">Dismiss</a>
					</td>
				<tr>
					<td class="left">
						<p>*Products list file name:</p>
					</td>
					<td>
						<input type="text" style="width:650px; height:22px;" name="list_name" id="list_name">
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
					<td class="left" style="vertical-align:top;">
						*Specify products:
					</td>
					<td>
						<textarea style="width:650px; height:250px;" name="add_products" id="add_products"></textarea>
					</td>
					<td class="help" vlign="top">
						<ul>?
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
							</li>
						</ul>
					</td>				
				</tr>
			</table>
		</div>
				<br>';
?>
<script type="text/javascript">
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
  
</script>