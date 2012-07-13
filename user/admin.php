<?php
/*
 * user/admin.php - actions avaliable only to administrators.
 */

/**
 * Menu box with action available only to administators.
 */
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
		= userIs('admin');
	$PAGE->addMenuBox(_('Administration'), $items);
}

/**
 * Lists all users (actually only those who have applied for the current edition).
 */
function actionAdminUsers()
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
		name              => full name;    ordername;
		email             => e-mail;       email;
		roles             => roles;	       rolecount DESC;
		lecturer          => L;            lecturer;
		qualified         => q;            qualified;
		motivationletter  => letter;       length(motivationletter) DESC;
	');
	$headers['motivationletter']['description'] .=
		'<small '. getTipJS(_('words in motivation letter')) .'>[?]</small>';


	$PAGE->title = _('Users administration');
	// We select -lecturer and -qualified because of SQL's stupid null sorting.
	$users = $DB->query('
		SELECT u.uid, u.name, u.email, u.motivationletter, u.gender,
			(SELECT COUNT(*) FROM table_user_roles ur WHERE ur.uid=u.uid) AS rolecount,
			(SELECT -eu.lecturer  FROM table_edition_users eu WHERE eu.uid=u.uid AND eu.edition = '. getOption('currentEdition') .') AS lecturer,
			(SELECT -eu.qualified FROM table_edition_users eu WHERE eu.uid=u.uid AND eu.edition = '. getOption('currentEdition') .') AS qualified
		FROM table_users u
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
		if (isset($row['lecturer']) && $row['lecturer'])
			$row['lecturer']  =  '<span '. getTipJS(genderize(_('lecturer'),$row['gender'])) .'>'. _('L') .'</span>';
		else if (isset($row['lecturer']))
			$row['lecturer']  =  '<span '. getTipJS(genderize(_('candidate'),$row['gender'])) .'>'. _('c') .'</span>';
		else
			$row['lecturer']  = '';
		if (isset($row['qualified']) && $row['qualified'])
			$row['qualified'] =  '<span '. getTipJS(genderize(_('qualified'),$row['gender'])) .'>'. _('q'). '</span>';
		else
			$row['qualified'] = '';
		$row[]= '<a href="editProfile('. $row['uid'] .')">'. _('profile') .'</a>
		         <a href="editUserStatus('. $row['uid'] .')">'. _('status') .'</a>
		         <a href="editAdditionalInfo('. $row['uid'] .')">'. _('info'). '</a>';
		unset($row['gender']);
		$rows[]= $row;
	}
	buildTableHTML($rows, $headers);
}

/**
 * Displays a summary about a user and a one-checkbox form - qualified or not.
 */
function actionEditUserStatus($uid)
{
	global $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$uid = intval($uid);
	$edition = getOption('currentEdition');

	$user = $DB->users[$uid]->assoc('uid,login,name,school,graduationyear,gender,'.
	 'interests,motivationletter');
	$user['interests'] = parseUserHTML($user['interests']);
	$user['motivationletter'] = parseUserHTML($user['motivationletter']);
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

/**
 * The header common to all user administration actions - tabs with other actions,
 * shortcuts to next user, previous user.
 * @param $uid edited user's uid.
 * @param $name edited user's name (displayed in h2).
 * @param $action currently executed action (to mark it's tab as "selected")
 */
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
	foreach ($actions as $a => $aname)
		$s .= "<a href='$a($uid)'". (($a == $action)?" class='selected'":"") .">$aname</a>";
	$s .= "</div></h2>";
	return $s;
}
