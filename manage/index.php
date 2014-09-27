<?php
	require_once("../account_funcs.php");
	require_once("../db_conn.php");
	session_start();
	
	if(isset($_GET["logout"])){
		logoff();
	}
	
	//check authentication if set
	if(isset($_POST["login"])){
		login();
	}
	
	if(!isset($_SESSION["remote"])||$_SESSION["remote"]!=$_SERVER["REMOTE_ADDR"]){
		logoff("?stolen");
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php print($_SESSION["username"]); ?> - #kitinfo Unified Account Management</title>
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
				
				<a href="#tokens" class="item">
					Tokens
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
					Password change, account deletion, etc
				</div>
				<div class="section" id="account-attributes">
					<h2><a name="attributes">Account attributes</a></h2>
					What the system knows about you
				</div>
				<div class="section" id="account-tokens">
					<h2><a name="tokens">Active tokens</a></h2>
					Active credentials on connected systems
				</div>
				<div class="section" id="account-endpoints">
					<h2><a name="endpoints">My endpoints</a></h2>
					Roll your own service
				</div>
			</div>
			<div id="foot">
				#kitinfo Unified Accounts System
			</div>
		</div>
	</body>
</html>
