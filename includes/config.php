<?php

/* registers our own error and exception handler */
require_once '../libraries/ExceptionHandler.php';
new ExceptionHandler('development', 'log');

/*typeset: utf8 multiple bit*/
mb_internal_encoding();
mb_http_output();

/*collection of commonly use functions*/
require "helpers.php";

/*Evernote autoload*/
require '../vendors/evernote/vendor/autoload.php';

/*Evernote interface with settings*/
require "../libraries/evernote.php";
$sandbox = true;
$china   = false;

/*MySQL interface*/
$configFile = '../config.json';
require "../libraries/mysql.php";
mysql::init($configFile);

session_start();

$_SESSION["id"] = 2;

if (!in_array($_SERVER["PHP_SELF"], ["/login.php", "/logout.php", "/register.php"]))
{
    if (empty($_SESSION["id"]))
    {
        require("/../views/login_form.php");
        exit;
    }
}
