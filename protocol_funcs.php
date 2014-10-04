<?php
	function systemv1_token_prepare($remote_data, $ident){
		return hash("sha256", $ident.$remote_data["remote_password"]);
	}
	
	function systemv1_authenticate_identity($remote_data, $ident){
		$token=systemv1_token_prepare($remote_data, $ident);
		$curl=curl_init($remote_data["remote_endpoint"]);
		//TODO add headers
		//TODO check return codes
		//TODO error checking
		curl_exec($curl);
		curl_close($curl);
		return true;
	}
	
	function proto_authenticate_identity($remote_data, $ident){
		switch($remote_data["remote_protocol"]){
			case "1":
			case "systemv1":
				return systemv1_authenticate_identity($remote_data, $ident);
			default:
				return false;
		}
	}
	
	function remote_info($remote_handle){
		global $db;
		
		$query_remote=$db->prepare("
			SELECT
				remote_id,
				remote_handle,
				remote_endpoint,
				remote_redirect,
				remote_user,
				remote_password,
				remote_protocol
			FROM
				remotes
			WHERE
				remote_handle = :handle
		");
		
		if(!$query_remote->execute(array(":handle"=>$remote_handle))){
			return FALSE;
		}
		
		return $query_remote->fetch(PDO::FETCH_ASSOC);
	}
	
	function active_association($uid, $service_handle){
		global $db;
		$query_assoc=$db->prepare("
			SELECT 
				association_issued, 
				association_lifetime
			FROM 
				associations
			JOIN
				remotes
				ON remotes.remote_id = associations.association_remote
			WHERE
				association_account = :uid
				AND remote_handle = :remote");
				
		if(!$query_assoc->execute(array(":uid"=>$uid, ":remote"=>$service_handle))){
			return FALSE;
		}		
		
		return $query_assoc->fetch(PDO::FETCH_ASSOC);
	}
?>