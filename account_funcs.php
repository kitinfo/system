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
	
	function get_tokens($uid){
		global $db;
		$token_data=$db->prepare("
		SELECT token_id,
			token_token,
			token_issued,
			token_lifetime,
			remote_handle
		FROM tokens
		JOIN remotes
			ON tokens.token_remote = remotes.remote_id
		WHERE tokens.token_account = :uid
		");
		
		if(!$token_data->execute(array(":uid"=>$uid))){
			return false;
		}
		
		return $token_data->fetchAll(PDO::FETCH_ASSOC);
	}
	
	function get_remotes($uid){
		global $db;
		$remote_data=$db->prepare("
			SELECT remote_id, 
				remote_handle, 
				remote_endpoint,
				remote_redirect,				
				remote_protocol
			FROM remotes 
			WHERE remote_manager = :uid
		");
		
		if(!$remote_data->execute(array(":uid"=>$uid))){
			return false;
		}
		
		return $remote_data->fetchAll(PDO::FETCH_ASSOC);
	}
	
	//TODO have this return a boolean success flag
	function login($user, $pass, $fetch_full_profile=false){
		global $_SESSION;
		global $db;
		
		if(!isset($user)||empty($user)){
			logoff("?empty-user");
		}
		
		$_SESSION["username"]=strtolower(htmlentities($user));
		
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
		$_SESSION["full_login"]=false;
		
		if($fetch_full_profile){
			$_SESSION["attributes"]=get_attributes($_SESSION["account"]);
			$_SESSION["tokens"]=get_tokens($_SESSION["account"]);
			$_SESSION["remotes"]=get_remotes($_SESSION["account"]);
			$_SESSION["full_login"]=true;
		}
	}
	
	function delete_account($uid){
		global $db;
		$delete_user=$db->prepare("DELETE FROM accounts WHERE account_id=:uid");
		return $delete_user->execute(array(":uid"=>$uid));
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
		return $update_pass->execute(array(":pass"=>$pass, ":uid"=>$uid));
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
	
	function revoke_token($uid, $token){
		global $db;
		$update_tokens=$db->prepare("DELETE FROM tokens WHERE token_id = :token AND token_account = :uid");
		return $update_tokens->execute(array(":uid"=>$uid, ":token"=>$token));
	}
	
	function delete_remote($uid, $remote){
		global $db;
		$update_remotes=$db->prepare("DELETE FROM remotes WHERE remote_id = :remote AND remote_manager = :uid");
		return $update_remotes->execute(array(":uid"=>$uid, ":remote"=>$remote));
	}
	
	function add_remote($uid, $handle, $endpoint, $redirect){
		global $db;
		if(!isset($handle)||!isset($endpoint)){
			return false;
		}
		
		if(empty($handle)||empty($endpoint)){
			return false;
		}
		
		$remote_data=$db->prepare("
			INSERT INTO remotes
			(remote_handle, remote_endpoint, remote_redirect, remote_user, remote_password, remote_manager) 
			VALUES (:handle, :endpoint, :redir, :user, :pass, :uid)
		");
		
		$user=hash("sha256", mt_rand());
		$pass=hash("sha256", mt_rand());
		
		if(!$remote_data->execute(array(
			":handle"=>htmlentities($handle), 
			":endpoint"=>$endpoint, 
			":redir"=>$redirect,
			":user"=>$user, 
			":pass"=>$pass, 
			":uid"=>$uid
		))){
			return false;
		}
		
		return array("user"=>$user, "pass"=>$pass);
	}
?>
