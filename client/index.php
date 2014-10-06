<?php
	
	/**
		SYSTEM demo client
	*/
	require_once("endpoint_credentials.php");
	require_once("client_db.php");

	session_start();
	
	if(isset($_GET["logoff"])){
		session_regenerate_id(TRUE);
		$_SESSION=array();
		header("Location: ./");
	}
	
	$my_token=hash("sha256", hash("sha256", session_id()).$SYSTEM_REMOTE_PASSWORD);
	
	$auth_query=$db->prepare("SELECT hit_user FROM authentication_hits WHERE hit_token = :token");
	$auth_query->execute(array(":token" => $my_token));
	$auth_data=$auth_query->fetch(PDO::FETCH_ASSOC);
	
	if($auth_data!==false){
		$_SESSION["authenticated_user"]=$auth_data["hit_user"];
		$auth_query=$db->prepare("DELETE FROM authentication_hits WHERE hit_token = :token");
		$auth_query->execute(array(":token" => $my_token));
	}
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>SYSTEM Authentication Demo Client</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="demo.css" />
		<meta name="description" content="SYSTEM Authentication Demo Client App" />
		<meta name="robots" content="noindex,nofollow" />
	</head>
	<body>
		<div id="center-wrap">
			<h1>Welcome to the SERVICE</h1>
			Your token is: <?php print($my_token); ?><br/>
			<?php
				if(isset($_SESSION["authenticated_user"])){
			?>
					Hi there, <?php print($_SESSION["authenticated_user"]); ?>! <a href="?logoff">Log off</a>
			<?php
				}
				else{
			?>
					To use our facilities, we kindly ask you to<br/><br/>
					<a class="system-login" href="http://auth.local.host/kitinfo-accounts/verify/?service=demo&ident=<?php print(hash("sha256", session_id())); ?>">
						<span class="system-head">Sign in with the</span>
						<span class="system-name">SYSTEM</span>
					</a>
			<?php
				}
			?>
		</div>
	</body>
</html>
