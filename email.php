<?php
/*	email.php - defines sendMail() and validEmail(). It's large and complicated due to
 * 	the implementation of OAUTH 2access for sending email that reliably won't be filtered as spam.
 *
 *	Defines:
 *		sendMail($subject, $content, $to, $isHTML = false).
 *		validEmail($email) - return true iff $email is a valid e-mail address.
 *
 *		actionFetchGmailOAuthAccessToken() - call this action to obtain an accessToken
 * 		to gmail for use in sendMail. The user will be redirected to gmail, google will ask
 * 		if he wants to give the application access to his gmail account, an accessToken
 * 		will be saved in the database and used subsequently in all sendMail calls.
 * 		I don't know of any expiration, but for now it seems to last at least a few months.
 *
 * Config:
 * 	const EMAIL_METHOD - either
 * 		'display' - don't send emails, just show them to the user when they would be sent.
 * 		'simple' - use php's builtin mail() function - DEPRECATED.
 * 		'gmail' - use a gmail account (SMTP) through OAUTH2 -
 * 			that's the only good way I found for emails not to fall into spam.
 * 	const OAUTH2_CLIENT_ID, OAUTH2_CLIENT_SECRET - values from the Google API Console
 * 	To use gmail, through oauth2, you need to register your application instance at
 * 		https://code.google.com/apis/console/
 *
 * I added Zend/Mail/Protocol/Smtp/Auth/Xoauth2.php to the Zend Package.
 */
require_once 'Zend/Oauth/Consumer.php';
require_once 'Zend/Crypt/Rsa/Key/Private.php';
require_once 'Zend/Mail/Transport/Smtp.php';
require_once 'Zend/Mail.php';
require_once 'Zend/Oauth/Token/Access.php';

require_once 'Zend/Google/Google_Client.php';

require_once 'Zend/Mail/Protocol/Imap.php';
require_once 'Zend/Mail/Storage/Imap.php';

function sendMail($subject, $content, $to, $isHTML = false)
{
	try
	{
		if (EMAIL_METHOD == 'display')
		{
			$message = "
				Gdyby nie włączone debugowanie, wysłano by maila:<br/>
				SUBJECT: <i>$subject</i><br/>
				TO: <i>". json_encode($to) ."</i><br/>
				CONTENT(html=". json_encode($isHTML) ."):<br/>
				$content
			";
			global $PAGE;
			if ($PAGE)
				$PAGE->addMessage($message);
			else
				echo $message;
		}
		/*
		// unmaintained (doesn't support $from = array(array('John','a@a'),...))
		else if (EMAIL_METHOD == 'simple')
		{
			if (!$isHTML)
				$content .= "\n__\nEmail automatycznie wysłany z ". $_SERVER['HTTP_HOST'];
			$from = 'noreply@warsztatywww.nstrefa.pl';
			ini_set("sendmail_from", $from);
			mail($to, "[WWW][app] $subject", $content , "From: $from", "-f$from");
		}
		*/

		else if (EMAIL_METHOD == 'gmail')
		{
			if (!$isHTML)
				$content .= "\n__\nEmail automatycznie wysłany z ". $_SERVER['HTTP_HOST'];

			$emailAddress = getOption('gmailOAuthEmail');
			$accessToken = unserialize(base64_decode(getOption('gmailOAuthAccessToken')));
			$accessToken = json_decode($accessToken);
			$accessToken = $accessToken->access_token;

			$config = array(
				'ssl' => 'ssl', // ssl,tls
				'port' => '465', //465,587 (docs say use tls and 465, or 587 if client issues text before STARTLS)
				'auth' => 'xoauth2',
				'xoauth_request' => base64_encode("user=$emailAddress\1auth=Bearer $accessToken\1\1")
			);
			$transport = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $config);
			$mail = new Zend_Mail('UTF-8');
			if ($isHTML)  $mail->setBodyHTML($content);
			else          $mail->setBodyText($content);
			$mail->setFrom($emailAddress, 'Aplikacja WWW');
			if (is_array($to))
				foreach ($to as $t)
					$mail->addTo($t[1], $t[0]);
			else  $mail->addTo($to);
			$mail->setSubject("[WWW][app] $subject");
			$mail->send($transport);
		}
	}
	// Error handlers send mails, sometimes it hangs and no error would be registered without this.
	catch (Exception $e)
	{
		errorLog("CAUGHT\n". $e->getMessage() ."TRACE\n". $e->getTraceAsString());
		throw $e;
	}
}

function actionFetchGmailOAuthAccessToken()
{
	global $DB, $PAGE;
	if (!userIs('admin'))  throw new PolicyException();
	logUser('redoOAuth');

	$client = new Google_Client();
	$client->setApplicationName('Summer Scientific Schools');
	$client->setClientId(OAUTH2_CLIENT_ID);
	$client->setClientSecret(OAUTH2_CLIENT_SECRET);
	$client->setRedirectUri(getCurrentUrl(false));
	$client->setScopes('https://mail.google.com/');

	if (!isset($_GET['code']))
		header('Location: '. $client->createAuthUrl());
	else
	{
		$accessToken = $client->authenticate();
		$accessToken = base64_encode(serialize($accessToken));
		$DB->options['gmailOAuthAccessToken']->update(array('value' => $accessToken));
		$PAGE->addMessage("Successfully received accessToken: <pre>$accessToken</pre>", 'success');
		callAction('editOptions');
	}
}

function getCurrentUrl($includeQuery = true)
{
	$scheme =  empty($_SERVER['HTTPS']) ? 'http' : 'https';
	$hostname = $_SERVER['SERVER_NAME'];
	$port = $_SERVER['SERVER_PORT'];
	$uri = $_SERVER['REQUEST_URI'];
	if (!$includeQuery)
	{
		$uri = explode('?', $uri);
		$uri = $uri[0];
	}
	if (!($port == '80' && $scheme == 'http') && !($port == '443' && $scheme == 'https'))
		$hostname .= ':'. $port;
  return $scheme . '://' . $hostname . $uri;
}


/*	Return true iff $email is a valid email address.
 *	by Douglas Lovell
 *	http://www.linuxjournal.com/article/9585?page=0,3 */
function validEmail($email)
{
	$isValid = true;
	$atIndex = strrpos($email, "@");
	if (is_bool($atIndex) && !$atIndex)  return false;

    $domain = substr($email, $atIndex+1);
    $local = substr($email, 0, $atIndex);
    $localLen = strlen($local);
    $domainLen = strlen($domain);
    if ($localLen < 1 || $localLen > 64)  return false;          // local part length exceeded
    if ($domainLen < 1 || $domainLen > 255)  return false; // domain part length exceeded
    if ($local[0] == '.' || $local[$localLen-1] == '.') return false; // local part starts or ends with '.'
    if (preg_match('/\\.\\./', $local))  return false; // local part has two consecutive dots
    if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))  return false; // character not valid in domain part
    if (preg_match('/\\.\\./', $domain))  return false; // domain part has two consecutive dots
    if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local)))
    {
       // character not valid in local part unless
       // local part is quoted
       if (!preg_match('/^"(\\\\"|[^"])+"$/',
           str_replace("\\\\","",$local)))  return false;
    }
    if (DEBUG<1 && !(checkdnsrr($domain,"MX") ||  checkdnsrr($domain,"A")))  return false; // domain not found in DNS
    return true;
}
