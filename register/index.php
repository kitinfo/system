<?php
	
	/**
		SYSTEM account registration procedure
	*/
	
	//Crude Form-Spamming detection
	if(isset($_POST["pass-rep-sp"])&&!empty($_POST["pass-rep-sp"])){
		exit("Spam.");
	}
	
	if(!isset($_POST["register_username"])||empty($_POST["register_username"])){
		exit("Username missing or empty");
	}
	$username=strtolower(htmlentities($_POST["register_username"]));
	
	if(!isset($_POST["register_password"])||empty($_POST["register_password"])){
		exit("Password missing or empty");
	}
	$pass=$_POST["register_password"];
	
	if(!isset($_POST["register_password_repetition"])||$pass!=$_POST["register_password_repetition"]){
		exit("Repetitions dont match");
	}
	
	$salt=hash("sha256", mt_rand());
	$pass=hash("sha256", $salt.$pass);
	
	require_once("../db_conn.php");
	
	$insert_user=$db->prepare("INSERT INTO accounts (account_handle, account_password, account_salt) VALUES (:name, :pass, :salt)");
	if(!$insert_user->execute(
		array(
			":name" => $username,
			":pass" => $pass,
			":salt" => $salt
		)
	)){
		var_dump($db->errorInfo());
		exit("Failed to add user");
	}
	
	$uid=$db->lastInsertId();
	
	$insert_attrib=$db->prepare("INSERT INTO account_attributes (attribute_account, attribute_attribute, attribute_value, attribute_modifiable) VALUES (:uid, :attrib, :value, 0)");
	if(!$insert_attrib->execute(
		array(
			":uid" => $uid,
			":attrib" => 2,
			":value" => time()
		)
	)){
		var_dump($db->errorInfo());
		exit("Failed to insert rtime attribute");
	}
	
	if(!$insert_attrib->execute(
		array(
			":uid" => $uid,
			":attrib" => 8,
			":value" => $username
		)
	)){
		var_dump($db->errorInfo());
		exit("Failed to insert username attribute");
	}
	
	if(!$insert_attrib->execute(
		array(
			":uid" => $uid,
			":attrib" => 9,
			":value" => $uid
		)
	)){
		var_dump($db->errorInfo());
		exit("Failed to insert uid attribute");
	}
	
	require_once("../account_funcs.php");
	session_start();
	
	login($username, $_POST["register_password"], true);
	header("Location: ../manage/");
	die();
?>
