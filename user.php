<?php
/*
 *	user.php handles users, sessions, rights.
 *	Logged in state is kept in $_SESSION['user_id'].
 *	Included in common.php
 *
 *	Defined:
 *		initUser() - initializes the global $USER assoc containing the current user's most used data.
 *		getUser($uid) - returns a $USER assoc containing uid,name,login,logged,gender,email,roles.
 *		actionLogout(), actionLogin()
 *		addLoginMenuBox(), addUserMenuBox()
		actionRegister()

		actionChangePassword, actionPasswordReset, actionPasswordResetForm
		actionEditProfile, handleEditProfileForm

		actionEditMotivationLetter
		actionEditAdditionalInfo, handleEditAdditionalInfoForm
 *	Security warning:
 *		If user has cookies disabled, session_id, thus access,
 *		can be sniffed through REFERER_URI.
 *		Listening to network traffic is in no way made harder.
 */
// TODO document and cleanup (table_user columns (preferowany_referat, polish names,...), roles).
define('USER_ROOT', -1);
define('USER_ANONYMOUS', -2);

require_once('user/profile.php');
require_once('user/admin.php');
require_once('user/utils.php');

/* Initializes the global $USER with the current user's commonly used data.
 * If $_GET['impersonate'] is set, first log in normally, check rights,
 * then return the impersonated $USER. */
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

/* Get a $USER assoc containing uid,name,login,logged,gender,email,roles. */
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

function actionLogout()
{
	global $PAGE, $USER;
	unset($_SESSION['user_id']);
	$USER = getUser(USER_ANONYMOUS);
	$PAGE->addMessage(_('Logged out.'), 'success');
}

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

	// Check if logged for first time
	if (!$USER['logged'])
	{
		logUser('user register');
		$PAGE->addMessage(_('Welcome! Tell us something about yourself.'), 'instruction');
		callAction('editProfile');
	}
	else
		callAction('homepage');
	$USER['logged'] = time();
	$DB->users[$USER['uid']]->update(array('logged' => $USER['logged']));
	$PAGE->addMessage(_('Successfully logged in.'), 'success');
}

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

	$link = 'http://'. $_SERVER['HTTP_HOST'] . ABSOLUTE_PATH_PREFIX ."registerConfirm%28$confirmkey%29";
	$mail = sprintf(_('A new user account has been created on %s using this e-mail address.\n'.
		'To confirm your registration, open the following link:\n%s\n\n'.
		'(If you didn\'t sign up, just ignore this email.)\n'),
			$_SERVER['HTTP_HOST'], $link);
	sendMail(_('New user account'), $mail, array(array($values['name'],$values['email'])));
	$PAGE->addMessage(_('Your account has been successfully created. '.
		'Now, click the link you received in an e-mail to confirm.<br/>'.
		'(If not, wait 15 minutes and check your spam.)'), 'success');
}

function actionRegisterConfirm($confirmkey)
{
	global $DB, $PAGE;
	if (!is_numeric($confirmkey) || $confirmkey<2)
		$PAGE->addMessage(_('The link you opened doesn\'t contain the confirmation number. '.
		'Please copy the whole link from the e-mail you received.'), 'userError');
	else
	{
		$DB->query('UPDATE table_users SET confirm=0 WHERE confirm=$1', intval($confirmkey));
		$PAGE->addMessage(_('Your registration has been successfully finished. Please log in.'), 'success');
	}
}

function actionEditMotivationLetter()
{
	if (!userCan('editMotivationLetter'))  throw new PolicyException();
	global $USER, $PAGE, $DB;
	$PAGE->title = _('Motivation letter');
	if (!assertProfileFilled())  return;

	$inputs = parseTable('
		NAME               => TYPE;
		motivationletter   => richtextarea;
	');
	$inputs['motivationletter']['description'] = sprintf(_(
			'Write (in %d - 300 words)<br/>'.
			'1. What do you expect from these workshops?<br/>'.
			'2. What are your interests in science?<br/>'.
			'3. (Optional) Would you like to make a short (15 min.) presentation?<br/>'.
			'<small>Tell us something about a topic you would choose, or (if you have no good idea)<br/>'.
			'describe as precisely as possible what would you suggest you\'d like to talk about.</br>'.
			'(This will be taken into account in case we get many good applications).</small>'),
		getOption('motivationLetterWords'));
	$form = new Form($inputs);

	if ($form->submitted())
	{
		$values = $form->fetchAndValidateValues();
		if ($form->valid)
		{
			$DB->users[$USER['uid']]->update($values);
			$PAGE->addMessage(_('Your motivation letter has been saved.'), 'success');
			logUser('user edit3');
		}
	}

	$form->values = $DB->users[$USER['uid']]->assoc($form->getColumns());
	return print $form->getHTML();
}

function actionEditAdditionalInfo($uid = null)
{
	global $USER, $DB, $PAGE;
	$edition = getOption('currentEdition');
	$admin = !is_null($uid) && $uid != $USER['uid'];
	if ($admin)
	{
		if (!userCan('adminUsers'))  throw new PolicyException();
		$uid = intval($uid);
	}
	else
	{
		if (!userCan('editAdditionalInfo'))  throw new PolicyException();
		$uid = intval($USER['uid']);
	}
	if (!$DB->edition_users($edition, $uid)->count())
		throw KnownException(_('You should first sign up as a participant or lecturer.'));

	$inputs = parseTable('
		NAME            => TYPE;     tDESCRIPTION;                                         VALIDATION
		pesel           => text;     PESEL number;                                         length(0 11),char(digit);
		address         => textarea; address <small>(for the insurance)</small>;           ;
		telephone       => text;     telephone;                                            longer(6);
		parenttelephone => text;     telephone to your parents/carers;                     ;
		staybegintime   => select;   staying time: <span class="right">from</span>;        int;
		stayendtime     => select;                 <span class="right">to</span>;          int;
		gatherplace     => select;   I\'ll join the gathering at;                          ;     default=>none;
		isselfcatered   => checkbox; accomodation and meals;                               ;
		tshirtsize      => select;   preferred t-shirt size;                               ;     default=>L;
		comments        => textarea; comments (e.g. vegetarian);                           ;
	');
	if (userIs('lecturer'))
		unset($inputs['parenttelephone']);

	$starttime = strtotime('2011/08/08 00:00');
	$mealhours = array(9=>_('breakfast'), 14=>_('dinner'), 19=>_('supper'));
	$stayoptions = array();
	// Workshops have 11 days [0..10].
	// The 0th days begins late, with a supper, the 10th day ends early, with a breakfast.
	$format = "%e. (%a) %H:%M";
	$firsttime = $starttime+(0*24+19)*60*60;
	$stayoptions[$firsttime] = strftime($format, $firsttime);
	for ($day=1; $day<10; $day++)
		foreach ($mealhours as $h=>$meal)
	{
		$time = $starttime+($day*24+$h)*60*60;
		$stayoptions[$time] = strftime($format .' ('. $meal .')', $time);
	}
	$lasttime = $starttime+(10*24+9)*60*60;
	$stayoptions[$lasttime] = strftime($format, $lasttime);
	$inputs['staybegintime']['default'] = $firsttime;
	$inputs['stayendtime']['default'] = $lasttime;
	$inputs['staybegintime']['options'] = $stayoptions;
	$inputs['stayendtime']['options']   = $stayoptions;

	$inputs['gatherplace']['options'] = array('warszawa'=>_('Warsaw PKP'),'olsztyn'=>_('Olsztyn PKP'),'none'=>_('I\'ll arrive on my own.'));
	$tshirtsizes = array('XS','S','M','L','XL','XXL');
	$inputs['tshirtsize']['options'] = array_combine($tshirtsizes, $tshirtsizes);;
	$inputs['isselfcatered']['text'] = _('on my own') .
		'<small '. getTipJS(_('applies to Olsztyn residents, for example')) .'>[?]</small>';


	$form = new Form($inputs);

	$editionColumns = array('staybegintime','stayendtime','isselfcatered');

	if ($form->submitted() && !$admin)
	{
		$values = $form->fetchAndValidateValues();
		if ($form->valid)
		{
			$editionValues = array();
			foreach ($editionColumns as $column)
			{
				$editionValues[$column] = $values[$column];
				unset($values[$column]);
			}
			$editionValues['lastmodification'] = time();
			$DB->users[$uid]->update($values);
			$DB->edition_users($edition, $uid)->update($editionValues);
			$PAGE->addMessage(_('Saved.'), 'success');
			logUser('user edit2', $uid);
		}
	}

	$r = $DB->users[$uid]->assoc('pesel,address,telephone,parenttelephone,gatherplace,tshirtsize,comments,name');
	if (is_null($r['tshirtsize']))  $r['tshirtsize'] = 'L';
	$r += $DB->edition_users($edition,$uid)->assoc(implode(',',$editionColumns));

	$PAGE->title = _('Additional info');
	if ($admin)  $PAGE->title = $r['name'] .' - '. $PAGE->title;

	if (userCan('adminUsers'))
		$PAGE->headerTitle = getUserHeader($uid, $r['name'], 'editAdditionalInfo');

	$form->values = $r;
	$form->columnWidth = '25%';
	if ($admin)
		$form->submitValue = null;
	return print $form->getHTML();
}
