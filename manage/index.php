<?php
	function logoff($param=""){
		session_destroy();
		header("Location: ../".$param);
		die();
	}

	require_once("../db_conn.php");
	session_start();
	
	if(isset($_GET["logout"])){
		logoff();
	}
	
	//check authentication if set
	if(isset($_POST["login"])){
		if(!isset($_POST["username"])||empty($_POST["username"])){
			logoff("?empty-user");
		}
		
		if(!isset($_POST["pass"])||empty($_POST["pass"])){
			logoff("?empty-pass");
		}
		
		$_SESSION["username"]=htmlentities($_POST["username"]);
		
		$user_data=$db->prepare("SELECT account_id, account_handle, account_password, account_salt FROM accounts WHERE account_handle = :username");
		if(!$user_data->execute(array(
			":username" => $_SESSION["username"]
		))){
			logoff("?error");
		}
		
		$user_data=$user_data->fetch(PDO::FETCH_ASSOC);
		
		if($user_data===FALSE){
			logoff("?nonesuch");
		}
		else{
			$pass=hash("sha256", $user_data["account_salt"].$_POST["pass"]);
			if($pass!=$user_data["account_password"]){
				logoff("?credentials");
			}
		}
		
		$_SESSION["remote"]=$_SERVER["REMOTE_ADDR"];
		$_SESSION["account"]=$user_data["account_id"];
		$_SESSION["username"]=$user_data["account_handle"];
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
		</div>
	</body>
</html>
