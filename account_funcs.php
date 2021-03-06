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
		
		$attributes=$db->prepare("
		SELECT
			attribute_id,
			attribute_name,
			attribute_displayname,
			attribute_type,
			attribute_desc,
			acc_attrs.attribute_value AS attribute_value,
			acc_attrs.attribute_modifiable AS attribute_modifiable
		FROM attributes
			LEFT JOIN ( 
				SELECT 
					attribute_attribute,
					attribute_value,
					attribute_modifiable
				FROM account_attributes
				WHERE attribute_account = :uid 
			) acc_attrs
			ON acc_attrs.attribute_attribute = attributes.attribute_id
		");
		
		if(!$attributes->execute(array(":uid"=>$user_id))){
			//FIXME this might be sensitive
			var_dump($db->errorInfo());
			die("Database failure");
		}
		
		$attribute_data=$attributes->fetchAll(PDO::FETCH_ASSOC);
		if($attribute_data===false){
			//FIXME this might be sensitive
			var_dump($db->errorInfo());
			die("Database failure");
		}
		
		$attributes_active=array();
		$attributes_unused=array();
		foreach($attribute_data as $attrib){
			if($attrib["attribute_value"]){
				$attributes_active[$attrib["attribute_name"]]=$attrib;
			}
			else{
				$attributes_unused[$attrib["attribute_name"]]=$attrib;
			}
			
		}
		
		return array("active"=>$attributes_active,
				"unused"=>$attributes_unused);
	}
	
	function get_associations($uid){
		global $db;
		$assoc_data=$db->prepare("
		SELECT association_id,
			datetime(association_issued, 'unixepoch') AS association_issued,
			association_lifetime,
			remote_handle
		FROM associations
		JOIN remotes
			ON associations.association_remote = remotes.remote_id
		WHERE associations.association_account = :uid
		");
		
		if(!$assoc_data->execute(array(":uid"=>$uid))){
			return false;
		}
		
		return $assoc_data->fetchAll(PDO::FETCH_ASSOC);
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
		$_SESSION["full_login"]=$fetch_full_profile;
		
		$_SESSION["attributes"]=get_attributes($_SESSION["account"]);
		if($fetch_full_profile){
			$_SESSION["associations"]=get_associations($_SESSION["account"]);
			$_SESSION["remotes"]=get_remotes($_SESSION["account"]);
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
	
	function revoke_association($uid, $assoc_id){
		global $db;
		$update_assoc=$db->prepare("DELETE FROM associations WHERE association_id = :assoc_id AND association_account = :uid");
		return $update_assoc->execute(array(":uid"=>$uid, ":assoc_id"=>$assoc_id));
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
	
	function add_association($uid, $remote_id){
		global $db;
		
		$store_query=$db->prepare("INSERT INTO associations
		(association_account, association_remote, association_issued, association_lifetime) 
		VALUES (:uid, :remote, :time, :lifetime)");
		
		return $store_query->execute(array(
			":uid"=>$uid,
			":time"=>time(),
			":remote"=>$remote_id,
			":lifetime"=>9001
		));
	}
?>
