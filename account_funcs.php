<?php
	function logoff($param=""){
		session_destroy();
		header("Location: ../".$param);
		die();
	}
	
	function verify_password($user_id, $pass){
		global $db;
		
		$user_data=$db->prepare("SELECT account_password, account_salt FROM accounts WHERE account_id = :uid");
		
		if(!$user_data->execute(array(":uid"=>$user_id))){
			return false;
		}
		
		$user_data=$user_data->fetch(PDO::FETCH_ASSOC);
		
		if($user_data===FALSE){
			return false;
		}
		
		$pass=hash("sha256",$user_data["account_salt"].$pass);
		return $pass==$user_data["account_password"];
	}
	
	function get_attributes($user_id){
		global $db;
		
		$attribute_data=$db->prepare("
		SELECT attribute_id,
		       attribute_name,
		       attribute_displayname,
		       attribute_value,
		       attribute_modifiable,
		       attribute_type,
		       attribute_desc
		FROM account_attributes
		JOIN attributes
			ON attribute_attribute = attribute_id
		WHERE attribute_account = :uid
		");
		
		$attributes_unused=$db->prepare("
			SELECT attribute_id, 
				attribute_name, 
				attribute_type, 
				attribute_displayname
			FROM attributes
			WHERE attribute_id NOT IN ( 
				SELECT attribute_attribute
					FROM account_attributes
					WHERE attribute_account = :uid
			       )
		");
		
		if(!$attribute_data->execute(array(":uid"=>$user_id))||!$attributes_unused->execute(array(":uid"=>$user_id))){
			var_dump($db->errorInfo());
			die();
		}
		
		return array("active"=>$attribute_data->fetchAll(PDO::FETCH_ASSOC),
				"unused"=>$attributes_unused->fetchAll(PDO::FETCH_ASSOC));
	}
	
	function login($user, $pass, $fetch_full_profile=false){
		global $_SESSION;
		global $db;
		
		if(!isset($user)||empty($user)){
			logoff("?empty-user");
		}
		
		$_SESSION["username"]=htmlentities($user);
		
		$user_data=$db->prepare("SELECT account_id, account_handle FROM accounts WHERE account_handle = :username");
		if(!$user_data->execute(array(
			":username" => $_SESSION["username"]
		))){
			logoff("?error");
		}
		
		$user_data=$user_data->fetch(PDO::FETCH_ASSOC);
		
		if($user_data===FALSE){
			logoff("?credentials");
		}
		else if(!verify_password($user_data["account_id"], $pass)){
			logoff("?credentials");
		}
		
		$_SESSION["remote"]=$_SERVER["REMOTE_ADDR"];
		$_SESSION["account"]=$user_data["account_id"];
		$_SESSION["username"]=$user_data["account_handle"];
		
		if($fetch_full_profile){
			$_SESSION["attributes"]=get_attributes($_SESSION["account"]);
			//fetch all tokens
			//fetch all remotes
		}
	}
	
	function delete_account($uid){
		global $db;
		$delete_user=$db->prepare("DELETE FROM accounts WHERE account_id=:uid");
		if(!$delete_user->execute(array(":uid"=>$uid))){
			return false;
		}
		return true;
	}
	
	function update_password($uid, $pass){
		global $db;
		$user_data=$db->prepare("SELECT account_salt FROM accounts WHERE account_id = :uid");
		$update_pass=$db->prepare("UPDATE accounts SET account_password = :pass WHERE account_id = :uid");
		if(!$user_data->execute(array(":uid"=>$uid))){
			return false;
		}
		$user_data=$user_data->fetch(PDO::FETCH_ASSOC);
		
		$pass=hash("sha256", $user_data["account_salt"].$pass);
		if(!$update_pass->execute(array(":pass"=>$pass, ":uid"=>$uid))){
			return false;
		}
		return true;
	}
	
	function delete_attribute($uid, $attrib){
		global $db;
		$update_attributes=$db->prepare("DELETE FROM account_attributes WHERE attribute_account = :uid AND attribute_attribute = :attrib AND attribute_modifiable");
		return $update_attributes->execute(array(":uid"=>$uid, ":attrib"=>$attrib));
	}
	
	function add_attribute($uid, $attribute, $value){
		global $db;
		$insert_attribute=$db->prepare("INSERT INTO account_attributes (attribute_account, attribute_attribute, attribute_value) VALUES (:uid, :attrib, :value)");
		return $insert_attribute->execute(array(":uid"=>$uid, ":attrib"=>$attribute, ":value"=>htmlentities($value)));
	}
?>