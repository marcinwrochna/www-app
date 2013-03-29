<?php
/*
 *	user.php handles user registration, sessions, passwords, rights.
 *	Logged-in state is kept in $_SESSION['user_id'].
 *
 *	Security warning:
 *		If user has cookies disabled, session_id, thus access,
 *		can be sniffed through REFERER_URI.
 *		Listening to network traffic is in no way made harder.
 */


define('USER_ROOT', -1);
define('USER_ANONYMOUS', -2);

require_once('user/profile.php');
require_once('user/admin.php');
require_once('user/utils.php');

/**
 * Initializes the global $USER with the current user's commonly used data.
 * If $_GET['impersonate'] is set, first log in normally, check rights, then set
 * the impersonated $USER - the application will behave exactly like if he was logged in,
 * except for setting $USER['impersonatedBy'] (and when set, the addUserMenuBox() is extended).
 */
function initUser()
{
	unset($GLOBALS['USER']);
	global $USER, $DB;
	$uid = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : USER_ANONYMOUS;
	$USER = getUser($uid);
	if (isset($_GET['impersonate']))
	{
		if (!userCan('impersonate'))  throw new PolicyException();
		$USER = getUser(intval($_GET['impersonate']));
		$USER['impersonatedBy'] = $uid;
	}
}

/**
 * Returns a $USER assoc containing uid,name,login,logged,gender,email,roles.
 */
function getUser($uid)
{
	global $DB, $PAGE;
	if ($uid == USER_ANONYMOUS)
		return array(
			'uid' => USER_ANONYMOUS,
			'name' => _('Anonym'),
			'login' => 'anonymous',
			'roles' => array('public'),
			'gender' => 'm'
		);

	if (!isset($DB->users[$uid]))
		throw new KnownException(sprintf(_('User #%d doesn\'t exist'), $uid));

	$user = $DB->users[$uid]->assoc('uid,name,login,logged,gender,email');
	$user['roles'] = getUserRoles($uid);
	$user['roles'][] = 'registered';
	$user['roles'][] = 'public';
	return $user;
}

/**
 * Logouts the current user.
 */
function actionLogout()
{
	global $PAGE, $USER;
	unset($_SESSION['user_id']);
	$USER = getUser(USER_ANONYMOUS);
	$PAGE->addMessage(_('Logged out.'), 'success');
}

/**
 * Action to check credentials from addLoginMenuBox()'s form and login the user.
 * Saves login time in table_users.logged, welcomes on first login.
 */
function actionLogin()
{
	global $DB, $PAGE, $USER;
	$result = $DB->query(
		'SELECT uid FROM table_users
		 WHERE (login=$1 OR email=$1) AND password=$2',
		$_POST['login'], passHash($_POST['password'])
	);
	if (!count($result))
	{
		unset($_SESSION['user_id']);
		$PAGE->addMessage(_('Wrong username/e-mail or password.'), 'userError');
		return callAction('homepage');
	}

	$uid = intval($result->fetch());
	if ($DB->users[$uid]->get('confirm'))
	{
		unset($_SESSION['user_id']);
		$PAGE->addMessage(_('This account hasn\'t been confirmed yet. Check your mailbox.'), 'userError');
		return callAction('homepage');
	}
	$_SESSION['user_id'] = $uid;
	$USER = getUser($uid);

	callAction('homepage');
	$USER['logged'] = time();
	$DB->users[$USER['uid']]->update(array('logged' => $USER['logged']));
	$PAGE->addMessage(_('Successfully logged in.'), 'success');
}

/**
 * Menu box visible to anonymous users - log in, register, reset password.
 */
function addLoginMenuBox()
{
	global $PAGE;
	$template = new SimpleTemplate();
	?>
		<div class="menuBox" id="loginBox">
			<form method="post" action="login">
					<input class="inputText" type="text" name="login"/><br/>
					<input class="inputText"  type="password" name="password"/><br/>
					<input class="inputButton"  type="submit" value="{{Log in}}"/><br/>
					<a href="register">{{create account}}</a><br/>
					<a href="passwordReset">{{forgot password?}}</a>
			</form>
		</div>
	<?php
	$PAGE->menu .= $template->finish(true);
}

/**
 * Menu box with user-specific actions.
 * Shows a logout button
 * (and an information about impersonation if the app simulates another user).
 */
function addUserMenuBox()
{
	global $USER, $PAGE, $DB;

	$items = parseTable('
		ACTION               => tTITLE;            ICON;
		editProfile          => profile;           user-green.png;
		editMotivationLetter => motivation letter; qual.png;
		editAdditionalInfo   => additional info;   page-white-edit.png;
	');
	$items['editProfile']['perm'] = userCan('editProfile', $USER['uid']);

	$custom = '';
	if (isset($USER['impersonatedBy']))
	{
		$custom .= genderize(_('You are now logged in as another person.'), $DB->users[$USER['impersonatedBy']]->get('gender'));
		$custom .= ' <a href="..">['. _('return') .']</a>';
	}

	$logout = getIcon('poweroff.png');
	$logout = '<a class="right" href="logout" '. getTipJS(_('Log out')) .'>'. $logout .'</a>';
	$PAGE->addMenuBox($USER['name'] .'&nbsp;', $items, $custom . $logout);
}

/**
 * Form to create a new account. Sends an email with a link to actionRegisterConfirm().
 */
function actionRegister()
{
	global $DB, $PAGE;
	$PAGE->title = _('Create your account');
	$form = new Form(parseTable('
		NAME            => TYPE;     tDESCRIPTION;    VALIDATION;
		login           => text;     username;        charset(name digit),length(4 20);
		password        => password; password;        length(3 200);
		password_repeat => password; retype password; equal(password);
		name            => text;     full name;       charset(name),length(4 60);
		email           => text;     e-mail;          email;
	'));
	$form->custom = _('All fields are required. '.
		'Your e-mail will only be visible to signed-in users.<br/>'.
		'One account should correspond to one person '.
		'(e.g. don\'t create an account for another lecturer).<br/>');

	if (!$form->submitted())
		return print $form->getHTML();

	$values = $form->fetchAndValidateValues();
	$DB->query('SELECT COUNT(*) FROM table_users WHERE login=$1', $values['login']);
	$form->assert($DB->fetch_int() === 0, _('This login is already taken.'));
	$DB->query('SELECT COUNT(*) FROM table_users WHERE email=$1', $_POST['email']);
	$form->assert($DB->fetch_int() === 0, _('This email address is already registered. '.
		'If you didn\'t receive an email with confirmation, check your spam or').
		' <a href="reportBug">'. _('report a bug') .'</a>.');
	if (!$form->valid)
		return print $form->getHTML();

	$confirmKey = rand(100000,999999);

	$DB->users[]= array(
		'login' => $values['login'],
		'password' => passHash($values['password']),
		'name' => $values['name'],
		'email' => $values['email'],
		'confirm' => $confirmKey,
		'registered' => time(),
		'logged' => 0
	);
	$uid = $DB->users->lastValue();

	// ordername of 'Tom Marvolo Riddle' is 'Riddle Tom Marvolo 666'.
	$nameParts = explode(' ', $values['name']);
	array_unshift($nameParts, array_pop($nameParts));
	$nameParts[]= $uid;
	$DB->users[$uid]->update(array('ordername' => implode(' ', $nameParts)));

	$link = 'http://'. $_SERVER['HTTP_HOST'] . ABSOLUTE_PATH_PREFIX ."registerConfirm%28$confirmKey%29";
	$mail = sprintf(_('A new user account has been created on %s using this e-mail address.\n'.
		'To confirm your registration, open the following link:\n%s\n\n'.
		'(If you didn\'t sign up, just ignore this email.)\n'),
			$_SERVER['HTTP_HOST'], $link);
	$mail = str_replace('\n', "\n", $mail);
	sendMail(_('New user account'), $mail, array(array($values['name'],$values['email'])));
	$PAGE->headerTitle = '';
	$PAGE->addMessage(_('Your account has been successfully created. '.
		'Now, click the link you received in an e-mail to confirm.<br/>'.
		'(If not, wait 15 minutes and check your spam.)'), 'success');
}

/**
 * Action called when a user clicks the link in his confirmation link after creating his account.
 * @param $confirmkey a number generated by actionRegister() and in the confirmation e-mail.
 */
function actionRegisterConfirm($confirmkey = null)
{
	global $DB, $PAGE, $USER;
	if (!is_numeric($confirmkey) || $confirmkey<2)
		return $PAGE->addMessage(_('The link you opened doesn\'t contain the confirmation number. '.
		'Please copy the whole link from the e-mail you received.'), 'userError');
	$uid = $DB->query('SELECT uid FROM table_users WHERE confirm=$1', intval($confirmkey));
	if (count($uid) != 1)
		return $PAGE->addMessage(_('Invalid confirmation number.'), 'userError');
	$uid = $uid->fetch();
	if (!is_numeric($uid))
		return $PAGE->addMessage(_('Invalid confirmation number.'), 'userError');
	$DB->users[$uid]->update(array('logged' => time(), 'confirm' => 0));
	$_SESSION['user_id'] = $uid;
	$USER = getUser($uid);
	logUser('user register');
	$PAGE->addMessage(_('Welcome! Now tell us something about yourself.'), 'instruction');
	callAction('editProfile');
}

/**
 * Form to change own password.
 */
function actionChangePassword()
{
	global $USER, $PAGE, $DB;
	if (!userIs('registered'))  throw new PolicyException();
	$PAGE->title = _('Password change');
	$form = new Form(parseTable('
		NAME                => TYPE;     tDESCRIPTION;    VALIDATION;
		oldpassword         => password; old password;    ;
		newpassword         => password; new password;    length(3 200);
		newpassword_repeat  => password; type new password again; equal(newpassword);
	'));

	if (!$form->submitted())
		return print $form->getHTML();

	$values = $form->fetchAndValidateValues();
	$pass = $DB->users[$USER['uid']]->get('password');
	$form->assert($pass === passHash($values['oldpassword']), _('Old password doesn\'t match.'));
	if (!$form->valid)
		return print $form->getHTML();

	$DB->users[$USER['uid']]->update(array('password' => passHash($values['newpassword'])));
	$PAGE->addMessage(_('Password succesfully changed.'), 'success');
	logUser('user pass change');
	callAction('editProfile');
}

/**
 * Form to reset the password, given a username OR e-mail address.
 * Sends a new random password by e-mail.
 */
function actionPasswordReset()
{
	global $USER, $PAGE, $DB;
	$PAGE->title = _('Password reset');
	echo _('Type your username or e-mail address. You\'ll recevie a message with a new password.');
	$form = new Form(parseTable('
		NAME   => TYPE; tDESCRIPTION; VALIDATION;
		login  => text; username;     charset(name digit);
		email  => text; e-mail;       ;
	'));

	if (!$form->submitted())
		return print $form->getHTML();

	$values = $form->fetchAndValidateValues();
	$r = $DB->query('SELECT uid,email,login FROM table_users WHERE login=$1 OR email=$2',
		$values['login'], $values['email']);

	$form->assert(count($r), _('No account found with such login/e-mail.'));
	if (!$form->valid)
		return print $form->getHTML();

	list($uid, $address, $login) = $r->fetch_vector();
	logUser('pass reset', $uid);
	$password = substr(sha1(uniqid('prefix', true)),0,10);

	$message = sprintf(_(
			'Your password at %s has been reset.\n'.
			'username: %s\ne-mail:  %s\npassword: %s\n'.
			'(the password has 10 hex characters)\n\n'.
			'If you don\'t know what this is about, report abuse:\n%s\n'
		),
		'http://'. $_SERVER['HTTP_HOST'] . ABSOLUTE_PATH_PREFIX,
		$login, $address, $password,
		'http://'. $_SERVER['HTTP_HOST'] . ABSOLUTE_PATH_PREFIX .'reportBug\n'
	);
	$message = str_replace('\n', "\n", $message);
	sendMail(_('New password'), $message, $address);
	$DB->users[$uid]->update(array('password'=>passHash($password)));
	$PAGE->addMessage(_('An e-mail message with the new password has been sent.'), 'success');
}
