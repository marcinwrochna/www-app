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

// Handle sessions.
ini_set('session.gc_maxlifetime', 2*365*24*60*60);
session_set_cookie_params(2*365*24*60*60);
session_cache_limiter('none');
session_start();

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


// Mini database abstraction layer.
define('TABLE_PREFIX', 'w1_');

function db_query($query, $errorString='')
{
	$query = str_replace('table_', TABLE_PREFIX, $query);
	$result = pg_query($query);
	if ($result === false && $errorString!==false)  throw new DbException($errorString);
	return $result;
}

function db_query_params($query, $params, $errorString='')
{
	$query = str_replace('table_', TABLE_PREFIX, $query);
	$result = pg_query_params($query, $params);
	if ($result === false)  throw new DbException($errorString);
	return $result;
}

function db_insert($table, $values, $errorString='', $nextval=NULL)
{

	$sqlQuery = "INSERT INTO table_$table (";
	$sqlQuery .= implode(array_keys($values), ',');
	$sqlQuery .= ') VALUES ($';
	$sqlQuery .= implode(range(1,count($values)),',$');
	$sqlQuery .= ')';
	db_query_params($sqlQuery, array_values($values), $errorString);
	if (!empty($nextval))
	{
		$result = db_query("SELECT currval('table_${table}_${nextval}_seq') AS val", 'Nie udało się odzyskać utworzonego obiektu.');
		$result = db_fetch_assoc($result);
		return $result['val'];
	}
}

function db_update($table, $where, $values, $errorString='')
{
	$temp = array();
	$i = 1;
	foreach ($values as $k=>$v)
	{
		$temp[]= $k.'=$'.$i;
		$i++;
	}
	$sqlQuery = "UPDATE table_$table SET ";
	$sqlQuery .= implode($temp,',');
	$sqlQuery .= ' '. $where;
	db_query_params($sqlQuery, array_values($values), $errorString);
}

function db_get($table, $primary_key, $field, $default = false)
{
	$keynames = array(
		'users' => 'uid',
		'workshops' => 'wid',
		'options' => 'name',
		'comments' => 'cid'
	);
	$keyname = $keynames[$table];
	$sqlQuery = "SELECT $field FROM table_$table WHERE $keyname=$1";
	$result = db_query_params($sqlQuery, array($primary_key));
	$result = db_fetch_array($result);
	if ($result === false)  return $default;
	if (count($result)<=2)  return $result[0];
	return $result;
}

function db_fetch($r)
{
	$row = pg_fetch_row($r);
	if (is_array($row))
	{
		if (count($row)==1)  return $row[0];
		else  return $row;
	}
	else  return false;
}

function db_connect($q)  { return pg_connect($q); }
function db_num_rows($r)  { return pg_num_rows($r); }
function db_affected_rows($r)  { return pg_affected_rows($r); }
function db_fetch_row($r)  { return pg_fetch_row($r); }
function db_fetch_array($r)  { return pg_fetch_array($r); }
function db_fetch_all($r)  {
	$result = pg_fetch_all($r);
	if ($result === false)  $result = array();
	return $result;
}
function db_fetch_all_columns($r, $c=0) { return pg_fetch_all_columns($r,$c); }
function db_fetch_assoc($r)  { return pg_fetch_assoc($r); }
function db_last_error()  { return pg_last_error(); }


// Connect to database.
if (defined('LOCAL'))  $connectionString = 'host=localhost user=token dbname=www';
else $connectionString = 'host=localhost user=kuzniami_www password=PymhpdV5cP7z dbname=kuzniami_www1';
$connection = db_connect($connectionString);
if (!$connection)  throw new DbException('Cannot connect with database ('.$connectionString.')');

require_once('utils.php');
require_once('template.php'); 
require_once('user.php');
include_once('log.php');
require_once('warsztaty.php');
initPage();
initUser();

