<?php
/*
	index.php shows the article/attachment/image pointed by the url query.
	Includes: common.php, html.php
*/

require_once('common.php');
require_once('utils.php');
include_once('log.php');
include_once('update.php'); // apply updates
require_once('template.php');
require_once('form.php');
require_once('user.php');
require_once('page.php');
$PAGE = new Page();

initUser();

require_once('warsztaty.php');
require_once('plan.php');
include_once('tutoring.php');

try
{
	$action = isset($_GET['action']) ? $_GET['action'] : 'homepage';
	$action = 'action'. ucfirst($action);
	$args = isset($_GET['args']) ? explode(';', $_GET['args']) : array();
	if (is_callable($action))
		call_user_func_array($action, $args);
	else
		throw new KnownException('Nieznana akcja.');
	
	$PAGE->menu .= addSiteMenuBox();
	if (in_array('registered', $USER['roles']))
		$PAGE->menu .= addUserMenuBox();
	else
		$PAGE->menu .= addLoginMenuBox();
	$PAGE->menu .= addWarsztatyMenuBox();
	//$PAGE->menu .= buildTutoringBox();
	$PAGE->menu .= addAdminMenuBox();		
}
catch (PolicyException $e)
{
	$PAGE->content = '';
	$PAGE->topContent = '';
	showMessage($e->getMessage(), 'exception');
}
	
echo $PAGE->finish();

//logVisitor('index', $fid);
