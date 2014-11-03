<?php

	/**
		SYSTEM verification flow
	*/

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
						<p>
							Feeling insecure? Use <a href="https://<?php print($_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]) ?>">HTTPS</a>!
						</p>
						<p>
							Don't have an account? No problem, <a href="../">create one now</a>, it's free and easy!
						</p>
					</div>
				</body>
			</html>
		<?php
	}
	else{
		require_once("../account_funcs.php");
		require_once("../protocol_funcs.php");
		require_once("../db_conn.php");
		
		$attributes_requested=array("username");
		if(isset($_GET["req"])){
			$attributes_requested=explode(",", $_GET["req"]);
		}
		
		$remote_data=remote_info($_GET["service"]);
		if($remote_data===FALSE){
			exit("Invalid service, aborting.");
		}
		
		//provide fallback identity
		$ident="ident_".mt_rand().mt_rand().mt_rand();
		if(isset($_GET["ident"])){
			$ident=$_GET["ident"];
		}
		
		if(isset($_POST["confirm"])){
			if(!add_association($_SESSION["account"], $remote_data["remote_id"])){
				die("Failed to confirm identity");
			}
			
			$_SESSION["associations"]=get_associations($_SESSION["account"]);
		}
		
		$active_assoc=active_association($_SESSION["account"], $_GET["service"]);
		if($active_assoc!==FALSE){
			if(!proto_authenticate_identity($remote_data, $ident, $_SESSION["attributes"]["active"], $attributes_requested)){
				exit("Failed to authenticate.");
			}
			header("Location: ".$remote_data["remote_redirect"]);
			exit("Redirecting to service");
		}
		
		?>
			<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml">
				<head>
					<title>Verify SYSTEM Association</title>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					<link rel="stylesheet" type="text/css" href="../static/accounts.css" />
					<meta name="robots" content="noindex,nofollow" />
				</head>
				<body>
					<div id="center-box" class="padded">
						<h1 style="margin-bottom:0;padding-bottom:0;">Hi <?php print($_SESSION["username"]); ?>!</h1>
						<div style="text-align:center;padding-bottom:0.7em;">
							<a class="note">(Not you?)</a>
						</div>
						
						<form method="POST" action="">
							<span class="box-attach">The service asked the SYSTEM to provide</span>
							<div id="user-forms">
								<?php
									foreach($attributes_requested as $attr){
										if(isset($_SESSION["attributes"]["active"][$attr])){
											?>
												<div class="form-entry">
													<div class="input">
														<?php print($_SESSION["attributes"]["active"][$attr]["attribute_value"]); ?>
													</div>
													<div class="description">
														<h3><?php print($_SESSION["attributes"]["active"][$attr]["attribute_displayname"]); ?></h3>
														<?php print($_SESSION["attributes"]["active"][$attr]["attribute_desc"]); ?>
													</div>
												</div>
											<?php
										}
										else if(isset($_SESSION["attributes"]["unused"][$attr])){
											?>
												<div class="form-entry">
													<div class="input">
														<em style="color:#888;">Not set</em>
													</div>
													<div class="description">
														<h3><?php print($_SESSION["attributes"]["unused"][$attr]["attribute_displayname"]); ?></h3>
														<?php print($_SESSION["attributes"]["unused"][$attr]["attribute_desc"]); ?>
													</div>
												</div>
											<?php
										}
										else{
											print("Unknown attrib: ".$attr);
										}
									}
								?>
							</div>
							<div class="box-attach" style="float:right;">
								If you think that's ok, please 
								<input type="submit" name="confirm" value="Confirm" />
							</div>
							<div class="icebreaker"></div>
						</form>
					</div>
				</body>
			</html>
		<?php
	}
?>


