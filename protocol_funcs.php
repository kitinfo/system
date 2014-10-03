<?php
	function store_token($uid, $remote_id, $token){
		global $db;
		
		$store_query=$db->prepare("INSERT INTO tokens
		(token_account, token_remote, token_token, token_issued, token_lifetime) 
		VALUES (:uid, :remote, :token, :issue, :lifetime)");
		
		return $store_query->execute(array(
			":uid"=>$uid,
			":remote"=>$remote_id,
			":token"=>$token,
			":issue"=>time(),
			":lifetime"=>9001
		));
	}

	function systemv1_token_prepare($ident, $remote_data){
		return hash("sha256", $ident.$remote_data["remote_password"]);
	}
	
	function systemv1_authenticate_token($remote_data, $token){
		$curl=curl_init($remote_data["remote_endpoint"]."?token=".$token);
		//TODO add headers
		//TODO check return codes
		//TODO error checking
		curl_exec($curl);
		curl_close($curl);
		return true;
	}
	
	function systemv1_ident_confirm($uid, $remote_data, $ident){
		$token=systemv1_token_prepare($ident, $remote_data);
		return store_token($uid, $remote_data["remote_id"], $token);
	}
	
	function proto_ident_confirm($uid, $remote_data, $ident){
		switch($remote_data["remote_protocol"]){
			case "1":
			case "systemv1":
				return systemv1_ident_confirm($uid, $remote_data, $ident);
			default:
				return false;
		}
	}
	
	function proto_authenticate_token($remote_data, $token){
		switch($remote_data["remote_protocol"]){
			case "1":
			case "systemv1":
				return systemv1_authenticate_token($remote_data, $token);
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
	
	function service_token($uid, $service){
		global $db;
		$query_token=$db->prepare("
			SELECT 
				token_token,
				token_issued, 
				token_lifetime
			FROM 
				tokens
			JOIN
				remotes
				ON remotes.remote_id = tokens.token_remote
			WHERE
				token_account = :uid
				AND remote_handle = :remote");
				
		if(!$query_token->execute(array(":uid"=>$uid, ":remote"=>$service))){
			return FALSE;
		}		
		
		return $query_token->fetch(PDO::FETCH_ASSOC);
	}
?>