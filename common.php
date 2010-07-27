<?php
/*
	common.php loads all commonly used functions and definitions.
	Included in index.php, news.php, install.php.
	It configures LOCAL, DEBUG constants, 
	error reporting, connects to the database, handles sessions.
*/

// Define LOCAL if running on my local test server.
$localNames = array('localhost', '127.0.0.1', '192.168.1.25', 'token.homelinux.com');
$serverName = $_SERVER['SERVER_NAME'];
if(!$serverName) $serverName = getenv('SERVER_NAME');
if (in_array($serverName, $localNames))  define('LOCAL', true);

if (!defined('STANDALONE'))
{
	// Handle sessions.
	ini_set('session.gc_maxlifetime', 2*365*24*60*60);
	session_set_cookie_params(2*365*24*60*60);
	session_cache_limiter('none');
	session_start();
}

setlocale(LC_ALL, 'pl_PL.UTF8','pl_PL.UTF-8','pl.UTF8','pl.UTF-8','pl_PL','pl');
date_default_timezone_set('Europe/Warsaw');

// Handle error reporting and debug verbosity.
require_once('error.php');
initErrors();

// Unquote if someone turned magic_quotes on by accident.
if(  (function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc())  ||
   (ini_get('magic_quotes_sybase') && ( strtolower(ini_get('magic_quotes_sybase')) != "off" ))  )  
{
        foreach($_GET as $k => $v) $_GET[$k] = stripslashes($v);
        foreach($_POST as $k => $v)
        {
			if (is_array($v))
			{
				foreach($v as $i=>$vi) $v[$i] = stripslashes($vi);
				$_POST[$k] = $v;
			}
			else  $_POST[$k] = stripslashes($v);
		}
        foreach($_COOKIE as $k => $v) $_COOKIE[$k] = stripslashes($v);
}

require_once('database.php');
require_once('utils.php');
require_once('template.php'); 
require_once('user.php');
include_once('log.php');
require_once('warsztaty.php');
require_once('plan.php');
initDB();
if (!defined('STANDALONE'))
{
	initPage();
	initUser();
}

