﻿<?php
	
	/**
		SYSTEM management interface
	*/

	require_once("../account_funcs.php");
	require_once("../db_conn.php");
	session_start();
	
	if(isset($_GET["logout"])){
		logoff();
	}
	
	if(isset($_POST["login"])){
		login($_POST["login_username"], $_POST["login_password"], true);
	}
	
	if(!isset($_SESSION["remote"])||$_SESSION["remote"]!=$_SERVER["REMOTE_ADDR"]){
		logoff("?session-stolen");
	}
	
	if(!isset($_SESSION["full_login"])||!$_SESSION["full_login"]){
		logoff("?session-elevation");
	}
	
	if(isset($_POST["terminate"])){
		if(verify_password($_SESSION["account"], $_POST["pass"])){
			if(delete_account($_SESSION["account"])){
				logoff();
			}
		}
	}
	
	if(isset($_POST["change-password"])){
		$_POST["change-password"]=false;
		if(verify_password($_SESSION["account"], $_POST["pw-old"])){
			if($_POST["pw-new"]==$_POST["pw-rep"]){
				if(update_password($_SESSION["account"], $_POST["pw-new"])){
					$_POST["change-password"]=true;
				}
			}
		}
	}
	
	if(isset($_GET["del-attribute"])&&isset($_GET["id"])){
		if(delete_attribute($_SESSION["account"], intval($_GET["id"]))){
			//update attribute data
			$_SESSION["attributes"]=get_attributes($_SESSION["account"]);
		}
	}
	
	if(isset($_POST["add-attribute"])){
		if(add_attribute($_SESSION["account"], intval($_POST["attribute"]), $_POST["attribute_value"])){
			//update attribute data
			$_SESSION["attributes"]=get_attributes($_SESSION["account"]);
		}
	}
	
	if(isset($_GET["rev-assoc"])&&isset($_GET["id"])){
		if(revoke_association($_SESSION["account"], intval($_GET["id"]))){
			$_SESSION["associations"]=get_associations($_SESSION["account"]);
		}
	}
	
	if(isset($_GET["del-remote"])&&isset($_GET["id"])){
		if(delete_remote($_SESSION["account"], intval($_GET["id"]))){
			$_SESSION["remotes"]=get_remotes($_SESSION["account"]);
		}
	}
	
	if(isset($_POST["add-remote"])){
		$_POST["add-remote"]=add_remote($_SESSION["account"], $_POST["remote_handle"], $_POST["remote_endpoint"], $_POST["remote_redirect"]);
		if($_POST["add-remote"]!==false){
			$_SESSION["remotes"]=get_remotes($_SESSION["account"]);
		}
	}
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php print($_SESSION["username"]); ?> - The SYSTEM</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="icon" href="favicon.ico" type="image/x-icon" />
		<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
		<link rel="stylesheet" type="text/css" href="../static/accounts.css" />
		<meta name="robots" content="noindex,nofollow" />
	</head>
	<body>
		<div id="center-box">
			<div id="headmenu">
				<a href="#account" class="item">
					Account settings
				</a>
				
				<a href="#attributes" class="item">
					Attributes
				</a>
				
				<a href="#associations" class="item">
					Associations
				</a>
				
				<a href="#endpoints" class="item">
					My Endpoints
				</a>
				
				<a href="?logout" class="item">
					Logoff
				</a>
			</div>
			<div id="head">
				<span style="float:right;margin-right:1em;">
					Welcome, <em><?php print($_SESSION["username"]); ?></em>
				</span>
			</div>
			<div id="section-wrapper">
				<div class="section" id="account-settings">
					<h2><a name="account">Account settings</a></h2>
					<h3>Manage your relationship to the SYSTEM</h3>
					<div class="inline-box">
						<strong>Change my Password</strong>
						<form action="?" method="POST">
							<input type="password" id="pw-old" name="pw-old" />
							<label for="pw-old">Current Password</label> 
							<br/>
							<input type="password" id="pw-new" name="pw-new" />
							<label for="pw-new">New Password</label>
							<br/>
							<input type="password" id="pass-rep" name="pw-rep" />
							<label for="pass-rep">Repeat</label> 
							<br/>
							<input type="submit" value="Continue" name="change-password"/>
							<?php
								if(isset($_POST["change-password"])){
									if($_POST["change-password"]){
										?>
											<em>OK</em>
										<?php
									}
									else{
										?>
											<em>Failed</em>
										<?php
									}
								}
							?>
						</form>
					</div>
					
					<div class="inline-box">
						<strong>Delete my account</strong>
						<form action="?" method="POST">
							<input type="password" id="pass-del" name="pass" />
							<label for="pass-del">Password</label> 
							<br/>
							<input type="submit" value="Terminate" name="terminate"/>
							<?php
								if(isset($_POST["terminate"])){
									?>
										<em>Incorrect password.</em>
									<?php
								}
							?>
						</form>
					</div>
				</div>
				<div class="section" id="account-attributes">
					<h2><a name="attributes">Account attributes</a></h2>
					<h3>What the SYSTEM knows about you</h3>
					<form action="?" method="POST">
						<table>
							<tr>
								<th>Attribute</th>
								<th>Value</th>
								<th>Options</th>
							</tr>
							<?php
								foreach($_SESSION["attributes"]["active"] as $attr){
									?>
										<tr>
											<td><?php print($attr["attribute_displayname"]); ?></td>
											<td><?php print($attr["attribute_value"]); ?></td>
											<td><?php if($attr["attribute_modifiable"]){print('<a href="?del-attribute&id='.$attr["attribute_id"].'">[Del]</a>');} ?></td>
										</tr>
									<?php
								}
							?>
							<tr>
								<td>
									<select name="attribute">
										<?php
											foreach($_SESSION["attributes"]["unused"] as $attr){
												print('<option value="'.$attr["attribute_id"].'">'.$attr["attribute_displayname"].'</option>');
											}
										?>
									</select>
								</td>
								<td><input type="text" name="attribute_value" /></td>
								<td><input type="submit" value="Add" name="add-attribute" /></td>
							</tr>
						</table>
					</form>
				</div>
				<div class="section" id="account-associations">
					<h2><a name="associations">Active associations</a></h2>
					<h3>Where the SYSTEM has authenticated you</h3>
					<table>
						<tr>
							<th>Service</th>
							<th>Issued</th>
							<th>Lifetime</th>
							<th>Options</th>
						</tr>
						<?php
							foreach($_SESSION["associations"] as $assoc){
								?>
									<tr>
										<td><?php print($assoc["remote_handle"]); ?></td>
										<td><?php print($assoc["association_issued"]); ?></td>
										<td><?php print($assoc["association_lifetime"]); ?></td>
										<td><a href="?rev-assoc&id=<?php print($assoc["association_id"]); ?>">[Rev]</a></td>
									</tr>
								<?php
							}
						?>
						
					</table>
				</div>
				<div class="section" id="account-endpoints">
					<h2><a name="endpoints">My endpoints</a></h2>
					<h3>Roll your own service connected to the SYSTEM</h3>
					<?php
						if(isset($_POST["add-remote"])){
							if($_POST["add-remote"]===false){
								?>
									Failed to add remote authenticator.
								<?php
							}
							else{
								?>
									<p>
									Remote added with user <em><?php print($_POST["add-remote"]["user"]); ?></em>
									and password <em><?php print($_POST["add-remote"]["pass"]); ?></em>
									</p>
								<?php
							}
						}
					?>
					<form action="?" method="POST">
						<table>
							<tr>
								<th>Handle</th>
								<th>Endpoint</th>
								<th>Redirect</th>
								<th>Protocol</th>
								<th>Options</th>
							</tr>
							<?php
								foreach($_SESSION["remotes"] as $remote){
									?>
										<tr>
											<td><?php print($remote["remote_handle"]); ?></td>
											<td><?php print(htmlentities($remote["remote_endpoint"])); ?></td>
											<td><?php print(htmlentities($remote["remote_redirect"])); ?></td>
											<td><?php print($remote["remote_protocol"]); ?></td>
											<td><a href="?del-remote&id=<?php print($remote["remote_id"]); ?>">[Del]</a></td>
										</tr>
									<?php
								}
							?>
							<tr>
								<td><input type="text" name="remote_handle" /></td>
								<td><input type="text" name="remote_endpoint" /></td>
								<td><input type="text" name="remote_redirect" /></td>
								<td>systemv1</td>
								<td><input type="submit" value="Create" name="add-remote" /></td>
							</tr>
						</table>
					</form>
				</div>
			</div>
			<div id="foot">
				The SYSTEM - Unified Accounts
				<span style="float:right;">
					<a href="https://github.com/kitinfo/system">[source]</a>
					<a href="http://www.kopimi.com/kopimi/"><img src="../static/kopimi.png" alt="kopimi"/></a>
					<a href="http://wtfpl.net/"><img src="../static/wtfpl.png" alt="wtfpl"/></a>
				</span>
			</div>
		</div>
	</body>
</html>
