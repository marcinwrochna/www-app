<?php
/*
 * index.php includes pretty much everything and
 * calls the appropriate action, parsing the GET.
*/

require_once('common.php');
require_once('utils.php');
require_once('enum.php');
include_once('log.php');
require_once('template.php');
require_once('page.php');
require_once('form.php');
require_once('user.php');
$PAGE = new Page();
include_once('update.php'); // Apply updates.
initUser(); // initializes the $USER global.
require_once('workshop.php');
require_once('plan.php');
include_once('tutoring.php');

function callAction($action, $args = array(), $redirect = true)
{
	global $PAGE;
	if ($redirect)
	{
		$_SESSION['pageMessages'] = $PAGE->topContent;
		header('HTTP/1.1 303 See Other');
		header('Location: http://'. $_SERVER['HTTP_HOST'] . ABSOLUTE_PATH_PREFIX .
			$action .'('. implode(';', $args) .')');
		exit;
	}

	$action = 'action'. ucfirst($action);
	if (is_callable($action))
		call_user_func_array($action, $args);
	else
		throw new KnownException('Nieznana akcja.');
}

try
{
	// If redirected - restore messages.
	if (!empty($_SESSION['pageMessages']))
	{
		$PAGE->topContent .= $_SESSION['pageMessages'];
		unset($_SESSION['pageMessages']);
	}

	// Example URL:                                     http://server/doSomething(a;b)
	// .htaccess (apache mod_rewrite) translates it to: action=doSomething&args=a;b
	// We then call the function:                       actionDoSomething(a,b)
	// and put it's output in $PAGE->content.
	$action = isset($_GET['action']) ? $_GET['action'] : 'homepage';
	$args = empty($_GET['args']) ? array() : explode(';', $_GET['args']);
	$buffer = new SimpleTemplate(); // Catch echo'ed things into buffer.
	callAction($action, $args, false);
	$PAGE->content .= $buffer->finish();

	/* Menu */
	addSiteMenuBox();
	if (userIs('registered'))
		addUserMenuBox();
	else
		addLoginMenuBox();
	addWarsztatyMenuBox();
	//addTutoringMenuBox();
	addAdminMenuBox();
}
catch (PolicyException $e)
{
	$buffer->finish();
	$PAGE->content = '';
	$PAGE->topContent = '';
	$PAGE->addMessage($e->getMessage(), 'exception');
}

echo $PAGE->finish();
