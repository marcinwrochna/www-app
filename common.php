<?php
/*
 *	common.php loads basic functions and definitions.
 *	Included in index.php, notify.php (a cron job), install.php
 *	Error reporting, database connection, session.
 */

require_once('config.php');

ini_set('session.gc_maxlifetime', 2*365*24*60*60);
session_set_cookie_params(2*365*24*60*60);
session_cache_limiter('none');
session_start();

setlocale(LC_ALL, 'pl_PL.UTF8','pl_PL.UTF-8','pl.UTF8','pl.UTF-8','pl_PL','pl');
date_default_timezone_set('Europe/Warsaw');

// Error reporting and debug verbosity.
require_once('error.php');
initErrors();

require_once('database.php'); //deprecated
require_once('database/db.php');
//$DB = DB::create(DB_DRIVER, $connectionParams);

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
