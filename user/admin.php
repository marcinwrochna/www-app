<?php
/*
 * user/admin.php
 */
 // TODO review and translate.

function addAdminMenuBox()
{
	global $PAGE, $USER;
	$items = parseTable('
		ACTION           => tTITLE;                    ICON;
		adminUsers       => users;                     group.png;
		listAllWorkshops => all workshops;             bricks.png;
		showCorrelation  => correlations;              table.png;
		editOptions      => settings;                  wrench.png;
		showLog          => log;                       time.png;
		listPersonalData => list of personal data;
		listArrivalData  => list of arrival data;
		listDailyCounts  => list of meal data;
		showPointsTable  => summary of points;
	');
	// TODO: join actions {showCorrelation,list*,showPointsTable} into one tabbed window showSummary.
	$items['listPersonalData']['perm']
		= $items['listArrivalData']['perm']
		= $items['listDailyCounts']['perm']
		= $items['showPointsTable']['perm']
		= in_array('admin', $USER['roles']);
	$PAGE->addMenuBox(_('Administration'), $items);
}

function actionAdminUsers($filterBy = null)
{
	global $USER, $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();

	$roledefs = parseTable('
		ROLE   => tSHORT; tDESCRIPTION;
		admin  => Adm;    administrator;
		tutor  => T;      tutor;
	');

	$headers = parseTable('
		COLUMN            => tDESCRIPTION; ORDER;
		uid               => uid;          u.uid;
		name              => full name;    u.ordername;
		email             => e-mail;       u.email;
		roles             => roles;	       rolecount DESC;
		lecturer          => L;            eu.lecturer DESC;
		qualified         => q;            eu.qualified DESC;
		motivationletter  => letter;       length(u.motivationletter) DESC;
	');
	$headers['motivationletter']['description'] .=
		'<small '. getTipJS(_('words in motivation letter')) .'>[?]</small>';


	$PAGE->title = _('Users administration');
	$users = $DB->query('
		SELECT u.uid, u.name, u.email, eu.lecturer, eu.qualified, u.motivationletter,
			(SELECT COUNT(*) FROM table_user_roles ur WHERE ur.uid=u.uid) AS rolecount
		FROM table_users u, table_edition_users eu
		WHERE eu.uid=u.uid AND eu.edition = '. getOption('currentEdition') .'
		ORDER BY '. implode(',', getUpdatedOrder('users', $headers, 'u.ordername')));
	$users = $users->fetch_all();

	$mails = array();
	foreach ($users as $row)
		$mails[]= $row['name'] .' <'.$row['email'] .'>';
	$mails = htmlspecialchars(implode(', ', $mails));
	echo '<a href="mailto:'. $mails .'" '. getTipJS($mails) .'>'. _('"mailto:" link') .'</a><br/>';

	$rows = array();
	foreach ($users as $row)
	{
		$row['roles'] = array();
		$roles = getUserRoles($row['uid']);
		foreach ($roles as $r)
			if (array_key_exists($r, $roledefs))
				$row['roles'][]= '<span '. getTipJS($roledefs[$r]['description']) .'>'.
					$roledefs[$r]['short'] .'</span>';
			//else
			//	$row['roles'][]= $r;
		$row['roles'] = implode(' ', $row['roles']);
		unset($row['rolecount']);
		/*$row['roles'] = '';
		foreach ($r->fetch_column() as $role)
			if (!array_key_exists($role, $roledefs))
				throw new KnownException(sprintf(_('Undefined role: %s.'), $role));
			else
				$row['roles'] .= "<a href='adminUsers($role)' ".
					getTipJS($roledefs[$role][1]) .">".	$roledefs[$role][0] ."</a> ";*/

		$row['motivationletter'] = str_word_count(strip_tags($row['motivationletter']));
		if (!$row['motivationletter'])
			$row['motivationletter'] = '';
		$row['lecturer']  = $row['lecturer'] ? '<span '. getTipJS(_('lecturer')) .'>L</span>' : '';
		$row['qualified'] = $row['qualified'] ? '<span '. getTipJS(_('qualified')) .'>q</span>' : '';
		$row[]= '<a href="editProfile('. $row['uid'] .')">'. _('profile') .'</a>
		         <a href="editUserStatus('. $row['uid'] .')">'. _('status') .'</a>
		         <a href="editAdditionalInfo('. $row['uid'] .')">'. _('info'). '</a>';

		$rows[]= $row;
	}
	buildTableHTML($rows, $headers);
}


function actionEditUserStatus($uid)
{
	global $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$uid = intval($uid);
	$edition = getOption('currentEdition');

	$user = $DB->users[$uid]->assoc('uid,login,name,school,graduationyear,gender,'.
	 'interests,motivationletter');
	$user['badge'] = getUserBadge($uid, true);

	$PAGE->title = $user['name'] .' - '. _('qualification');
	if (userCan('adminUsers'))
		$PAGE->headerTitle = getUserHeader($uid, $user['name'], 'editUserStatus');
	$template = new SimpleTemplate($user);
	 ?>
		<span class="left">%badge% (%login%)</span>
		<span class="right">%school% - ({{graduation year}}: %graduationyear%)</span><br/>

		<h3 onclick='$("#interests_sign").toggle(); $("#interests").toggle("fast");' style='cursor: pointer'>
			 <span id='interests_sign'>+</span> {{Interests}}</h3>
		<div class="descriptionBox" id="interests" style="display:none">%interests%</div>
		<h3>{{Motivation letter}}</h3>
		<div class="descriptionBox">%motivationletter%</div>
	<?php
	echo $template->finish(true);


	echo '<h3>'. _('Workshops') . '</h3>';
	echo '<table>';
	$workshops = $DB->query('
		SELECT w.wid, w.title, w.duration, wu.*
		FROM table_workshop_users wu, table_workshops w
		WHERE w.edition=$1 AND w.wid=wu.wid AND uid=$2', getOption('currentEdition'), $uid);
	foreach ($workshops as $row)
	{
		$row['status'] = genderize(enumParticipantStatus($row['participant'])->description, $user['gender']);
		$row['comment'] = '';
		if (!empty($row['admincomment']))
			$row['comment'] = '<a '. getTipJS($row['admincomment']) .'>('. _('comment'). ')</a>';
		$template = new SimpleTemplate($row);
		?><tr>
			<td><b><a href="showWorkshop(%wid%)">%title%</a></b></td>
			<td><a href="showTaskSolutions(%wid%,%uid%)" title="{{details}}">%status%</a></td>
			<td>%points%</td>
			<td>%comment%</td>
		</tr><?php
		echo $template->finish(true);
	}
	echo '</table>';
	echo '<br/>';

	$form = new Form();
	$form->cssClass = 'inline';
	$form->addRow('checkbox', 'qualified', '<h3>'. genderize(_('Qualified'), $user['gender']) .'</h3>');
	if ($form->submitted())
	{
		$values = $form->fetchAndValidateValues();
		$form->assert(
			$DB->edition_users($edition, $uid)->count(),
			_('The user didn\'t candidate as a participant nor lecturer.')
		);
		if ($form->valid)
		{
			$DB->edition_users($edition, $uid)->update($values);
			logUser('set qualified '. ($values['qualified']?'1':'0'), $uid);
			$PAGE->addMessage(_('Saved'), 'success');
		}
	}
	$roles = getUserRoles($uid);
	$form->values = array('qualified' => array_search('qualified', $roles) !== false);
	echo $form->getHTML();
}

/*function actionEditUserStatusForm($uid)
{
	global $DB, $PAGE;
	$uid = intval($uid);
	if (!userCan('adminUsers'))  throw new PolicyException();

	$oldj = isset($DB->user_roles[array('uid'=>$uid,'role'=>'jadący')]);
	$newj = !empty($_POST['jadący']);
	if (!$oldj && $newj)
	{
		$DB->user_roles[]= array('uid'=>$uid,'role'=>'jadący');
		$DB->edition_users(getOption('currentEdition'), $uid)->update(array('qualified'=>1));
		logUser('set jadący 1', $uid);
		$PAGE->addMessage('Pomyślnie dodano do jadących.', 'success');
	}
	else if ($oldj && !$newj)
	{
		$DB->user_roles[array($uid,'jadący')]->delete();
		$DB->edition_users(getOption('currentEdition'), $uid)->update(array('qualified'=>0));
		logUser('set jadący 0', $uid);
		$PAGE->addMessage('Pomyślnie usunięto z jadących.', 'success');
	}
	else
		$PAGE->addMessage('Pozostawiono niezmieniony status.');
	callAction('editUserStatus', array($uid));
}*/


function getUserHeader($uid, $name, $action)
{
	global $DB;
	$prev = $DB->query('SELECT MAX(uid) FROM table_users WHERE uid<$1', $uid)->fetch();
	$next = $DB->query('SELECT MIN(uid) FROM table_users WHERE uid>$1', $uid)->fetch();

	$s = "<a class='back' href='adminUsers'>". _('back to the list'). "</a>";
	if (is_string($next))
		$s = "<a class='back' href='$action($next)' title='". _('next') ."'>→</a>". $s;
	if (is_string($prev))
		$s .= "<a class='back' href='$action($prev)' title='". _('previous') ."'>←</a>";
	$s .= "<h2>$name<div class='tabs'>";
	$actions = array(
		'editProfile' => _('profile'),
		'editAdditionalInfo' => _('additional info'),
		'editUserStatus' => _('qualification status')
	);
	foreach ($actions as $a=>$aname)
		$s .= "<a href='$a($uid)'". (($a == $action)?" class='selected'":"") .">$aname</a>";
	$s .= "</div></h2>";
	return $s;
}


function actionListPersonalData()
{
	global $USER, $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$PAGE->title = _('List of personal data');
	if (!isset($_GET['print']))
	{
		echo '<a class="right" href="?print">'. _('printable version') .'</a><br/>';
		$users = $DB->query('
			SELECT u.name, u.email FROM table_users u
			WHERE '. sqlUserIsQualified() .' AND u.pesel IS NULL AND u.telephone IS NULL
			ORDER BY u.ordername
		');
		echo _('Persons who did not fill their data: ');
		$emails = array();
		foreach ($users as $user)
			$emails[]= $user['name'] .' &lt;'. $user['email']. '&gt;';
		echo implode(', ',$emails) .'.';
	}

	$headers = array(
		'name' => _('full name'),
		'telephone' => _('phone number'),
		'parenttelephone' => _('parent\'s phone'),
		'pesel'           => _('PESEL'),
		'address'         => _('address')
	);
	$rows = $DB->query('
		SELECT u.'. implode(', u.', array_keys($headers)) .'
		FROM table_users u
		WHERE '. sqlUserIsQualified() .'
		ORDER BY u.pesel IS NULL AND u.telephone IS NULL DESC, u.ordername
	');
	buildTableHTML($rows, $headers);
}

function actionListArrivalData($comments = true)
{
	global $USER, $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$PAGE->title = _('List of arrival data');
	if (!isset($_GET['print']))
	{
		echo '<a class="right" href="?print">' ._('printable version'). '</a>';
		if ($comments)
			echo '<a href="listArrivalData(0)">'. _('without comments') .'</a>';
		else
			echo '<a href="listArrivalData(1)">'. _('with comments') .'</a>';
	}

	$headers = array(
		'name'          => _('full name'),
		'telephone'     => _('phone number'),
		'staybegintime' => _('arrival'),
		'stayendtime'   => _('departure'),
		'gatherplace'   => _('gathering'));
		//parenttelephone?
	if ($comments)
	{
		$headers['comments']= _('comments');
		$headers['lastmodification']= _('last mod.');
	}

	$users = $DB->query('
		SELECT '. implode(',', array_keys($headers)) .'
		FROM table_users u, table_edition_users eu
		WHERE u.uid=eu.uid AND eu.edition=$1 AND eu.qualified>0
		ORDER BY u.gatherplace DESC, eu.staybegintime, u.ordername
	', getOption('currentEdition'));

	$rows = array();
	foreach ($users as $user)
	{
		if (!is_null($user['staybegintime']))
			$user['staybegintime'] = strftime('%a %e. %H:%M', $user['staybegintime']);
		if (!is_null($user['stayendtime']))
			$user['stayendtime']   = strftime('%a %e. %H:%M', $user['stayendtime']  );
		if (!is_null($user['lastmodification']))
		{
			$t = $user['lastmodification'];
			$user['lastmodification'] = strftime('%Y-%m-%d', $t);
			// Highlight if older than 300 days
			if ($t < time() -  300 * 24 * 60 * 60)
				$user['lastmodification'] = '<span style="color:#a00">'. $user['lastmodification'] . '</span>';
		}
		if ($user['gatherplace'] == 'none')
			$user['gatherplace'] = ' - ';
		else
			$user['gatherplace'] = ucfirst($user['gatherplace']);

		$rows[] = $user;
	}
	buildTableHTML($rows, $headers);
}

function actionListDailyCounts()
{
	global $DB,$PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$PAGE->title = _('List of meal data');
	$edition = getOption('currentEdition');
	$starttime = strtotime('2011/08/08 00:00');

	if (!isset($_GET['print']))
		echo '<a class="right" href="?print">'. _('printable version') .'</a>';

	$DB->query('SELECT COUNT(*) FROM w1_edition_users
		        WHERE edition=$1 AND qualified>0
					AND (staybegintime IS NULL OR stayendtime IS NULL
					     OR staybegintime < $2 OR stayendtime < $2)',
				$edition, $starttime);
	echo _('Number of qualified users who didn\'t specify their staying time: '). $DB->fetch();
	echo ' ('. _('see') .' <a href="listPersonalData">'. _('list of personal data') .'</a>)<br/>';


	$hours = array(3,9,14,19);
	$headers = array(_('day'));
	foreach ($hours as $h)
		$headers[]= "$h:00";
	$rows = array();
	for ($i=0; $i<=10; $i++) // Workshops have 11 days [0..10].
	{
		$time = $starttime + $i*24*3600;
		$row = array(strftime("%a %e.", $time+9*3600));
		$query = 'SELECT COUNT(*) FROM w1_edition_users
			      WHERE edition=$1 AND qualified > 0
			            AND staybegintime<=$2 AND stayendtime>=$2
			            AND isselfcatered=0
			            ';
		foreach ($hours as $h)
			$row[]= $DB->query($query, $edition, $time+$h*3600)->fetch();
		$rows[]= $row;
	}
	buildTableHTML($rows, $headers);

	$tshirtsizes = array('XS','S','M','L','XL','XXL');
	echo '<h4>' ._('T-shirt sizes') .'</h4>';
	$rows = array();
	$query = 'SELECT COUNT(*) FROM w1_users u WHERE '. sqlUserIsQualified() .' AND u.tshirtsize=$1';
	foreach ($tshirtsizes as $t)
		$rows[]= array($t, $DB->query($query, $t)->fetch());
	buildTableHTML($rows);
}

function sqlUserIsQualified($edition = null)
{
	if (is_null($edition))
		$edition = getOption('currentEdition');
	return ' EXISTS (SELECT * FROM table_edition_users eu WHERE eu.uid=u.uid AND eu.qualified=1
		AND eu.edition='. intval($edition) .') ';
}
