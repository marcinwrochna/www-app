<?php
/*
 * user/profile.php
 */

function actionChangePassword()
{
	global $USER, $PAGE, $DB;
	if (!in_array('registered', $USER['roles']))  throw new PolicyException();
	$PAGE->title = _('Password change');
	$form = new Form(parseTable('
		NAME                => TYPE;     tDESCRIPTION;    VALIDATION;
		oldpassword         => password; old password;    ;
		newpassword         => password; new password;    length(3 200);
		newpassword_repeat  => password; type new password again; equal(newpassword);
	'));
	//return var_dump($form->rows);

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

function actionPasswordReset()
{
	global $USER, $PAGE, $DB;
	$PAGE->title = _('Password reset');
	echo _('Type your username or e-mail address. You\'ll recevie a message with a new password.');
	$form = new Form(parseTable('
		NAME   => TYPE; tDESCRIPTION; VALIDATION;
		login  => text; username;     char(name digit);
		email  => text; e-mail;       email;
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
	sendMail(_('New password'), $message, $address);
	$DB->users[$uid]->update(array('password'=>passHash($password)));
	$PAGE->addMessage(_('An e-mail message with the new password has been sent.'), 'success');
}

function actionEditProfile($uid = null)
{
	global $USER, $PAGE, $DB;
	$currentEdition = getOption('currentEdition');
	// Edit my own profile or admin-edit someone other's profile:
	$admin = !is_null($uid);
	if ($admin)
	{
		if (!userCan('adminUsers'))  throw new PolicyException();
		$uid = intval($uid);
		if (!isset($DB->users[$uid]))
			throw new KnownException(_('User not found.'));
		$name = $DB->users[$uid]->get('name');
		$PAGE->title = $name. ' - '. _('profile');
		$PAGE->headerTitle = getUserHeader($uid, $name, 'editProfile');
	}
	else
	{
		$uid = intval($USER['uid']);
		//if (!userCan('editProfile', $uid))  throw new PolicyException();
		$admin = false;
		$PAGE->title = _('Your profile');
	}

	if (userCan('impersonate', $uid) && ($uid != $USER['uid']))
		echo '<a href="impersonate('. $uid .')/" '.
			getTipJS(_('Executes everything as if you were logged in as that person.')) .'>'.
			_('impersonate'). '</a>';

	$nadmin = $admin ? 'false' : 'true'; // Non-admins can only read some values.
	$inputs = parseTable("
		NAME            => TYPE;          tDESCRIPTION;            bREADONLY; VALIDATION;
		registered      => timestamp;     registered;              true;      ;
		logged          => timestamp;     last login;              true;      ;
		name            => text;          full name;               $nadmin;   char(name),length(3 70);
		login           => text;          username;                $nadmin;   char(name digit),length(4 20);
		email           => text;          e-mail;                  $nadmin;   email;
		password        => custom;        password;                true;      ;
		gender          => select;        grammatical gender;      false;     ;
		role            => select;        role in current edition; $nadmin;   ;              notdb;
		roles           => checkboxgroup; other roles;             $nadmin;   ;              notdb;
		school          => text;          school/university;       false;     ;
		maturayear      => select;        graduation year;         false;     int;           other;
		skadwieszowww   => text;          how do you know WWW?;    false;     ;
		zainteresowania => richtextarea;  interests;               false;     ;
	");
	$inputs['password']['default'] = '<a href="changePassword">'. _('change') .'</a>';
	if ($admin)
		unset($inputs['password']);
	else
	{
		unset($inputs['registered']);
		unset($inputs['logged']);
	}
	$inputs['gender']['options'] = array('m' => _('masculine'), 'f' => _('feminine'));
	$inputs['role']['options'] = array(
		'none'=> _('None'),
		'uczestnik'=> _('Candidate'),
		'auczestnik'=> _('Qualified participant'),
		'kadra'=> _('Lecturer'),
		'akadra'=> _('Qualified lecturer'),
	);
	$inputs['roles']['options'] = array(
		'admin'=> _('Admin'),
		'tutor'=> _('Tutor'),
		//'jadący'=> genderize(_('Qualified'), $DB->users[$uid]->get('gender'))
	);
	$inputs['school']['autocomplete'] = $DB->query('SELECT school FROM table_users WHERE school IS NOT NULL
		GROUP BY school HAVING count(*)>1 ORDER BY count(*) DESC LIMIT 150')->fetch_column();
	$inputs['maturayear']['options'] = getMaturaYearOptions();

	$form = new Form($inputs);
	$form->columnWidth = '35%';

	if ($form->submitted())
	{
		$values = $form->fetchAndValidateValues();
		if ($form->valid)
		{
			// Update roles (in table_user_roles and table_edition_user.{lecturer,qualified}.
			if ($admin)
			{
				$role = $values['role'];
				unset($values['role']);
				$roles = array();
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
						$value['edition'] = $currentEdition;
						$value['uid'] = $uid;
						$DB->edition_user[]= $value;
					}
				}

				$DB->query('DELETE FROM table_user_roles WHERE uid=$1', $uid);
				foreach ($roles as $role)
					$DB->user_roles[]= array('uid'=>$uid,'role'=>$role);
			}
			$DB->users[$uid]->update($values);
			$PAGE->addMessage(_('Saved.'), 'success');
			logUser('user edit', $uid);
		}
	}

	$form->values = $DB->users[$uid]->assoc($form->getColumns() .',"confirm"');
	$roles = $DB->query('SELECT role FROM table_user_roles WHERE uid=$1', $uid);
	$form->values['roles'] = array_intersect($roles->fetch_column(), array_keys($inputs['roles']['options']));
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
			$form->values['logged'] = _('the user hasn\'t confirmed his e-mail yet');
		else if ($form->values['logged'] == 0)
			$form->values['logged'] = _('the user hasn\'t logged in yet');
	}

	return print $form->getHTML();
}

// Returns an array of 9 most probable graduation years.
function getMaturaYearOptions()
{
	// I decided not to use the text descriptions anymore, they're imprecise and confusing.
	// (so this table's values are not actually used).
	$maturaOptions = array('3. gimnazjum ', '1. klasa liceum','2. klasa liceum ', '3. klasa liceum',
		'I rok studiów', 'II  rok studiów', 'III rok studiów', 'IV rok studiów', 'V rok studiów');
	$date = getdate();
	$year = $date['year']+3; // The first element of $maturaOptions graduates in 3 years.
	if ($date['mon']>=9)  $year++; // We consider the 1st of September to be the threshold (should we?).
	$maturaYearOptions = array();
	foreach ($maturaOptions as $i=>$opt)
	{
		$maturaYearOptions[$year] = $year; // ." ($opt)";
		$year--;
	}
	return $maturaYearOptions;
}
