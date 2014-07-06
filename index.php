<?php
/**
 *	index.php includes pretty much everything and
 *	calls the appropriate action, parsing the GET.
 *	The PAGE is built, the action's output written
 * to $PAGE->content, add*MenuBox()s called.
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
require_once('summary.php');
include_once('tutoring.php');
require_once('generatecsv.php');

/**
 * Calls an action, by default with a 303 redirect - implementing the POST-REDIRECT-GET pattern.
 * The redirect preserves only messages.
 * @param $action the action's name, e.g.: 'homepage'.
 * @param $args array of arguments past to the action function.
 * @param $redirect (optional) Defaults to true. If false, the action will be called directly.
 */
function callAction($action, $args = array(), $redirect = true)
{
	global $PAGE, $USER;
	if ($redirect)
	{
		$_SESSION['pageMessages'] = $PAGE->topContent;
		header('HTTP/1.1 303 See Other');
		$url = 'http://'. $_SERVER['HTTP_HOST'] . ABSOLUTE_PATH_PREFIX;
		if (isset($USER['impersonatedBy']))
			$url .= 'impersonate('. $USER['uid'] .')/';
		$url .= $action .'('. implode(';', $args) .')';
		header('Location: '. $url);
		exit;
	}

	$action = 'action'. ucfirst($action);
	if (is_callable($action))
		call_user_func_array($action, $args);
	else
		throw new KnownException(_('Unknown action requested.'), '404 Not Found');
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
	header('HTTP/1.1 403 Forbidden');
	$buffer->finish();
	$PAGE->content = '';
	$PAGE->topContent = '';
	$PAGE->addMessage($e->getMessage(), 'exception');
}

echo $PAGE->finish();
