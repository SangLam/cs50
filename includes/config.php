<?php

	mb_internal_encoding();
	mb_http_output();

	ini_set("display_errors", true);
	error_reporting(E_ALL);
	
	require("helpers.php");
	
	session_start();
	
	$_SESSION["id"] = 1;
	
	if (!in_array($_SERVER["PHP_SELF"], ["/login.php", "/logout.php", "/register.php"]))
	{
		if (empty($_SESSION["id"]))
		{
			require("/../views/login_form.php");
			exit;
		}
	}