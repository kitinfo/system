<?php
	require_once("endpoint_credentials.php");
	require_once("client_db.php");
	
	if($_SERVER["PHP_AUTH_USER"]==$SYSTEM_REMOTE_USER&&$_SERVER["PHP_AUTH_PW"]==$SYSTEM_REMOTE_PASSWORD){
		var_dump($_POST);
		$stmt=$db->prepare("INSERT INTO authentication_hits (hit_token, hit_user) VALUES (:token, :user)");
		if(!$stmt->execute(array(":token"=>$_POST["token"], ":user"=>$_POST["username"]))){
			die("Failed to insert: ".json_encode($db->errorInfo()));
		}
		die("OK, inserted token ".$_POST["token"]);
	}
	die("Failed auth.");
?>