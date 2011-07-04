<?php
/*
 * user/profile.php
 */

function actionChangePassword()
{	
	global $USER, $PAGE, $DB;
	if (!in_array('registered', $USER['roles']))  throw new PolicyException();
	$PAGE->title = 'Zmień hasło';
	$form = new Form(array(
		array('stare hasło', 'oldpassword', 'password'),
		array('nowe hasło', 'newpassword', 'password'),
		array('powtórz hasło', 'newpassword_repeat', 'password')
	), 'changePasswordForm');
	$form->submitValue = 'Zmień';
	$PAGE->content .= $form->getHTML();
}

function actionChangePasswordForm()
{
	global $USER, $PAGE, $DB;
	if (!in_array('registered', $USER['roles']))  throw new PolicyException();	
	$PAGE->title = 'Zmieniono hasło';
	
	$pass = $DB->users[$USER['uid']]->get('password');
	$correct = true;				
	assertOrFail($pass == passHash($_POST['oldpassword']),
		'Stare hasło się nie zgadza.', $correct);
	assertOrFail($_POST['newpassword'] == $_POST['newpassword_repeat'],
		'Nowe hasło nie zgadza się z powtórzeniem.', $correct);
		
	if ($correct)
	{
		$DB->users[$USER['uid']]->update(array('password' => passHash($_POST['newpassword'])));
		$PAGE->addMessage('Pomyślnie zmieniono hasło', 'success');
		logUser('user pass change');
		actionEditProfile();
	}
	else  actionChangePassword();
}

function actionPasswordReset()
{
	global $PAGE;
	$PAGE->title = 'Resetuj hasło';	
	$PAGE->content .= 'Wpisz swój login lub email - dostaniesz wiadomość z nowym hasłem.';
	$form = new Form(array(
		array('login', 'login', 'text'),
		array('email', 'email', 'text'),
	), 'passwordResetForm');
	$PAGE->content .= $form->getHTML();
}

function actionPasswordResetForm()
{
	global $USER, $PAGE, $DB;
	$PAGE->title = 'Zresetowano hasło';
	$PAGE->headerTitle = '';
	
	$r = $DB->query('SELECT uid,email,login FROM table_users WHERE login=$1 OR email=$2',
		$_POST['login'], $_POST['email']);
		
	if (!count($r))
	{
		$PAGE->addMessage('Nie znaleziono takiego użytkownika.', 'userError');		
		return actionPasswordReset();
	}	
		
	list($uid, $address, $login) = $r->fetch_vector();
	logUser('pass reset', $uid);
	$password = substr(sha1(uniqid('prefix', true)),0,10);
	
	$mail = "Zresetowano Ci hasło na http://". $_SERVER['HTTP_HOST'] . ABSOLUTE_PATH_PREFIX ."\n".
		"login: $login\n".
		"email: $address\n".
		"hasło: $password\n".
		"(hasło ma 10 znaków hex)\n\n".		
		"Jeśli nie wiesz o co chodzi, zgłoś nadużycie\n".
		"http://". $_SERVER['HTTP_HOST'] . ABSOLUTE_PATH_PREFIX ."reportBug\n";
	sendMail('Nowe hasło', $mail, $address);
	$DB->users[$uid]->update(array('password'=>passHash($password)));
	$PAGE->addMessage('Wysłano nowe hasło na maila.', 'success');	
}

function actionEditProfile($uid = null)
{
	global $USER, $PAGE, $DB;
	$currentEdition = getOption('currentEdition');
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
		
	$maturaYearOptions = getMaturaYearOptions();
	
	$genderOptions = array('m' => 'męski', 'f' => 'żeński');
	$rolesOptions = array(
		'admin'=>'Admin',
		'tutor'=>'Tutor',
		'jadący'=>'Jadąc'. gender('y','a',$DB->users[$uid]->get('gender'))
	);
	$roleOptions = array(
		'none'=>'Nikt',
		'uczestnik'=>'Uczestnik',
		'auczestnik'=>'Zakwalifikowany uczestnik',
		'kadra'=>'Kadra',
		'akadra'=>'Zakwalifikowana kadra',
	);
	
	$impersonate = '<a href="impersonate('. $uid .')/" '.
		getTipJS('wykonuje wszystko dokładnie, jakby Cię zalogować jako ta osoba') .'>impersonuj</a>';
		
	$schoolsAutoCompleteData = $DB->query('SELECT school FROM table_users WHERE school IS NOT NULL
		GROUP BY school HAVING count(*)>1 ORDER BY count(*) DESC')->fetch_column();
		
	$inputs = array(
		array('custom',       'impersonate',  '', 'custom' => $impersonate, 'hidden'=>!$admin),
		array('timestamp',    'registered',     'rejestracja',        true, 'hidden'=>!$admin),
		array('timestamp',    'logged',         'ostatnie logowanie', true, 'hidden'=>!$admin),
		array('text',         'name',           'imię i nazwisko',    !$admin),
		array('text',         'login',          'login',              !$admin),
		array('text',         'email',          'email',              !$admin),
		array('custom',       'password',       'hasło',              true, 'hidden'=>$admin, 'default'=>'<a href="changePassword">zmień</a>'),
		array('select',       'gender',         'rodzaj gramatyczny', 'options'=>$genderOptions),
		array('select',       'role',           'rola w obecnej edycji', !$admin, 'options'=>$roleOptions, 'notdb'=> true),
		array('checkboxgroup','roles',          'role',               'options'=>$rolesOptions, 'hidden'=>!$admin),
		array('text',         'school',         'szkoła/kierunek studiów', 'autocomplete'=>$schoolsAutoCompleteData),
		array('select',       'maturayear',     'rocznik (ściślej: rok zdania matury)', 'options'=>$maturaYearOptions, 'other'=>''),
		array('text',         'skadwieszowww',  'skąd wiesz o WWW?'),
		array('richtextarea', 'zainteresowania','zainteresowania'),
		//array('checkbox',     'isselfcatered',  'nocleg i wyżywienie', 'text'=>'we własnym zakresie <small '. getTipJS('dotyczy np. mieszkańców Olsztyna') .'>[?]</small>')
	);	
			
	$action = 'editProfile';
	if ($admin)  $action .= "($uid)";
	$form = new Form($inputs, $action);
	
	if ($form->submitted())
	{
		$values = $form->fetchValues();
		$values['maturayear'] = intval($values['maturayear']);
		$role = $values['role'];
		unset($values['role']);
		$DB->users[$uid]->update($values);		
		if ($admin)
		{
			if (isset($_POST['roles']) && is_array($_POST['roles'])) //test for empty checkboxGroup
				$roles = $_POST['roles'];
			
			if ($role == 'none')
				$DB->edition_user($currentEdition, $uid)->delete();
			else
			{
				$value = array(
					'qualified' => in_array($role, array('auczestnik', 'akadra')) ? 1 : 0,
					'lecturer' => in_array($role, array('kadra', 'akadra')) ? 1 : 0
				);
				$roles[]= $value['lecturer'] ? 'kadra' : 'uczestnik';
				if ($role == 'akadra')  $roles[]='akadra';
				
				if ($DB->edition_user($currentEdition, $uid)->count())
					$DB->edition_user($currentEdition, $uid)->update($value);
				else 
				{
					$value['edition'] = $currentEdition; $value['uid'] = $uid;
					$DB->edition_user[]= $value;
				}
			}
			
			$DB->query('DELETE FROM table_user_roles WHERE uid=$1', $uid);
			foreach ($roles as $role)
				$DB->user_roles[]= array('uid'=>$uid,'role'=>$role);
		}
		$PAGE->addMessage('Pomyślnie zmieniono profil.', 'success');
		logUser('user edit', $uid);
	}
	
	$form->values = $DB->users[$uid]->assoc($form->getColumns().',confirm');
	$roles = $DB->query('SELECT role FROM table_user_roles WHERE uid=$1', $uid);
	$form->values['roles'] = $roles->fetch_column();
	$row = $DB->edition_user($currentEdition, $uid);
	if (!$row->count())
		$form->values['role'] = 'none';
	else
	{
		$form->values['role'] =  $row->get('qualified') ? 'a' : '';
		$form->values['role'] .= $row->get('lecturer') ? 'kadra' : 'uczestnik';
	}
	if ($admin)
	{
		if ($form->values['confirm'] > 0)
			$form->values['logged'] = 'użytkownik nie potwierdził maila';
		else if ($form->values['logged'] == 0)
			$form->values['logged'] = 'użytkownik jeszcze się nie logował';
	}
	
	$name = $DB->users[$uid]->get('name');
	if ($admin)  $PAGE->title = $name. ' - profil';
	else $PAGE->title = 'Twój profil';
	if (userCan('adminUsers'))
		$PAGE->headerTitle = getUserHeader($uid, $name, 'editProfile');
	$form->submitValue = 'Zapisz';
	$form->columnWidth = '35%';
	$PAGE->content = $form->getHTML();
}

function getMaturaYearOptions()
{
	$maturaOptions = array('3. gimnazjum ', '1. klasa liceum','2. klasa liceum ', '3. klasa liceum',
		'I rok studiów', 'II  rok studiów', 'III rok studiów', 'IV rok studiów', 'V rok studiów');
	$date = getdate();
	$year = $date['year']+3; // Pierwszy element $maturaOptions ma maturę za 3 lata.
	if ($date['mon']>=9)  $year++; // Od 1 września w "nowej" klasie. (może powinno być wcześniej?)
	$maturaYearOptions = array();
	foreach ($maturaOptions as $i=>$opt)
	{
		$maturaYearOptions[strval($year)] = "$opt ($year)";
		$year--;
	}
	return $maturaYearOptions;
}
