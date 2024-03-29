<?php
/*
	error.php defines error and exception reporting and handling.
	Included in common.php and install.php.
	Defined:
		constant DEBUG - 0 for nothing, 1 for E_STRICT, 2 for superglobal dump
		class KnownException - for explicitely called exceptions.
		class DbException - the same, with $DB->last_error() appended
		class PolicyException - throwed at privilege escalation attempts
		errorHandler(), errorParse() - passed to set_exception_handler()
			and set_error_handler(), will send errors to my email.
		shutdownHandler() - passed to register_shutdown_function(), handles fatal errors.
		dumpSuperGlobals() - returns a description of $_GET,$_POST,$_SESSION,$_SERVER
		errorLog($s) - writes $s with timestamp to ERROR_LOG, rotates it.
			Used as a backup, mails should be enough.
		actionShowErrorLog() - shows the ERROR_LOG.
*/

function initErrors()
{
	if (isset($_GET['debug']))  $_SESSION['debug'] = $_GET['debug'];
	if (isset($_SESSION['debug']) && $_SESSION['debug']>0)  define('DEBUG', $_SESSION['debug']);
	else define('DEBUG', 0);

	if (DEBUG>=1)  error_reporting(E_ALL | E_STRICT);
	else  error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_USER_ERROR);
	ini_set("display_errors", 1);
	ini_set("display_startup_errors", 1);

	set_error_handler('errorHandler');
	set_exception_handler('errorHandler');
	register_shutdown_function('shutdownHandler');
}

function errorParse($errno, $errstr='', $errfile='', $errline='', &$logMessage=null)
{
	if (is_object($errno))
	{
		  // Called by exception.
		  $exc = func_get_arg(0);
		  $errno = $exc->getCode();
		  $errstr = $exc->getMessage();
		  $errfile = $exc->getFile();
		  $errline = $exc->getLine();
		  $backtrace = $exc->getTrace();
	}
	else
	{
		  // Called by trigger_error().
		  $exception = null;
		  list($errno, $errstr, $errfile, $errline) = func_get_args();
		  $backtrace = array_reverse(debug_backtrace());
	}

	$error_types = array
	(
		E_ERROR           => 'ERROR',
		E_WARNING         => 'WARNING',
		E_PARSE           => 'PARSING ERROR',
		E_NOTICE          => 'NOTICE',
		E_CORE_ERROR      => 'CORE ERROR',
		E_CORE_WARNING    => 'CORE WARNING',
		E_COMPILE_ERROR   => 'COMPILE ERROR',
		E_COMPILE_WARNING => 'COMPILE WARNING',
		E_USER_ERROR      => 'USER ERROR',
		E_USER_WARNING    => 'USER WARNING',
		E_USER_NOTICE     => 'USER NOTICE',
		E_STRICT          => 'STRICT NOTICE',
		E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
	);

	if (array_key_exists($errno, $error_types))
		$err = $error_types[$errno];
	else
		$err = 'CAUGHT EXCEPTION';

	$trace = "plik $errfile linia $errline\n";
	foreach ($backtrace as $v)
	{
		if (isset($v['class']))
		{
			$trace .= 'in class '.$v['class'].'::'.$v['function'].'(';
			if (isset($v['args']))
			{
				$args = array();
				foreach ($v['args'] as $arg )  $args[]= getArgument($arg);
				$trace .= implode(', ',$args);
			}
			$trace .= ")\n";
			if (isset($v['line']))
				$trace .= '     '. $v['file'] .' #'. $v['line'] ."\n";
		}

		elseif (isset($v['function']) /*&& empty($trace)*/)
		{
			$trace .= 'in function '.$v['function'].'(';
			if (!empty($v['args']))
			{
				$args = array();
				if (!in_array($v['function'], array('errorHandler','errorParser','getArgument')))
					foreach ($v['args'] as $arg )  $args[]= getArgument($arg);
				$trace .= implode(', ',$args);
			}
			$trace .= ")\n";
			if (isset($v['line']))
				$trace .= '     '. $v['file'] .' #'. $v['line'] ."\n";
		}
	 }

	$server = '';
	foreach ($_SERVER as $name => $value)
		$server .= json_encode($name) ." :\t". json_encode($value) ."\n";

	if (isset($logMessage))  $logMessage =
	"	===WWW $err===
		'$errstr'
		zapytanie: {$_SERVER['REQUEST_URI']}
		file $errfile #$errline
		===Backtrace====
		$trace
		". strftime('%T') ."
		================
		\$_GET: ". json_encode($_GET) ."
		\$_POST: ". json_encode($_POST) ."
		\$_SERVER: {\n". $server ."
		}
		\$_SESSION: ". json_encode($_SESSION) ."
		================
	";

	$errstr = nl2br($errstr);
	$trace  = nl2br($trace);

	$report =
	"<div class=\"errorReport\">
		<h1 style='font-size: 140%; margin-bottom:0;'>". _('Error') ."</h1>
		<div>". _('Oops, we\'re sorry, something exploded. Our team of kittens is working on the problem.') ."<br/>$errstr</div><br />
	";
	if (DEBUG)  $report.=
	"
		<a onclick=\"getElementById('errorDetails').style.display='block';\" style='margin:10px; border: 1px solid #aaa'>Show details</a><br />
		<div id='errorDetails' style='margin:10px; padding:5px; border: 1px solid #aaa; line-height:150%; display:none'>
			<b>typ: </b>$err<br/>
			<b>zapytanie: </b> {$_SERVER['REQUEST_URI']} <br />
			<b>backtrace:</b> $trace<br />
			<b>\$_GET: </b>". htmlentities(json_encode($_GET)) ."<br />
			<b>\$_POST: </b>". htmlentities(json_encode($_POST)) ."<br />
			<b>\$_SESSION: </b>". htmlentities(json_encode($_SESSION)) ."<br />
			<b>\$_SERVER: </b>". htmlentities(json_encode($_SERVER)) ."<br />
		</div>
	";
	$report.= strftime('%T %s') ."<br /></div>";
	return $report;
}

function errorHandler($errno, $errstr='', $errfile='', $errline='')
{
	// Skip errors suppresed with @.
	if (error_reporting() == 0) return;
	// Rethrow errors as exceptions so they can be caught.
	if (!is_object($errno))
	{
		if ($errno & error_reporting())
		  throw new ErrorException($errstr, $errno, $errno, $errfile, $errline);
		else  return;
	}

	try
	{
		if ($errno->getCode() & error_reporting())
		{
			$level = ob_get_level();
			for ($i=0; $i<$level; $i++) @ob_end_clean();
			if ($errno instanceof KnownException)
				header('HTTP/1.1 '. $errno->status);
			$logMessage = '';
			$parsed = @errorParse($errno,$errstr,$errfile,$errline,$logMessage);
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
			echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl" dir="ltr"><head>';
			echo '    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
			echo "</head><body>$parsed</body></html>";
			errorLog($logMessage);
			if (ERROR_EMAIL)  sendMail('warsztatyWWW error', $logMessage, ERROR_EMAIL_ADDRESS);
		}
	}
	catch (Exception $e)
	{
		echo '<b>Błąd w samej obsłudze błędów - pewnie nie udaje się wysłać raportu. Napisz proszę do '. BUGREPORT_EMAIL_ADDRESS .'</b><br/>';
		echo 'Error within handler: <pre>'.$e->getMessage().'</pre> on line '.$e->getLine();

	}
}

function shutdownHandler()
{
	// Check for fatal error on shutdown.
	$error = error_get_last();
	if (is_array($error) && in_array($error['type'], array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR), true))
	{
		$logMessage = "FATAL ERROR\n". $error['file'] .' : '. $error['line'] ."\n";
		$logMessage .= $error['message'];
		errorLog($logMessage);
		if (ERROR_EMAIL)  sendMail('warsztatyWWW fatal error', $logMessage, ERROR_EMAIL_ADDRESS);
	}
}

function getArgument($arg)
{
	switch (strtolower(gettype($arg)))
	{
		case 'string':
			return( '"'.substr(htmlspecialchars(str_replace( array("\n"), array(''), $arg )),0,50).'"' );
		case 'boolean':
			return (bool)$arg;
		case 'object':
			return 'object('.get_class($arg).')';
		case 'array':
			$args = array();
			foreach ($arg as $k => $v)  $args[]= getArgument($k).' => '.getArgument($v);
			$ret = 'array('.implode(', ',$args).')';
			return $ret;
		case 'resource':
			return 'resource('.get_resource_type($arg).')';
		default:
			return var_export($arg, true);
	 }
}

class KnownException extends Exception
{
	public $status;
	function __construct($s, $status='500 Internal Server Error', $n=E_USER_ERROR)
	{
		parent::__construct($s,$n);
		$this->status = $status;
	}
}

class DbException extends KnownException
{
	function __construct($s, $n=E_USER_ERROR)
	{
		$s = "<h4>Database</h4><pre>$s</pre>";
		parent::__construct($s, '500 Internal Server Error', $n);
	}
}

class PolicyException extends KnownException /* These are caught in index.php not to spill anything. */
{
	function __construct($s=null, $n=E_USER_ERROR)
	{
		if (is_null($s))
			$s = _('Access forbidden.');
		parent::__construct($s, '403 Forbidden', $n);
	}
}

function dumpSuperGlobals()
{
	$result = '';
	$result .= '<b>GET:</b><br/>';
	foreach ($_GET as $name=>$value)
		$result .= $name .'=>'. htmlentities($value) .',<br/>';

	$result .= '<b>POST:</b><br/>';
	foreach ($_POST as $name=>$value)
	{
		if (is_array($value))  $result .= $name .'=>'. htmlspecialchars(implode(',',$value)) .',<br/>';
		else   $result .= $name .'=>'. htmlspecialchars($value) .',<br/>';
	}

	$result .= '<b>SESSION:</b><br/>';
	foreach ($_SESSION as $name=>$value)
		$result .= $name .'=>'. htmlentities(json_encode($value)) .',<br/>';


	$result .= '<b>SERVER:</b><br/>';
	$exclude = array
	(
		'HTTP_ACCEPT','HTTP_ACCEPT_CHARSET',
		'HTTP_ACCEPT_ENCODING','HTTP_TE','PATH','SERVER_SIGNATURE',
		'SERVER_SOFTWARE','SERVER_ADMIN','GATEWAY_INTERFACE','SERVER_PROTOCOL'
	);
	foreach ($_SERVER as $name=>$value)  if(!in_array($name, $exclude))
	{
		if (is_array($value))  $result .= $name .'=>'. htmlspecialchars(implode(',',$value)) .',<br/>';
		else   $result .= $name .'=>'. htmlspecialchars($value) .',<br/>';
	}

	return $result;
}

function errorLog($logMessage)
{
	touch(ERROR_LOG);
	$lines = file(ERROR_LOG);
	if (is_array($lines))
		$lines = array_slice($lines, -8000);
	else
		$lines = array();
	$lines[]= "LOGGED at ". strftime("%Y-%m-%d %H:%M:%S") ."\n\n";
	$lines[]= $logMessage ."\n";
	file_put_contents(ERROR_LOG, $lines);
}

function actionShowErrorLog()
{
	global $DB, $USER, $PAGE;
	if (!userCan('showLog'))  return;
	if (!ERROR_LOG)
		$PAGE->addMessage(_('Error log disabled in configuration.'));
	else
	{
		$contents = file_get_contents(ERROR_LOG);
		$PAGE->addMessage('<pre>'. htmlspecialchars($contents) .'</pre>');
	}
}

