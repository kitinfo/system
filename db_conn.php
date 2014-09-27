<?php
	$db=new PDO("sqlite:/home/cbdev/dev/accounts/provider.db3");
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
	$db->query("PRAGMA foreign_keys = ON");
?>