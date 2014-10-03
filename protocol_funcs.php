<?php
	function systemv1_token_prepare($token, $remote_data){
		return hash("sha256", $token.$remote_data["remote_password"]);
	}
	
	function systemv1_authenticate_token(){
		//TODO
	}
	
	function remote_info($remote_handle){
		global $db;
		
		$query_remote=$db->prepare("
			SELECT
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