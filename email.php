<?php
/*	email.php - defines sendMail() and validEmail(). It's large and complicated due to
 * 	the implementation of OAUTH access for sending email that reliably won't be filtered as spam.
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
 * 		'gmail' - use a gmail account (SMTP) through OAUTH -
 * 			that's the only good way I found for emails not to fall into spam.
 * 	const OAUTH_CONSUMER_KEY - usually just use your domain name (the same when registering).
 * 	const OAUTH_METHOD - 'HMAC-SHA1' or 'RSA-SHA1'
 *		const OAUTH_CONSUMER_SECRET -
 * 		if using HMAC - a ~24char string given by https://www.google.com/accounts/ManageDomains
 * 		if using RSA  - path to your .pem key
 *
 *	To make 3-legged oauth (the method here implemented) work with HMAC-SHA1 you need to:
 *	Register your domain at https://www.google.com/accounts/ManageDomains
 *	Define OAUTH_CONSUMER_SECRET with the secret you'll get there.
 *
 * To use RSA-SHA1 instead:
 *	Generate an RSA key and certificate for you application:
 *			(subj only has to contain CN=your.domain.name, other data is optional)
 *			The key is your consumerSecret, available only to the application,
 * 		the certificate is a file you'll give to Google and probably won't need later.
 * 		(Details: http://code.google.com/apis/gdata/docs/auth/authsub.html#Registered)
 * 	openssl req -x509 -nodes -days 365 -newkey rsa:1024 -sha1 \
 * 	-subj '/C=US/ST=CA/L=Mountain View/CN=www.example.com' \
 * 	-keyout myrsakey.pem -out /tmp/myrsacert.pem
 * Register your web-app with google:
 * read http://code.google.com/apis/accounts/docs/RegistrationForWebAppsAuto.html
 * goto: https://www.google.com/accounts/ManageDomains
 * (Details: http://code.google.com/apis/accounts/docs/OAuth.html#ReadyOauth)
 *
 * I added Zend/Mail/Protocol/Smtp/Auth/Xoauth.php to the Zend Package.
 */
require_once 'Zend/Oauth/Consumer.php';
require_once 'Zend/Crypt/Rsa/Key/Private.php';
require_once 'Zend/Mail/Transport/Smtp.php';
require_once 'Zend/Mail.php';
require_once 'Zend/Oauth/Token/Access.php';

function sendMail($subject, $content, $to, $isHTML = false)
{
	if (EMAIL_METHOD == 'display')
	{
		$message = "
			Gdyby nie włączone debugowanie, wysłano by maila:<br/>
			SUBJECT: <i>$subject<i><br/>
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
		$email_address = getOption('gmailOAuthEmail');
		if (!$isHTML)
			$content .= "\n__\nEmail automatycznie wysłany z ". $_SERVER['HTTP_HOST'];

		$config = new Zend_Oauth_Config();
		$config->setOptions(getGoogleOAuthOptions());
		// Could try-catch here (for 'access disallowed'?)
		$config->setToken(unserialize(base64_decode(getOption('gmailOAuthAccessToken'))));
		$config->setRequestMethod('GET');
		$url = 'https://mail.google.com/mail/b/' . $email_address . '/smtp/';

		$httpUtility = new Zend_Oauth_Http_Utility();
		$params = $httpUtility->assembleParams($url, $config);
		ksort($params);
		$oauthParams = array();
		foreach ($params as $key => $value)
			if (strpos($key, 'oauth_') === 0)
				$oauthParams []= $key . '="' . urlencode($value) . '"';
		$initClientRequest = 'GET ' . $url . ' ' . implode(',', $oauthParams);

		$config = array(
			'ssl' => 'ssl',
			'port' => '465',
			'auth' => 'xoauth',
			'xoauth_request' => base64_encode($initClientRequest)
		);
		$transport = new Zend_Mail_Transport_Smtp('smtp.gmail.com', $config);
		$mail = new Zend_Mail('UTF-8');
		if ($isHTML)  $mail->setBodyHTML($content);
		else          $mail->setBodyText($content);
		$mail->setFrom($email_address, 'Aplikacja WWW');
		if (is_array($to))
			foreach ($to as $t)
				$mail->addTo($t[1], $t[0]);
		else  $mail->addTo($to);
		$mail->setSubject("[WWW][app] $subject");
		$mail->send($transport);

		/* If you ever needed to access IMAP this way too: */
		//require_once 'Zend/Mail/Protocol/Imap.php';
		//require_once 'Zend/Mail/Storage/Imap.php';
		/*
		$imap = new Zend_Mail_Protocol_Imap('imap.gmail.com', '993', true);
		$authenticateParams = array('XOAUTH', base64_encode($initClientRequest));
		$imap->requestAndResponse('AUTHENTICATE', $authenticateParams);

		$storage = new Zend_Mail_Storage_Imap($imap);

		echo '<html><head><title>2legged OAuth test</title></head><body>';
		echo '<h1>Total messages: ' . $storage->countMessages() . "</h1>\n";

		echo 'First five messages: <ul>';
		for ($i = 1; $i <= $storage->countMessages() && $i <= 5; $i++ )
			echo '<li>' . htmlentities($storage->getMessage($i)->subject) . "</li>\n";
		echo '</ul>';
		echo '</body></html>';
		*/
	}
}

function actionFetchGmailOAuthAccessToken()
{
	global $DB, $PAGE;
	if (!userIs('admin'))  throw new PolicyException();
	logUser('redoOAuth');
	$consumer = new Zend_Oauth_Consumer(getGoogleOAuthOptions());
	if (!isset($_SESSION['REQUEST_TOKEN'])) {
		// Get Request Token and redirect to Google
		$scopes = array('https://mail.google.com/');
		$_SESSION['REQUEST_TOKEN'] = serialize($consumer->getRequestToken(array('scope' => implode(' ', $scopes))));
		$consumer->redirect();
	} else {
		// Have Request Token already, Get Access Token
		$requestToken = unserialize($_SESSION['REQUEST_TOKEN']);
		unset($_SESSION['REQUEST_TOKEN']);
		$accessToken = $consumer->getAccessToken($_GET, $requestToken);
		$accessToken = base64_encode(serialize($accessToken));
		$DB->options['gmailOAuthAccessToken']->update(array('value' => $accessToken));
		$PAGE->addMessage("Successfully received accessToken: <pre>$accessToken</pre>", 'success');
		callAction('editOptions');
	}
}

function getGoogleOAuthOptions()
{
	return array(
		'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER,
		'version' => '1.0',
		'consumerKey' => OAUTH_CONSUMER_KEY,
		'signatureMethod' => OAUTH_METHOD,
		'consumerSecret' =>
			(OAUTH_METHOD == 'RSA-SHA1') ?
				new Zend_Crypt_Rsa_Key_Private(file_get_contents(OAUTH_CONSUMER_SECRET)) :
				OAUTH_CONSUMER_SECRET,
		'callbackUrl' => getCurrentUrl(),
		'requestTokenUrl' => 'https://www.google.com/accounts/OAuthGetRequestToken',
		'userAuthorizationUrl' => 'https://www.google.com/accounts/OAuthAuthorizeToken',
		'accessTokenUrl' => 'https://www.google.com/accounts/OAuthGetAccessToken'
	);
}

function getCurrentUrl($includeQuery = true) {
  $scheme =  empty($_SERVER['HTTPS']) ? 'http' : 'https';
  $hostname = $_SERVER['SERVER_NAME'];
  $port = $_SERVER['SERVER_PORT'];
  $uri = ($includeQuery) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'];
  if (($port == '80' && $scheme == 'http') || ($port == '443' && $scheme == 'https'))
      $url = $scheme . '://' . $hostname . $uri;
  else
      $url = $scheme . '://' . $hostname . ':' . $port . $uri;
  return $url;
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
