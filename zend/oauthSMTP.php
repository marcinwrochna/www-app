<?php
$path = dirname(__FILE__);
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
require_once 'Zend/Oauth/Consumer.php';
require_once 'Zend/Crypt/Rsa/Key/Private.php'; 
require_once 'Zend/Mail/Transport/Smtp.php';
require_once 'Zend/Mail.php';
require_once 'Zend/Oauth/Token/Access.php';

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

function getGmailOAuthOptions()
{
	return array(
		'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER,
		'version' => '1.0',
		'consumerKey' => 'warsztatywww.nstrefa.pl',
		'signatureMethod' => 'RSA-SHA1',
		'consumerSecret' => new Zend_Crypt_Rsa_Key_Private(file_get_contents('zend/www_rsaprivatekey.pem')),
		'callbackUrl' => getCurrentUrl(),
		'requestTokenUrl' => 'https://www.google.com/accounts/OAuthGetRequestToken',
		'userAuthorizationUrl' => 'https://www.google.com/accounts/OAuthAuthorizeToken',
		'accessTokenUrl' => 'https://www.google.com/accounts/OAuthGetAccessToken'
	);
}


function actionFetchGmailOAuthAccessToken()
{
	global $DB, $PAGE, $USER;
	if (!in_array('admin', $USER['roles']))  throw new PolicyException();
	logUser('redoOAuth');
	$consumer = new Zend_Oauth_Consumer(getGmailOAuthOptions());
	if (!isset($_SESSION['REQUEST_TOKEN'])) {
		// Get Request Token and redirect to Google
		$_SESSION['REQUEST_TOKEN'] = serialize($consumer->getRequestToken(array('scope' => implode(' ', array('https://mail.google.com/')))));		
		$consumer->redirect(); //redirect(array('hd'=>'default')) ?
		//exit;
	} else {
		// Have Request Token already, Get Access Token    
		$requestToken = unserialize($_SESSION['REQUEST_TOKEN']);
		unset($_SESSION['REQUEST_TOKEN']);
		$accessToken = $consumer->getAccessToken($_GET, $requestToken);
		$accessToken = base64_encode(serialize($accessToken));
		$DB->options['gmailOAuthAccessToken']->update(array('value' => $accessToken));		
		$PAGE->addMessage("Pomyślnie otrzymano accessToken: <pre>$accessToken</pre>", 'success');			
	} 
}

function sendMail($subject, $content, $to, $isHTML = false)
{
	$email_address = getOption('gmailOAuthEmail');
	if (!$isHTML)
		$content .= "\n__\nEmail automatycznie wysłany z ". $_SERVER['HTTP_HOST'];
	/* $from = 'noreply@warsztatywww.nstrefa.pl';
	  ini_set ("sendmail_from",$from);
	  mail($to, "[WWW][app] $subject", $content , "From: $from", "-f$from"); */
		
	$config = new Zend_Oauth_Config();
	$config->setOptions(getGmailOAuthOptions());
	// Could try-catch here
	debug(getOption('gmailOAuthAccessToken'));
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
}

//require_once 'Zend/Mail/Protocol/Imap.php';
//require_once 'Zend/Mail/Storage/Imap.php';
/*  $imap = new Zend_Mail_Protocol_Imap('imap.gmail.com', '993', true);
  $authenticateParams = array('XOAUTH', base64_encode($initClientRequest));
  $imap->requestAndResponse('AUTHENTICATE', $authenticateParams);

  
  $storage = new Zend_Mail_Storage_Imap($imap);
 
  echo '<html><head><title>2legged OAuth test</title></head><body>';
  echo '<h1>Total messages: ' . $storage->countMessages() . "</h1>\n";

  echo 'First five messages: <ul>';
  for ($i = 1; $i <= $storage->countMessages() && $i <= 5; $i++ ){ 
    echo '<li>' . htmlentities($storage->getMessage($i)->subject) . "</li>\n";
  }
  echo '</ul>';
	echo '</body></html>';*/
