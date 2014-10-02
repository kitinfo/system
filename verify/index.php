<?php
	session_start();
	if(isset($_POST["login"])){
		require_once("../account_funcs.php");
		require_once("../db_conn.php");
		login($_POST["username"], $_POST["pass"], false);
	}

	
	if(!isset($_SESSION["account"])){
		?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml">
				<head>
					<title>Log in with your SYSTEM account</title>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					<link rel="icon" href="favicon.ico" type="image/x-icon" />
					<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
					<link rel="stylesheet" type="text/css" href="../static/accounts.css" />
					<meta name="description" content="Log in with your SYSTEM account in order to use other services and websites" />
					<meta name="robots" content="noindex,nofollow" />
				</head>
				<body>
					<div id="center-box" class="padded">
						<h1>Hi there!</h1>
						You're here because the referring service uses the SYSTEM to authenticate its users.
						<div id="user-forms">
							<div class="inline-box">
								<strong>Sign in</strong>
								<form action="" method="POST">
									<input type="text" id="username" name="username" />
									<label for="username">User</label> 
									<br/>
									<input type="password" id="pass" name="pass" />
									<label for="pass">Password</label> 
									<br/>
									<input type="submit" value="Continue" name="login"/>
								</form>
							</div>
						</div>
						Don't have an account? No problem, <a href="../">create one now</a>, it's free and easy!
					</div>
				</body>
			</html>
		<?php
	}
	else{
		require_once("../account_funcs.php");
		require_once("../db_conn.php");
		
		print("Welcome ".$_SESSION["username"]);
			//check if theres an active token for the service
				//log in
			//else show verification page
	}
?>


