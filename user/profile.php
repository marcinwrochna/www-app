<?php
/*
 * user/profile.php
 */

function getAvatarPath($uid = null)
{
	if (is_null($uid))
		return 'fineuploader/avatars/';
	else
		return 'fineuploader/avatars/user'. $uid .'.jpg';
}

// Unfinished function for showing someone's profile (to anyone registered).
function actionShowProfile($uid = null)
{
	global $USER, $PAGE, $DB;
	$currentEdition = getOption('currentEdition');
	$own = is_null($uid) || ($uid == $USER['uid']);
	$uid = $own ? $USER['uid'] : intval($uid);
	if (!userCan('showProfile', $uid))  throw new PolicyException();
	if (!isset($DB->users[$uid]))
		throw new KnownException(_('User not found.'));

	$user = $DB->users[$uid]->assoc('*');

	$gender = $user['gender'];
	$PAGE->title = $user['name']. ' - '. _('profile');
	$PAGE->headerTitle = '<span class="left">'. $uid .'.&nbsp;</span>';
	$PAGE->headerTitle .= '<h2>'. $user['name']. ' - '. _('profile') .'</h2>';

	$avatar = getAvatarPath($uid);
	if (file_exists($avatar))
		$user['avatar'] = '<img class="avatar" src="'. $avatar .'?'. filemtime($avatar) .'" />';
	else
		$user['avatar'] = '';

	//print '<a href="mailto:'. $user['name'] .' <'. $user['email'] .'>">'. $user['email'] .'</a><br/>';

	$gradOptions = getGraduationYearOptions(true);
	if (isset($gradOptions[$graduation]))
		$user['graduationyear'] = $gradOptions[$user['graduationyear']];

	$user['interests'] = parseUserHTML($user['interests']);
	$user['motivationletter'] = parseUserHTML($user['motivationletter']);
	$user['badge'] = getUserBadge($uid, true);
	$user['school'] = parseUserHTML($user['school']);

	$template = new SimpleTemplate($user);
	?>
		<span class="left">%badge% (%login%)</span>
		<span class="right">%school% - ({{graduation year}}: %graduationyear%)</span><br/>
		%avatar%

		<h3 onclick='$("#interests_sign").toggle(); $("#interests").toggle("fast");' style='cursor: pointer'>
			 <span id='interests_sign'>+</span> {{Interests}}</h3>
		<div class="descriptionBox" id="interests" style="display:none">%interests%</div>
	<?php
	if (userCan('adminUsers'))
	{
	?>
		<h3 onclick='$("#motivation_sign").toggle(); $("#motivation").toggle("fast");' style='cursor: pointer'>
			<span id='motivation_sign'>+</span> {{Motivation letter}}</h3>
		<div class="descriptionBox" id="motivation" style="display:none">%motivationletter%</div>
	<?php
		// TODO show roles, howdoyouknowus
		print '<br/>';
		print genderize(_('registered'), $gender) .': ';
		print fixedStrftime('%Y-%m-%d %H:%M (%ago%)', $user['registered']) ;
		print ', &nbsp;';
		print _('last login') .': ';
		if ($user['confirm'] > 0)
			print _('the user hasn\'t confirmed his e-mail yet');
		else if ($user['logged'] == 0)
			print _('the user hasn\'t logged in yet');
		else
			print fixedStrftime('%Y-%m-%d %H:%M (%ago%)', $user['logged']);
		print '<br/>';
	}

	echo $template->finish(true);
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
		avatar          => custom;        photo;                   true;      ;
		name            => text;          full name;               $nadmin;   charset(name),length(3 70);
		login           => text;          username;                $nadmin;   charset(name digit),length(3 20);
		email           => text;          e-mail;                  $nadmin;   email;
		password        => custom;        password;                true;      ;
		gender          => select;        grammatical gender;      false;     ;
		role            => select;        role in current edition; $nadmin;   ;              notdb;
		roles           => checkboxgroup; other roles;             $nadmin;   ;              notdb;
		school          => text;          school/university;       false;     ;
		graduationyear  => select;        high school graduation year; false; int;           other=>;
		howdoyouknowus  => text;          how do you know us?;     false;     ;
		interests       => richtextarea;  interests;               false;     ;
	");

	$inputs['avatar']['default'] = '<div onclick=\'$("#avatarSign").toggle(); $("#avatarBox").toggle("fast");\' style="cursor: pointer">
			 <span id="avatarSign">+</span> '. _('change') .'...</div><div id="avatarBox">'.
			 '<div id="avatarUpload"></div> <img id="avatar" ';
	$src = getAvatarPath($uid);
	if (file_exists($src))
		$inputs['avatar']['default'] .= 'src="'. $src .'?'. filemtime($src) .'"';
	$inputs['avatar']['default'] .=
			 ' /> </div>';
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
		'candidate'=> _('Candidate'),
		'qualified_candidate'=> _('Qualified candidate'),
		'lecturer'=> _('Lecturer'),
		'qualified_lecturer'=> _('Qualified lecturer'),
	);
	$inputs['roles']['options'] = array(
		'admin'=> _('Admin'),
		'tutor'=> _('Tutor'),
	);
	$inputs['school']['autocomplete'] = $DB->query('SELECT school FROM table_users WHERE school IS NOT NULL
		GROUP BY school HAVING count(*)>1 ORDER BY count(*) DESC LIMIT 150')->fetch_column();
	$inputs['graduationyear']['options'] = getGraduationYearOptions();

	$form = new Form($inputs);
	$form->columnWidth = '35%';

	if ($form->submitted())
	{
		$values = $form->fetchAndValidateValues();
		if ($form->valid)
		{
			// Update roles (in table_user_roles and table_edition_users.{lecturer,qualified}.
			if ($admin)
			{
				$role = $values['role'];
				unset($values['role']);
				$roles = array();
				if (isset($_POST['roles']) && is_array($_POST['roles'])) //test for empty checkboxGroup
					$roles = $_POST['roles'];

				if ($role == 'none')
					$DB->edition_users($currentEdition, $uid)->delete();
				else
				{
					$value = array(
						'qualified' => (strpos($role, 'qualified') !== false) ? 1 : 0,
						'lecturer' =>  (strpos($role, 'lecturer')  !== false) ? 1 : 0,
					);

					if ($DB->edition_users($currentEdition, $uid)->count())
						$DB->edition_users($currentEdition, $uid)->update($value);
					else
					{
						$value['edition'] = $currentEdition;
						$value['uid'] = $uid;
						$DB->edition_users[]= $value;
					}
				}

				$DB->query('DELETE FROM table_user_roles WHERE uid=$1', $uid);
				foreach ($roles as $role)
					$DB->user_roles[]= array('uid'=>$uid,'role'=>$role);
			}
			// ordername of 'Tom Marvolo Riddle' is 'Riddle Tom Marvolo 666'.
			if (isset($values['name']))
			{
				$nameParts = explode(' ', $values['name']);
				array_unshift($nameParts, array_pop($nameParts));
				$nameParts[]= $uid;
				$values['ordername'] = implode(' ', $nameParts);
			}

			$DB->users[$uid]->update($values);
			$PAGE->addMessage(_('Saved.'), 'success');
			logUser('user edit', $uid);
		}
	}

	$form->values = $DB->users[$uid]->assoc($form->getColumns() .',"confirm"');
	if (isset($form['registered']))
		$form['registered']['description'] = genderize($form['registered']['description'], $form->values['gender']);
	$roles = $DB->query('SELECT role FROM table_user_roles WHERE uid=$1', $uid);
	$form->values['roles'] = array_intersect($roles->fetch_column(), array_keys($inputs['roles']['options']));
	$row = $DB->edition_users($currentEdition, $uid);
	if (!$row->count())
		$form->values['role'] = 'none';
	else
	{
		$form->values['role'] =  $row->get('qualified') ? 'qualified_' : '';
		$form->values['role'] .= $row->get('lecturer') ? 'lecturer' : 'candidate';
	}
	if ($admin)
	{
		if ($form->values['confirm'] > 0)
		{
			$form->values['logged'] = _('the user hasn\'t confirmed his e-mail yet');
			$form['logged']['type'] = 'text';
		}
		else if ($form->values['logged'] == 0)
		{
			$form->values['logged'] = _('the user hasn\'t logged in yet');
			$form['logged']['type'] = 'text';
		}
	}

	// Avatar uploader.
	$uploader = array(
		'request' => array(
			'endpoint' => 'fineuploader/handle.php',
			'params' => array('uid' => $uid)
		),
		'validation' => array(
			'allowedExtensions' => array('jpeg', 'jpg'),
			'sizeLimit' => 5 * 1024 * 1024, // 5M
		),
		'debug' => true, // log messages in browser console
		'multiple' => false,
		'text' => array('uploadButton' => _('Upload'))
	);
	$PAGE->jsOnLoad .= 'var avatarUploader = $("#avatarUpload").fineUploader('.
		json_encode($uploader) .')'.
		'.on("complete", function(event, id, fileName, responseJSON) {
			if (responseJSON.success) {
				$("#avatar").attr("src",
					"'. getAvatarPath($uid) .'?"+ new Date().getTime());
				setTimeout(function(){ $("#avatarUpload").fineUploader("reset"); }, 3000);
			} });
	';

	return print $form->getHTML();
}

function actionHandleAvatarUpload()
{
	exit;
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

	$r = $DB->users[$uid]->assoc('pesel,address,telephone,parenttelephone,gatherplace,tshirtsize,comments,name');

	$PAGE->title = _('Additional info');
	if ($admin)  $PAGE->title = $r['name'] .' - '. $PAGE->title;

	if (userCan('adminUsers'))
		$PAGE->headerTitle = getUserHeader($uid, $r['name'], 'editAdditionalInfo');

	if (!$DB->edition_users($edition, $uid)->count())
	{
		if ($admin)
			$PAGE->addMessage(_('The user hasn\'t signed up for this edition yet.'));
		else
			$PAGE->addMessage(_('You should first sign up as a participant or lecturer.'));
		return;
	}

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

	$data = $DB->editions[$edition]->assoc('*');
	$starttime = $data['begintime'];
	$starttime -= 60*60*strftime('%H', $starttime);
	$hours = explode(' ', $data['importanthours']);
	$stayoptions = array();
	$inputs['staybegintime']['default'] = false;
	for ($day=0; $starttime + $day*24*60*60 <= $data['endtime']; $day++)
		foreach ($hours as $h)
	{
		$time = $starttime+($day*24+$h)*60*60;
		if ($time >= $data['begintime'] && $time <= $data['endtime'])
			$stayoptions[$time] = strftime('%e. (%a) %H:%M', $time);
		if (!$inputs['staybegintime']['default'])
			$inputs['staybegintime']['default'] = $time;
		$inputs['stayendtime']['default'] = $time;
	}
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

	if (is_null($r['tshirtsize']))  $r['tshirtsize'] = 'L';
	$r += $DB->edition_users($edition,$uid)->assoc(implode(',',$editionColumns));

	$stayoptions = array_keys($stayoptions);
	if (!in_array($r['staybegintime'], $stayoptions))
		$r['staybegintime'] = $stayoptions[0];
	if (!in_array($r['stayendtime'], $stayoptions))
		$r['stayendtime'] = $stayoptions[count($stayoptions) - 1];

	$form->values = $r;
	$form->columnWidth = '25%';
	if ($admin)
		$form->submitValue = null;
	return print $form->getHTML();
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
	$inputs['motivationletter']['description'] = sprintf(genderize(_(
			'Write (in %d - %d words)<br/>'.
			'1. What do you expect from these workshops?<br/>'.
			'2. What are your interests in science?<br/>'.
			'3. (Optional) Would you like to make a short (15 min.) presentation?<br/>'.
			'<small>Tell us something about a topic you would choose, or (if you have no good idea)<br/>'.
			'describe as precisely as possible what would you suggest you\'d like to talk about.<br/>'.
			'(This will be taken into account in case we get many good applications).</small>')),
		getOption('motivationLetterWords'), 300);
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


// Returns an array of 9 most probable graduation years.
function getGraduationYearOptions($withComment = false)
{
	// I decided not to use the text descriptions anymore, they're imprecise and confusing.
	// (so this table's values are not actually used).
	$classOptions = array('3. gimnazjum ', '1. klasa liceum','2. klasa liceum ', '3. klasa liceum',
		'I rok studiów', 'II  rok studiów', 'III rok studiów', 'IV rok studiów', 'V rok studiów');
	$date = getdate();
	$year = $date['year']+3; // The first element of $classOptions graduates in 3 years.
	if ($date['mon']>=9)
		$year++; // We consider the 1st of September to be the threshold (should we?).
	$graduationYearOptions = array();
	foreach ($classOptions as $i=>$opt)
	{
		$s = $year;
		if ($withComment)  $s .= " (~$opt)";
		$graduationYearOptions[$year] = $s;
		$year--;
	}
	return $graduationYearOptions;
}
