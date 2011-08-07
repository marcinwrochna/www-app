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
		adminUsers       => admin users;               group.png;
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

	$roledefs = array(
		'admin'     => array('Adm','administrator'),
		'kadra'     => array('K','lecturer'),
		'akadra'    => array('AK','qualified lecturer'),
		'uczestnik' => array('u','uczestnik (nie kadra)'),
		'tutor'     => array('T','tutor'),
		'jadący'    => array('j','jadący')
	);

	$where = 'WHERE EXISTS (SELECT * FROM table_user_roles r WHERE r.uid=u.uid)';
	if (isset($filterBy) && $filterBy == 'all')
		$where = '';
	if (isset($filterBy) && in_array($filterBy, array_keys($roledefs)))
		$where = 'WHERE EXISTS (SELECT * FROM table_user_roles r
			WHERE r.uid=u.uid AND r.role=\''. $filterBy .'\')';


	$PAGE->title = _('Users administration');
	$template = new SimpleTemplate();
	echo '<a href="adminUsers(all)" '.  getTipJS(_('By default, only users with a role are shown.')) .'>'.
		_('all accounts'). '</a><br/>';

	$users = $DB->query("SELECT u.uid, u.name, u.email, u.motivationletter, u.proponowanyreferat
	                 FROM table_users u $where ORDER BY u.uid");
	$users = $users->fetch_all();
	$mails = array();
	foreach ($users as $row)
		$mails[]= $row['name'] .' <'.$row['email'] .'>';
	$mails = htmlspecialchars(implode(', ', $mails));
	echo '<a href="mailto:'. $mails .'" '. getTipJS($mails) .'>'. _('"mailto:" link') .'</a><br/>';

	?>
	<table>
	<tr>
		<th>id</th><th>imię i nazwisko</th><th>e-mail</th><th>role</th>
		<th>list <small <?php echo getTipJS('słów w liście motywacyjnym'); ?>>[?]</small></th>
		<th>referat <small <?php echo getTipJS('znaków w prop. temacie referatu'); ?>>[?]</small></th>
	</tr>
	<?php
		$class = 'even';
		foreach ($users as $row)
		{
			$r = $DB->query('SELECT role FROM table_user_roles WHERE uid=$1 ORDER BY role', $row['uid']);
			$row['roles'] = '';
			foreach ($r->fetch_column() as $role)
				if (!array_key_exists($role, $roledefs))
					throw new KnownException(sprintf(_('Undefined role: %s.'), $role));
				else
					$row['roles'] .= "<a href='adminUsers($role)' ".
						getTipJS($roledefs[$role][1]) .">".	$roledefs[$role][0] ."</a> ";

			$row['ml_words'] = str_word_count(strip_tags($row['motivationletter']));
			$row['pr_chars'] = strlen($row['proponowanyreferat']);
			$row['class'] = $class;

			$sub = new SimpleTemplate($row);
			?><tr class='%class%'>
				<td>%uid%</td><td>%name%</td><td>%email%</td><td>%roles%</td>
				<td>%ml_words%</td><td>%pr_chars%</td>
				<td>
					<a href='editProfile(%uid%)'>edytuj</a>
					<a href='editUserStatus(%uid%)'>status</a>
					<a href='editAdditionalInfo(%uid%)'>dane</a>
				</td>
			</tr><?php
			echo $sub->finish();

			$class = ($class=='even')?'odd':'even';
		}
	?></table>
	<?php
	echo $template->finish();
}


function actionEditUserStatus($uid)
{
	global $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$uid = intval($uid);

	$user = $DB->users[$uid]->assoc('uid,login,name,email,school,maturayear,gender,'.
	 'zainteresowania,motivationletter,proponowanyreferat');
	$roles = $DB->query('SELECT role FROM table_user_roles WHERE uid=$1', $uid);
	$user['roles'] = $roles->fetch_column();
	$user['badge'] = getUserBadge($uid);
	$user['maturayeartext'] = '('. $user['maturayear'] .')';
	$maturaYearOptions = getMaturaYearOptions();
	if (array_key_exists(strval($user['maturayear']), $maturaYearOptions))
		$user['maturayeartext'] = $maturaYearOptions[$user['maturayear']];
	$user['jadący'] = (array_search('jadący', $user['roles']) !== false) ? 'checked="checked"' : '';
	$user['genderya'] = gender('y','a',$user['gender']);

	$user['workshops'] = '<table>';
	$workshops = $DB->query('
		SELECT w.wid, w.title, w.duration, wu.*
		FROM table_workshop_user wu, table_workshops w
		WHERE w.edition=$1 AND w.wid=wu.wid AND uid=$2', getOption('currentEdition'), $uid);
	foreach ($workshops as $row)
	{
		if ($row['lecturer'])
			$row['status'] = genderize('prowadząc%', $user['gender']);
		else
			$row['status'] = genderize(enumParticipantStatus($row['participant'])->description, $user['gender']);
		if (!empty($row['admincomment']))
			$row['comment'] = '<a '. getTipJS($row['admincomment']) .'>(komentarz)</a>';
		else
			$row['comment'] = '';
		$template = new SimpleTemplate($row);
		?><tr>
			<td><b><a href="showWorkshop(%wid%)">%title%</a></b></td>
			<td><a href="showTaskSolutions(%wid%,%uid%)" title="szczegóły">%status%</a></td>
			<td>%points%</td>
			<td>%comment%</td>
		</tr><?php
		$user['workshops'] .= $template->finish();
	}
	$user['workshops'] .= '</table>';


	$PAGE->title = $user['name'] . ' - kwalifikacja';
	if (userCan('adminUsers'))
		$PAGE->headerTitle = getUserHeader($uid, $user['name'], 'editUserStatus');
	$template = new SimpleTemplate($user);
	 ?>
		<span class="left">%badge% (%login%) &nbsp; %email%</span>
		<span class="right">%school% - %maturayeartext%</span><br/>

		<h3 onclick='$("#zaint_sign").toggle(); $("#zaint").toggle("fast");'>
			 <span id='zaint_sign'>+</span> Zainteresowania</h3>
		<div class="descriptionBox" id="zaint" style="display:none">%zainteresowania%</div>
		<h3>List motywacyjny</h3>
		<div class="descriptionBox">%motivationletter%</div>
		<h3>Proponowany referat</h3>
		%proponowanyreferat%
		<h3>Warsztaty</h3>
		%workshops%
		<br/>

		<form method="post" action="editUserStatusForm(%uid%)">
			<h3 style="display: inline">Jadąc%genderya%</h3>
			<input type="checkbox" name="jadący" value="1" %jadący%>
			<input type="submit" name="submitted" value="zapisz" />
		</form>
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function actionEditUserStatusForm($uid)
{
	global $DB, $PAGE;
	$uid = intval($uid);
	if (!userCan('adminUsers'))  throw new PolicyException();

	$oldj = isset($DB->user_roles[array('uid'=>$uid,'role'=>'jadący')]);
	$newj = !empty($_POST['jadący']);
	if (!$oldj && $newj)
	{
		$DB->user_roles[]= array('uid'=>$uid,'role'=>'jadący');
		$DB->edition_user(getOption('currentEdition'), $uid)->update(array('qualified'=>1));
		logUser('set jadący 1', $uid);
		$PAGE->addMessage('Pomyślnie dodano do jadących.', 'success');
	}
	else if ($oldj && !$newj)
	{
		$DB->user_roles[array($uid,'jadący')]->delete();
		$DB->edition_user(getOption('currentEdition'), $uid)->update(array('qualified'=>0));
		logUser('set jadący 0', $uid);
		$PAGE->addMessage('Pomyślnie usunięto z jadących.', 'success');
	}
	else
		$PAGE->addMessage('Pozostawiono status.');
	actionEditUserStatus($uid);
}


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
			ORDER BY regexp_replace(u.name,\'.*\ ([^\ ]+)\',\'\\\\1\')
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
		ORDER BY u.pesel IS NULL AND u.telephone IS NULL DESC, regexp_replace(u.name,\'.*\ ([^\ ]+)\',\'\\\\1\')
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
		'name'        => _('full name'),
		'telephone'   => _('phone number'),
		'staybegin'   => _('arrival'),
		'stayend'     => _('departure'),
		'gatherplace' => _('gathering'));
		//parenttelephone?
	if ($comments)
	{
		$headers['comments']= _('comments');
		$headers['lastmodification']= _('last mod.');
	}

	$users = $DB->query('
		SELECT u.'. implode(',u.', array_keys($headers)) .' FROM table_users u
		WHERE '. sqlUserIsQualified() .'
		ORDER BY u.gatherplace DESC, u.staybegin, regexp_replace(u.name,\'.*\ ([^\ ]+)\',\'\\\\1\')
	');

	$rows = array();
	foreach ($users as $user)
	{
		$starttime = strtotime('2011/08/08 00:00');
		if (!is_null($user['staybegin']))
			$user['staybegin'] = strftime('%a %d. %H:00', $starttime + 3600*$user['staybegin']);
		if (!is_null($user['stayend']))
			$user['stayend']   = strftime('%a %d. %H:00', $starttime + 3600*$user['stayend']  );
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

	if (!isset($_GET['print']))
		echo '<a class="right" href="?print">'. _('printable version') .'</a>';

	$DB->query('SELECT COUNT(*) FROM w1_users u
		        WHERE '. sqlUserIsQualified() .' AND (u.staybegin IS NULL OR u.stayend IS NULL)');
	echo _('Number of qualified users who didn\'t specify their staying time: '). $DB->fetch();
	echo ' ('. _('see') .' <a href="listPersonalData">'. _('list of personal data') .'</a>)<br/>';

	$starttime = strtotime('2011/08/08 00:00');
	$hours = array(3,9,14,19);
	$headers = array(_('day'));
	foreach ($hours as $h)
		$headers[]= "$h:00";
	$rows = array();
	for ($i=0; $i<=10; $i++) // Workshops have 11 days [0..10].
	{
		$row = array(strftime("%a %e.", $starttime+($i*24+9)*3600));
		$query = 'SELECT COUNT(*) FROM w1_users u
			      WHERE '. sqlUserIsQualified() .'
			            AND u.staybegin<=$1 AND u.stayend>=$1
			            AND u.isselfcatered=0';
		foreach ($hours as $h)
			$row[]= $DB->query($query, $i*24+$h)->fetch();
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
	return ' EXISTS (SELECT * FROM table_edition_user eu WHERE eu.uid=u.uid AND eu.qualified=1
		AND eu.edition='. intval($edition) .') ';
}
