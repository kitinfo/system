<?php
	function systemv1_token_prepare($remote_data, $ident){
		return hash("sha256", $ident.$remote_data["remote_password"]);
	}
	
	function systemv1_authenticate_identity($remote_data, $ident, $attributes, $attribs_requested){
		$rv=false;
		$token=systemv1_token_prepare($remote_data, $ident);
		$curl=curl_init($remote_data["remote_endpoint"]);
		
		$post_fields=array("token" => $token);
		foreach($attribs_requested as $attrib){
			if(isset($attributes[$attrib])){
				$post_fields[$attrib]=$attributes[$attrib]["attribute_value"];
			}
		}
		
		if($curl!==false){
			if(curl_setopt_array($curl, array(
				CURLOPT_FORBID_REUSE => true,
				CURLOPT_RETURNTRANSFER => true,
				//CURLOPT_MUTE => true,
				CURLOPT_NOBODY => true,
				CURLOPT_POST => true,
				CURLOPT_CONNECTTIMEOUT => 1,
				CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
				CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
				CURLOPT_HTTPHEADER => array('Expect:'),
				CURLOPT_TIMEOUT => 2,
				CURLOPT_USERPWD => $remote_data["remote_user"].":".$remote_data["remote_password"],
				CURLOPT_POSTFIELDS => $post_fields
			))){
				if(curl_exec($curl)!==false){
					$rv=true;
				}
			}
			
			curl_close($curl);
		}

		return $rv;
	}
	
	function proto_authenticate_identity($remote_data, $ident, $attribs, $reqs){
		switch($remote_data["remote_protocol"]){
			case "1":
				//damn legacy.
			case "systemv1":
				return systemv1_authenticate_identity($remote_data, $ident, $attribs, $reqs);
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
