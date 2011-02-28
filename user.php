<?php
/*
	user.php handles users,sessions,authors,rights
	Included in common.php
	Defined:			
		initUser() - initialize global $USER
		actionLogout, actionLogin
		addLoginMenuBox(), addUserMenuBox()
		actionRegister()
		
		actionChangePassword, actionPasswordReset, actionPasswordResetForm
		actionEditProfile, handleEditProfileForm

		actionShowQualificationStatus
		actionEditMotivationLetter		
		actionEditAdditionalInfo, handleEditAdditionalInfoForm
	Security warning:
		If user has cookies disabled, session_id, thus access,
		can be sniffed through REFERER_URI.
		Listening to network traffic is in no way made harder.
*/
define('USER_ROOT', -1);
define('USER_ANONYMOUS', -2);

require_once('user/profile.php');
require_once('user/admin.php');
require_once('user/utils.php');

function initUser($impersonating = false)
{			
	unset($GLOBALS['USER']);
	global $USER, $DB, $PAGE;
	
	if ($impersonating === false)
		$uid = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : USER_ANONYMOUS;
	else
		$uid = $impersonating;
	
	if ($uid == USER_ANONYMOUS)
		$USER = array(
			'uid' => USER_ANONYMOUS,
			'name' => 'Anonim',
			'login' => 'anonymous',
			'roles' => array('public'),	
			'gender' => 'm'
		);
	else	
	{
		$USER = $DB->users[$uid]->assoc('uid,name,login,logged,gender,email');
		$roles = $DB->query('SELECT role FROM table_user_roles WHERE uid=$1', $uid);
		$USER['roles'] = $roles->fetch_column();
		$USER['roles'][] = 'registered';
		$USER['roles'][] = 'public';
	}
	
	if ($impersonating === false && isset($_GET['impersonate']))
	{
		if (!userCan('impersonate'))  throw new PolicyException();
		initUser(intval($_GET['impersonate']));
		global $USER;
		$USER['impersonatedBy'] = $uid;
	}
}

function actionLogout()
{
	unset($_SESSION['user_id']);
	global $PAGE;
	$PAGE ->addMessage('Pomyślnie wylogowano.', 'success');
	initUser();
}

function actionLogin()
{	
	global $DB, $PAGE;
	$query = 'SELECT uid FROM table_users WHERE (login=$1 OR email=$1) AND password=$2 AND confirm=0';
	$result = $DB->query($query, $_POST['login'], passHash($_POST['password']));
	if (!count($result))
	{
		unset($_SESSION['user_id']);
		$PAGE->addMessage('Błędny login lub hasło.', 'userError');
		actionHomepage();
		return;
	}
	
	$_SESSION['user_id'] = intval($result->fetch());	
	initUser();
	global $USER;
	
	// Check if logged for first time
	if (!$USER['logged'])  
	{
		logUser('user register');
		$PAGE->addMessage('Napisz teraz coś o sobie.', 'instruction');			
		actionEditProfile();
	}		
	else 
		actionHomepage();
	$USER['logged'] = time();
	$DB->users[$USER['uid']] = array('logged' => $USER['logged']);
	$PAGE->addMessage('Pomyślnie zalogowano', 'success');
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
					<input class="inputButton"  type="submit" value="Zaloguj"/><br/>
					<a href="register">zarejestruj się</a><br/>
					<a href="passwordReset">zapomniałem hasła</a>				
			</form>
		</div>
	<?php
	$PAGE->menu .= $template->finish();
}

function addUserMenuBox()
{
	global $USER, $PAGE, $DB;
	$items = array(
		array('profil',         'editProfile',             'user-green.png', userCan('editProfile',$USER['uid'])),
		array('kwalifikacja',   'showQualificationStatus', 'qual.png'           ),
		array('dodatkowe dane', 'editAdditionalInfo',      'page-white-edit.png')
	);
	$custom = '';
	if (isset($USER['impersonatedBy']))
		$custom .= str_replace('%', gender('y','a',$DB->users[$USER['impersonatedBy']]->get('gender')),
			'Jesteś teraz zalogowan%<br/> jako inna osoba. <a href="..">[wróć]</a>');
		
	$logout = getIcon('poweroff.png');
	$logout = '<a class="right" href="logout" '. getTipJS('wyloguj') .'>'. $logout .'</a>';
	$PAGE->addMenuBox($USER['name'] .'&nbsp;', $items, $custom . $logout);	
}

function actionRegister()
{
	$inputs = array(
		array('text', 'login', 'login'),
		array('password', 'password', 'hasło'),
		array('password', 'password_repeat', 'powtórz hasło'),
		array('text', 'name', 'imię i nawisko'),
		array('text', 'email', 'email')
	);
	
	global $PAGE;	
	$PAGE->title = 'Zarejestruj się';
	$form = new Form($inputs, 'registerForm');
	$form->custom = 'Wszystkie pola są obowiązkowe. Email będzie widoczny tylko dla
		zarejestrowanych.<br/>Jedno konto powinno odpowiadać jednej osobie.<br/>';
	$PAGE->content .= $form->getHTML();
}

function actionRegisterConfirm($confirmkey)
{
	global $DB, $PAGE;
	$DB->query('UPDATE table_users SET confirm=0 WHERE confirm=$1', $confirmkey);
	$PAGE->addMessage('Pomyślnie dokończono rejestrację. Możesz się teraz zalogować.', 'success');
	return;
}

function actionRegisterForm()
{
	global $DB, $PAGE;
	$PAGE->title = 'Rejestracja';
	
	$correct = true;				
	assertOrFail(strlen(trim($_POST['login'])),    'Login jest pusty.', $correct);
	assertOrFail(strlen(trim($_POST['password'])), 'Hasło jest puste.', $correct);
	assertOrFail(validEmail($_POST['email']), 'Podany email nie jest poprawny.', $correct);		
	assertOrFail($_POST['password'] === $_POST['password_repeat'], 'Hasło nie zgadza się z powtórzeniem.', $correct);
		
	$DB->query('SELECT COUNT(*) FROM table_users WHERE login=$1', $_POST['login']);
	assertOrFail($DB->fetch_int() === 0, 'Login już jest w użyciu.', $correct);
		
	$DB->query('SELECT COUNT(*) FROM table_users WHERE email=$1', $_POST['email']);
	assertOrFail($DB->fetch_int() === 0, 'Podany adres email już jest zarejestrowany. Jeżeli nie masz
		maila z potwierdzeniem,	sprawdź spam lub <a href="reportBug">zgłoś problem</a>.',
		$correct);
	
	if (!$correct)
	{
		actionRegister();
		return;
	}
	
	$confirmkey = rand(100000,999999);
	
	$DB->users[]= array(
		'login' => htmlspecialchars($_POST['login']),
		'password' => passHash($_POST['password']),
		'name' => htmlspecialchars($_POST['name']),
		'email' => $_POST['email'],
		'confirm' => $confirmkey,
		'registered' => time(),
		'logged' => 0,
		'roles' => 0
	);
	$uid = $DB->users->lastValue();

	$roles = explode(',',getOption('newUserRoles'));
	foreach ($roles as $role)
		$DB->user_roles[]= array('uid'=>$uid, 'role'=>trim($role));
			
	$link = 'http://'. $_SERVER['HTTP_HOST'] . ABSOLUTE_PATH_PREFIX ."registerConfirm($confirmkey)";
	$mail = "Zarejestrowano nowe konto na ". $_SERVER['HTTP_HOST'].	" używając tego emaila.\n".
			"Aby potwierdzić, otwórz poniższy link:\n".
			$link ."\n".
			"Jeśli nie wiesz o co chodzi, po prostu usuń tego maila.\n";
	sendMail("Nowe konto", $mail, array(array($_POST['name'],$_POST['email'])));
	$PAGE->addMessage('Pomyślnie utworzono nowe konto. Kliknij teraz w link
		otrzymany przez email by dokończyć rejestrację.<br/>W razie braku
		zaczekaj 15 minut i sprawdź spam.', 'success');
}


function actionShowQualificationStatus()
{
	if (!userCan('showQualificationStatus'))  throw new PolicyException();
	global $USER, $PAGE, $DB;
	$PAGE->title = 'Kwalifikacja';
	if (!assertProfileFilled())  return;
		
	$inputs = array(
		array('type'=>'richtextarea', 'name'=>'motivationletter', 'description'=>
				'<h3>List motywacyjny</h3>
				Napisz ('. getOption('motivationLetterWords') .'-300 słów)<br/>
				1. Czego oczekuję od Warsztatów?<br/>
				2. Jakie są moje zainteresowania naukowe?<br/>'),
		array('type'=>'textarea', 'name'=>'proponowanyreferat', 'description'=>
			'Proponowany temat referatu<br/>
			<small>W przypadku dużej liczby dobrych zgłoszeń istotna będzie
		chęć wygłoszenia krótkiego (15 min.) referatu. Jeśli masz pomysł na
		taki referat - opisz go w zgłoszeniu, jeśli chciałbyś coś opowiedzieć,
		ale nie masz konkretnego pomysłu - opisz możliwie dokładnie swoje
		zainteresowania, a postaramy się zasugerować Ci temat do
		zreferowania.</small>')
	);
		
	$form = new Form($inputs);

	if ($form->submitted())
	{
		$DB->users[$USER['uid']] = $form->fetchValues();
		$PAGE->addMessage('Pomyślnie zapisano list motywacyjny.', 'success');
		logUser('user edit3');		
	}	
	
	showQualificationStatus();
	
	$form->values = $DB->users[$USER['uid']]->assoc($form->getColumns());
	$form->submitValue = 'Zapisz';
	$PAGE->content .= $form->getHTML();
}


function showQualificationStatus()
{
	global $USER,$DB,$PAGE;
	$data = $DB->users[$USER['uid']]->assoc('motivationletter,proponowanyreferat');	
	$template = new SimpleTemplate();
	?>
		<h3>Status</h3>
		<p>
		<?php
			// Sprawdź istnienie i długość listu motywacyjnego.
			$length = strlen(trim(strip_tags($data['motivationletter'])));
			$words = str_word_count(strip_tags($data['motivationletter']));
			if (!$length)
				echo getIcon('arrow-small.gif') .' Napisz list motywacyjny.';
			else if ($words < getOption('motivationLetterWords'))
				echo getIcon('arrow-small.gif') .' Napisz dłuższy list motywacyjny. ('.
					'napisał'. gender('e') .'ś '.
					$words .' < '. getOption('motivationLetterWords') .' słów)';
			else
				echo getIcon('checkmark.gif') .' List motywacyjny ok.';
			echo '<br/>';
			
			// Sprawdź liczbę i długość zapisanych warsztatów.
			$result = $DB->query('SELECT COUNT(*) AS count, SUM(duration) AS sum
				FROM table_workshops w, table_workshop_user wu
				WHERE w.status=4 AND wu.wid=w.wid AND wu.uid=$1', $USER['uid']);
			$result = $result->fetch_assoc();			
			if ($result['count']<4)
				echo getIcon('arrow-small.gif') .' Zarejestruj się na więcej warsztatów.'.
					' (masz '. $result['count']. ' < 4)';
			else
				echo getIcon('checkmark.gif') .' Zapisy na warsztaty ok.';
			echo '<br/>';
			
		?>
		</p>		
	<?php
	$PAGE->content .= $template->finish();	
}

function actionEditAdditionalInfo($uid = null)
{
	global $USER, $DB, $PAGE;
	if (!userCan('editAdditionalInfo'))  throw new PolicyException();
	$admin = true;
	if (is_null($uid))
	{
		$uid = intval($USER['uid']);
		$admin = false;
		if (!in_array('registered', $USER['roles']))  throw new PolicyException();
	}
	else
	{
		$uid = intval($uid);
		if (!userCan('adminUsers'))  throw new PolicyException();
	}
	
	$tshirtsizes = array('XS','S','M','L','XL','XXL');
	$tshirtsizes = array_combine($tshirtsizes,$tshirtsizes);	
	
	$starttime = strtotime('2010/08/19 00:00');
	$mealhours = array(9=>'śniadanie', 14=>'obiad', 19=>'kolacja');
	$stayoptions = array();
	// Warsztaty mają 11 dni [0..10].
	// Dzień zero zaczyna się chyba od kolacji, dzień 10 kończy na śniadaniu.
	$stayoptions[19] = strftime("%e. (%a)", $starttime+19*60*60);
	for ($i=1; $i<10; $i++)
		foreach ($mealhours as $h=>$meal)
	{
		$stayoptions[$i*24+$h] = strftime("%e. (%a) $meal",
			$starttime+($i*24+$h)*60*60);
	}
	$stayoptions[10*24+9] = strftime("%e. (%a)", $starttime+(10*24+9)*60*60);
	
	$gatherPlaceOptions = array('warszawa'=>'Warszawa','olsztyn'=>'Olsztyn','none'=>'we własnym zakresie');
	
	$inputs = array(		
		array('description'=>'PESEL', 'name'=>'pesel', 'type'=>'text'),
		array('description'=>'adres zameldowania <small>(do ubezpieczenia)</small>', 'name'=>'address', 'type'=>'textarea'),
		array('description'=>'telefon', 'name'=>'telephone', 'type'=>'text'),
		array('description'=>'telefon do rodziców/opiekunów', 'name'=>'parenttelephone', 'type'=>'text'),
		array('description'=>'termin przyjazdu: <span class="right">od</span>', 'name'=>'staybegin',
			'type'=>'select', 'options'=>$stayoptions, 'default'=>19,      'filter'=>'int'),
		array('description'=>'                  <span class="right">do</span>', 'name'=>'stayend',
			'type'=>'select', 'options'=>$stayoptions, 'default'=>10*24+9, 'filter'=>'int'),
		array('description'=>'miejsce zbiórki', 'name'=>'gatherplace','type'=>'select',
			'options'=>$gatherPlaceOptions, 'default'=>'none'),
		array('description'=>'nocleg i wyżywienie', 'name'=>'isselfcatered',
			'type'=>'checkbox', 'text'=>'we własnym zakresie <small '.
				getTipJS('dotyczy np. mieszkańców Olsztyna') .'>[?]</small>'),
		array('description'=>'preferowany rozmiar koszulki', 'name'=>'tshirtsize', 'type'=>'select',
			'options'=>$tshirtsizes, 'other'=>'', 'default'=>'L'),
		array('description'=>'uwagi dodatkowe (np. wegetarianie)', 'name'=>'comments', 'type'=>'textarea'),		
	);	
	
	$form = new Form($inputs);
	
	if ($form->submitted() && !$admin)
	{
		$DB->users[$uid] = $form->fetchValues();
		$PAGE->addMessage('Pomyślnie zapisano dane.', 'success');
		logUser('user edit2', $uid);		
	}
	
	$columns = $form->getColumns();
	$columns .= ',name';
	$r = $DB->users[$uid]->assoc($columns);
	if (is_null($r['tshirtsize']))  $r['tshirtsize'] = 'L';
	
	$PAGE->title = 'Dodatkowe dane';
	if ($admin)  $PAGE->title = $r['name'] .' - dodatkowe dane';	
	
	if (userCan('adminUsers'))
		$PAGE->headerTitle = getUserHeader($uid, $r['name'], 'editAdditionalInfo'); 	
		
	$form->values = $r;
	$form->columnWidth = '25%';
	$form->submitValue = $admin ? null : 'Zapisz';		
	$PAGE->content .= $form->getHTML();			
}
