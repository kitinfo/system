<?php
	
	/**
		SYSTEM account registration procedure
	*/
	
	//Crude Form-Spamming detection
	if(isset($_POST["pass-rep-sp"])&&!empty($_POST["pass-rep-sp"])){
		exit("Spam.");
	}
	
	if(!isset($_POST["username"])||empty($_POST["username"])){
		exit("Username missing or empty");
	}
	$username=strtolower(htmlentities($_POST["username"]));
	
	if(!isset($_POST["pass"])||empty($_POST["pass"])){
		exit("Password missing or empty");
	}
	$pass=$_POST["pass"];
	
	if(!isset($_POST["pass-rep"])||$pass!=$_POST["pass-rep"]){
		exit("Repetitions dont match");
	}
	
	$salt=hash("sha256", mt_rand());
	$pass=hash("sha256", $salt.$pass);
	
	require_once("../db_conn.php");
	
	$insert_statement=$db->prepare("INSERT INTO accounts (account_handle, account_password, account_salt) VALUES (:name, :pass, :salt)");
	if(!$insert_statement->execute(
		array(
			":name" => $username,
			":pass" => $pass,
			":salt" => $salt
		)
	)){
		var_dump($db->errorInfo());
		exit("Failed to insert");
	}
	
	$insert_statement=$db->prepare("INSERT INTO account_attributes (attribute_account, attribute_attribute, attribute_value, attribute_modifiable) VALUES (:uid, 2, :timestamp, 0)");
	if(!$insert_statement->execute(
		array(
			":uid" => $db->lastInsertId(),
			":timestamp" => time()
		)
	)){
		var_dump($db->errorInfo());
		exit("Failed to insert");
	}
	
	
	require_once("../account_funcs.php");
	session_start();
	
	login($username, $_POST["pass"], true);
	header("Location: ../manage/");
	die();
?>
