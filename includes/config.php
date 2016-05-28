<?php

	ini_set("display_errors", true);
	error_reporting(E_ALL);
	
	/*typeset: utf8 multiple bit*/
	mb_internal_encoding();
	mb_http_output();
	
	$configFile = '../config.json';
	
	/*collection of commonly use functions*/
	require "helpers.php";
	
	/*Evernote autoload*/
	require '/../vendors/evernote/vendor/autoload.php';
	
	/*Evernote interface with settings*/
	require "../libraries/evernote.php";
	$sandbox = true;
	$china   = false;
	
	/*MySQL interface*/
	require "../libraries/mysql.php";
	mysql::init($configFile);
	
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