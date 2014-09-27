<?php
	function logoff($param=""){
		session_destroy();
		header("Location: ../".$param);
		die();
	}
	
	function login(){
		global $_POST;
		global $_SESSION;
		global $db;
		
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
?>