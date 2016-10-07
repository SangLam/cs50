<?php

require_once 'vendor/autoload.php';

new ExceptionHandler('development', 'log');

/*typeset: utf8 multiple bit*/
mb_internal_encoding();
mb_http_output();

/*MySQL interface*/
$configFile = 'config.json';
MySqlConnection::getInstance($configFile);

session_start();

/* for development */
$_SESSION["id"] = 2;

if (!in_array($_SERVER["PHP_SELF"], ["/login.php", "/logout.php", "/register.php"]))
{
    if (empty($_SESSION["id"]))
    {
        require("/../views/login_form.php");
        exit;
    }
}
