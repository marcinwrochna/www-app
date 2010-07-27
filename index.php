<?php
/*
	index.php shows the article/attachment/image pointed by the url query.
	Includes: common.php, html.php
*/

require_once('common.php');
include_once('update.php');
include_once('tutoring.php');

function actionHomepage()
{
	global $PAGE;
	$PAGE->title = 'Strona główna';	
	$PAGE->content .= getOption('homepage');
}

try
{
	$action = isset($_GET['action']) ? $_GET['action'] : 'homepage';
	$action = 'action'. ucfirst($action);
	if (is_callable($action)) call_user_func($action);
	else throw new KnownException('Nieznana akcja.');
	
	$PAGE->menu .= buildSiteBox();
	if (in_array('registered', $USER['roles']))
		$PAGE->menu .= buildUserBox();
	else
		$PAGE->menu .= buildLoginBox();
	$PAGE->menu .= buildWarsztatyBox();
	//$PAGE->menu .= buildTutoringBox();
	$PAGE->menu .= buildAdminBox();		
}
catch (PolicyException $e)
{
	$PAGE->content = '';
	$PAGE->topContent = '';
	showMessage($e->getMessage(), 'exception');
}
	
outputPage();


//logVisitor('index', $fid);
