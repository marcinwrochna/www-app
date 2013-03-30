<?php
/*
 * workshop.php
 */
require_once('tasks.php');

/**
 * Menu box with workshop-specific actions.
 */
function addWarsztatyMenuBox()
{
	global $PAGE;
	$PAGE->addMenuBox('Warsztaty', parseTable('
		ACTION              => tTitle;                     ICON;
		listPublicWorkshops => workshop list;              brick.png;
		listOwnWorkshops    => your workshops;             brick-green.png;
		createWorkshop      => propose new workshops;      brick-add.png;
	'));
}

/**
 * Lists workshops marked as accepted, for the participants to sign up.
 * Displays an information/warning about how much workshops did a user sign up for.
 * @see listWorkshops()
 */
function actionListPublicWorkshops()
{
	global $PAGE, $USER, $DB;
	if (!userCan('listPublicWorkshops'))  throw new PolicyException();
	$PAGE->title = _('Workshop list');
	if (!assertProfileFilled())  return;

	// Inform how many hours of workshops did the user sign up for.
	$DB->query('SELECT SUM(w.duration)
		FROM table_workshops w, table_workshop_users wu
		WHERE w.status=$1 AND w.edition=$2 AND wu.wid=w.wid AND wu.uid=$3',
		enumBlockStatus('accepted')->id, getOption('currentEdition'), $USER['uid']);
	$sum = intval($DB->fetch());
	if ($sum)
	{
		$msg = sprintf(genderize(_('You signed up for %d x 1,5 hours of workshops.')), $sum);
		if ($sum>30)  $msg .= '<br/>'. _('Remember that WWW has approximately 36 x 1,5 hours in total.');
		$PAGE->addMessage($msg, 'info');
	}

	// Public workshops = workshops with type:workshop and status:accepted.
	listWorkshops('Public',
		'(type='.  enumBlockType('workshop')->id .' AND '.
		'status='. enumBlockStatus('accepted')->id .')',
		array('wid','lecturers','title','subject','duration','participants'));
}

/**
 * Lists user's own workshops (those he sent proposals for, or has been added to as a lecturer).
 * @see listWorkshops()
 */
function actionListOwnWorkshops()
{
	global $USER, $PAGE;
	if (!userCan('listOwnWorkshops'))  throw new PolicyException();
	$PAGE->title = _('Your workshops');
	$where = 'EXISTS (SELECT wu.uid FROM table_workshop_users wu WHERE wu.uid='. $USER['uid'].'
		AND wu.wid = w.wid AND wu.participant='. enumParticipantStatus('lecturer')->id .')';
	listWorkshops('Own', $where,
		array('wid','lecturers','title','type','subject','duration','status','participants'));
}

/**
 * Lists all workshops, most of all those awaiting acceptation.
 * @see listWorkshops()
 */
function actionListAllWorkshops()
{
	global $PAGE;
	if (!userCan('listAllWorkshops'))  throw new PolicyException();
	$PAGE->title = _('All workshops');
	listWorkshops('All', '',
		array('wid','lecturers','title','type','subject','duration','status','participants'));
}

/**
 * Lists workshops in a table.
 * (Admins need different information than lecturers, but most of it is the same.)
 * @param $which 'Public', 'Own' or 'All'
 * @param $where a WHERE sql clause limiting the workshops listed.
 * @param $columns columns to be displayed.
 */
function listWorkshops($which, $where, $columns)
{
	global $USER, $DB, $PAGE;

	$cols = parseTable('
		COLUMN        => tTH;             ORDER;
		wid           => #;               w.wid;
		lecturers     => lecturers;       lecturers;
		title         => title;           w.title;
		type          => type;            w.type;
		subject       => subjects;        w.subjects_order;
		duration      => duration [1.5h]; w.duration DESC;
		status        => status;          w.status;
		participants  => signups;         count_accepted DESC;
	');

	$from = '';
	$whereClauses = array();
	$orderby = array();
	/* ORDER BY (remember in SESSION, keep stabile, special order when ordering by lecturers) */
	$allowed = array();
	foreach ($cols as $col)  if ($col['order'] != 'lecturers')  $allowed[]= $col['order'];
	if (!isset($_SESSION['workshopOrder']))  $_SESSION['workshopOrder'] = array();
	if (isset($_GET['order']) && $_GET['order'] =='lecturers')
	{
		$orderby[] = 'u.ordername';
		$from = ', table_users u';
		$whereClauses[] = 'EXISTS (SELECT * FROM table_workshop_users wu
			WHERE w.wid=wu.wid AND u.uid=wu.uid
				AND participant='. enumParticipantStatus('lecturer')->id .')';
		$PAGE->addMessage(_('When sorting by lecturers, common workshops are shown repeatedly.'), 'info');
	}
	else if (isset($_GET['order']))
		$_SESSION['workshopOrder'][]= $_GET['order'];
	while (count($_SESSION['workshopOrder']) > 3)
		array_shift($_SESSION['workshopOrder']);

	$orderClauses = array_reverse($_SESSION['workshopOrder']);
	$orderClauses[]= 'w.title';
	foreach ($orderClauses as $o)
		if (in_array($o, $allowed, true) && !in_array($o, $orderby))
			$orderby[]= $o;
	$orderby = implode(',', $orderby);

	/* WHERE (current editions, restriction to subject) */
	if (!empty($where))  $whereClauses[]= $where;
	$whereClauses[]= 'w.edition='. intval(getOption('currentEdition'));
	if (isset($_GET['subject']) && enumSubject()->exists($_GET['subject']))
		$whereClauses[]= 'EXISTS (SELECT * FROM table_workshop_subjects ws
			WHERE ws.wid=w.wid AND ws.subject=\''. $_GET['subject'] .'\')';
	if (!empty($whereClauses))  $where = 'WHERE '. implode(' AND ', $whereClauses);
	else $where = '';

	/* SELECT (participant counts by status) */
	$selectParticipants = '';
	foreach (enumParticipantStatus() as $statusName => $status)
		$selectParticipants .= '(SELECT COUNT(*) FROM table_workshop_users wu
			WHERE wu.wid=w.wid AND participant='. $status->id .') AS count_'. $statusName .',';


	$workshops = $DB->query('
		SELECT w.wid, w.title, w.status, w.type, w.duration, w.link,
			'. $selectParticipants .'
			(SELECT participant FROM table_workshop_users wu
			 WHERE wu.wid=w.wid AND wu.uid=$1) AS participant
		FROM table_workshops w'. $from .'
		'. $where .'
		ORDER BY w.edition DESC, '. $orderby, $USER['uid']);

	$PAGE->headerTitle = "<h2><a href='list${which}Workshops'>". $PAGE->title . "</a></h2>";
	echo "<table class='workshopList'>";
	echo "<thead><tr>";
	foreach ($columns as $c)
	{
		$th = $cols[$c]['th'];
		$order = htmlspecialchars(urlencode($cols[$c]['order']), ENT_QUOTES);
		echo "<th><a href='list${which}Workshops?order=$order'>$th</a></th>";
	}
	echo "</tr></thead><tbody>";
	foreach ($workshops as $row)
	{
		$status = enumBlockStatus(intval($row['status']));
		$row['status'] = userCan('seeWorkshopStatus') ? $status->decision : $status->status;
		$row['status'] = str_replace(' ','&nbsp;', $row['status']);

		$row['lecturers'] = array();
		foreach (getLecturers($row['wid']) as $lecturer)
			$row['lecturers'][]= getUserBadge($lecturer);
		$row['lecturers'] = implode(',<br/>', $row['lecturers']);

		$row['type'] = enumBlockType(intval($row['type']))->short;

		$subjects = $DB->query('
			SELECT subject FROM table_workshop_subjects
			WHERE wid=$1 ORDER BY subject', $row['wid'])->fetch_column();
		$row['subject'] = '';
		foreach ($subjects as $subject)
		{
			$s = enumSubject($subject);
			$row['subject'] .= getIcon('subject-'. $s->icon .'.png', $s->description,
			 "list${which}Workshops?subject=$subject");
		}

		$row['title'] = '<a href="showWorkshop('. $row['wid'] .')">'. $row['title'] .'</a>';

		$row['participants'] = '';
		if (userCan('showWorkshopParticipants', getLecturers($row['wid'])))
		{
			$tip = '';
			foreach (enumParticipantStatus() as $statusName => $status)
				$tip .= genderize($status->description, 'p') .': '. $row["count_$statusName"] .'<br/>';
			$row['participants'] .= '<a '. getTipJS($tip) .'>';
			$row['participants'] .= ($row['count_accepted'] + $row['count_autoaccepted']) .'</a>';
		}

		$participant = enumParticipantStatus(intval($row['participant']));
		if (isset($participant->icon))
			$row['participants'] .= ' '. getIcon($participant->icon, genderize($participant->explanation));

		$class = alternate('even', 'odd');

		echo "<tr class='$class'>";
		foreach ($columns as $c)
			echo '<td>'. $row[$c] .'</td>';
		echo "</tr>";
	}
	echo "</tbody></table>";

	if (count($workshops) == 0)
		echo _('No workshops have been accepted for this edition yet.');
}

/**
 * Returns array of uids of given workshop's lecturers..
 * @param $wid a wid (workshop id).
 */
function getLecturers($wid)
{
	global $DB;
	$lecturers = $DB->query('SELECT uid FROM table_workshop_users WHERE participant=$1 AND wid=$2',
		enumParticipantStatus('lecturer')->id, $wid);
	return $lecturers->fetch_column();
}

/**
 * Displays information about a workshop block (proposal).
 * Displays a simple form for admins to change to workshop block's status
 * @param $wid a wid (workshop id).
 */
function actionShowWorkshop($wid = null)
{
	global $USER, $DB, $PAGE;
	if (is_null($wid))  throw new KnownException(_('No workshop id given.'), '400 Bad Request'); // Handle deprecated links.
	$wid = intval($wid);

	$data = $DB->workshops[$wid]->assoc('*');
	if (!$data)  throw new KnownException(_('Workshop not found.'), '404 Not Found');
	$data['title'] = htmlspecialchars($data['title']);
	$data['type'] = ucfirst(enumBlockType(intval($data['type']))->description);
	$data['description'] = parseUserHTML($data['description']);

	$participant = $DB->workshop_users[array('wid'=>$wid, 'uid'=>$USER['uid'])]->get('participant');
	$participant = intval($participant);

	// Lecturers list.
	$data['by'] = array();
	$lecturers = getLecturers($wid);
	$displayEmail = $participant || userCan('editWorkshop', $lecturers);
	foreach ($lecturers as $lecturer)
		$data['by'][]= getUserBadge($lecturer, $displayEmail);
	$data['by'] =  ngettext('Lecturer', 'Lecturers', count($data['by'])) .': '. implode(', ', $data['by']);

	if (!userCan('showWorkshop', $lecturers))  throw new PolicyException();

	if (empty($data['link']))
		$data['link'] = 'http://warsztatywww.wikidot.com/www'. getOption('currentEdition').
			':'. urlencode($data['title']);

	// The subjects.
	$subjects = $DB->query('SELECT subject FROM table_workshop_subjects WHERE wid=$1', $wid);
	$data['subjects'] = array();
	foreach ($subjects as $subject)
		$data['subjects'][]= enumSubject($subject['subject'])->description;
	$data['subjects'] = implode(', ', $data['subjects']);

	// Basic info.
	$PAGE->title = $data['title'];
	$PAGE->headerTitle = '';
	$template = new SimpleTemplate($data);
	?>
		<span class='left'>%wid%.&nbsp;</span><h2>%title% <span class='tabs'>
			<a class='selected'>{{description}}</a>
			<a href="showWorkshopTasks(%wid%)">{{signups and tasks}}</a>
		</span></h2>
		%type%: %subjects%.<br/>
		%by%<br/>
		{{Duration}}: %duration% × 1,5 godz.<br/>
		<a href="%link%">{{see the description on wikidot}}</a><br/>
		<br/>
	<?php
	echo $template->finish(true);

	// The signup/signout button (same as in showWorkshopTasks).
	if (enumParticipantStatus($participant)->inArray(array('candidate', 'autoaccepted')))
		echo getButton(_('sign out'), "resignFromWorkshop($wid)", 'cart-remove.png')  .'<br/>';
	else if (!$participant && userCan('signUpForWorkshop', $lecturers))
		echo getButton(_('sign up'), "signUpForWorkshop($wid)", 'cart-put.png') .'<br/>';
	echo '<br/>';

	// Proposition description.
	if (userCan('showWorkshopDetails', $lecturers)) {
		echo _('Proposal').': <div class="descriptionBox">'. $data['description'] .'</div>';
	}
	// Workshop block status (e.g. accepted or not).
	if (userCan('changeWorkshopStatus', $lecturers))
	{
		echo _('Status:');
		$inputs = parseTable('
			NAME   => TYPE;   tDESCRIPTION;
			status => select; ;
		');
		$inputs['status']['options'] = enumBlockStatus()->assoc('key', 'decision');
		$form = new Form($inputs);
		if ($form->submitted())
		{
			$values = $form->fetchAndValidateValues();
			if ($form->valid)
			{
				$status = enumBlockStatus($values['status'])->id;
				$DB->workshops[$wid]->update(array('status' => $status));
				$PAGE->addMessage(_('Changed workshop block status.'));
				logUser('workshop status chg', $wid);
			}
		}
		$form->values['status'] = enumBlockStatus(intval($data['status']))->key;
		echo $form->getHTML();
		echo '<br/>';
	}
	else if (userCan('showWorkshopDetails', $lecturers))
	{
		if (userCan('seeWorkshopStatus'))
			$status = enumBlockStatus($data['status'])->decision;
		else
			$status = enumBlockStatus($data['status'])->status;
		 echo _('Status:') ." $status<br/><br/>";
	}
	// Edit-workshop button.
	if (userCan('editWorkshop', $lecturers))
		echo getButton(_('edit'), "editWorkshop($wid)", 'brick-edit.png');
}

function actionShowWorkshopTasks($wid)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);

	$data = $DB->workshops[$wid]->assoc('*');
	$data['title'] = htmlspecialchars($data['title']);
	$data['type'] = ucfirst(enumBlockType(intval($data['type']))->description);
	$data['description'] = parseUserHTML($data['description']);
	$lecturers = getLecturers($wid);
	$participant = $DB->workshop_users[array('wid'=>$wid, 'uid'=>$USER['uid'])]->get('participant');
	$participant = intval($participant);

	$PAGE->title = $data['title'] .' - '. _('signups and tasks');
	$PAGE->headerTitle = '';
	$template = new SimpleTemplate($data);
	?>
		<span class='left'>%wid%.&nbsp;</span><h2>%title% <span class='tabs'>
			<a href='showWorkshop(%wid%)'>{{description}}</a>
			<a class='selected'>{{signups and tasks}}</a>
		</span></h2>
	<?php
	echo $template->finish(true);

	echo _('Your status:') .' <i>'. genderize(enumParticipantStatus($participant)->explanation) .'</i><br/>';

	// The signup/signout button (same as in showWorkshop).
	if (enumParticipantStatus($participant)->inArray(array('candidate', 'autoaccepted')))
		echo getButton(_('sign out'), "resignFromWorkshop($wid)", 'cart-remove.png')  .'<br/>';
	else if (!$participant && userCan('signUpForWorkshop', $lecturers))
		echo getButton(_('sign up'), "signUpForWorkshop($wid)", 'cart-put.png') .'<br/>';
	echo '<br/>';

	// List of signed-up participants.
	if (userCan('showWorkshopParticipants', $lecturers))
		echo buildParticipantList($wid) . '<br/>';

	// List of qualification tasks.
	echo buildTaskList($wid);
}

function actionCreateWorkshop()
{
	actionEditWorkshop(-1);
}

function actionEditWorkshop($wid)
{
	global $USER, $PAGE, $DB;
	$wid = intval($wid);
	$new = ($wid == -1);
	if ($new && !userCan('createWorkshop'))  throw new PolicyException();
	else if (!$new && !userCan('editWorkshop', getLecturers($wid)))  throw new  PolicyException();
	if (!assertProfileFilled())  return;

	$inputs = parseTable('
		NAME        => TYPE;          tDESCRIPTION;                   VALIDATION;
		title       => text;          Title;                          length(5 100);
		lecturers   => readonly;      Lecturers;
		description => richtextarea;  Description;
		subjects    => checkboxgroup; Subject;
		type        => select;        Type;
		duration    => select;        Duration;                       int;             other;
		link        => text;          Link to description on wikidot;
	');

	if (!$new)
		$inputs['lecturers']['description'] .= " <a href='editWorkshopLecturers($wid)'>[". _('change') ."]</a>";

	$inputs['description']['description'] .= '<br/><small>'.
		_('Topics addressed, difficulty, scope of material, '.
		  'what will you do (theoretical problems, coding, experiments, ...). '.
		  'We encourage possibly detailed descriptions.')
		.'</small>';

	$inputs['subjects']['options'] = enumSubject()->assoc('key', 'description');
	$inputs['type']['options'] = enumBlockType()->assoc('key','description');

	$inputs['type']['properties'] = 'onchange="$(\'#row_duration\').toggle(this.selectedIndex=='. enumBlockType('workshop')->id .');"';
	$inputs['duration']['options'] = array(4=>'6h (4x1.5h)', 6=>'9h (6x1.5h)');
	$inputs['duration']['other'] = '[x1,5h]<span class="left">('.
		_('non-standard durations should be<br/> consulted with the organizers.')
		 .')</span>';

	/* It seems more annoying than useful.
	$inputs['title']['properties'] = 'onchange="$(\'#link\')[0].value = \''.
	'http://warsztatywww.wikidot.com/www9:'.
	'\' +this.value.replace(/[^a-zA-Z0-9]/g,\'_\');"';
	*/


	$PAGE->title = $new ? _('Propose a new workshop block') : _('Edit a workshop block');
	if (!$new)
		echo "<a class='back' href='showWorkshop($wid)'>". _('view') ."</a>";
	$form = new Form($inputs);

	if ($form->submitted())
	{
		$values = $form->fetchAndValidateValues();
		if ($form->valid)
		{
			submitEditWorkshopForm($wid, $values);
			$new = false;
		}
	}

	if ($new)
	{
		if (time() > $DB->editions[getOption('currentEdition')]->get('proposaldeadline'))
			$PAGE->addMessage(_('The deadline for workshop proposal submissions has expired. '.
				'Fill the form at your own risk.'), 'warning');

		$data = array(
			'wid' => -1,
			'title' => '',
			'description' => '',
			'status' => 'new',
			'materials' => '',
			'subjects' => array(),
			'type' => 'workshop',
			'duration' => 3*2, //3*2*90min = 9h
			'duration_min' => 90,
			'link' => 'http://warsztatywww.wikidot.com/www'. getOption('currentEdition') .':tytul-warsztatow'
		);
		$lecturers = array($USER['uid']);
	}
	else
	{
		$data = $DB->workshops[$wid]->assoc('*');
		$lecturers = getLecturers($wid);
		$data['type'] = enumBlockType(intval($data['type']))->key;
		$DB->query('SELECT subject FROM table_workshop_subjects WHERE wid=$1', $wid);
		$data['subjects'] = $DB->fetch_column();
	}

	foreach ($lecturers as $lecturer)
		$data['lecturers'][]= getUserBadge($lecturer, true);
	$data['lecturers'] =  implode(', ', $data['lecturers']);
	if ($new)
		$data['lecturers'] .= ' <small class="right">'.
			_('You\'ll be able to add more lecturers later, by editing this workshop block.').
			'</small>';

	$form->values = $data;
	echo $form->getHTML();

	if ($data['type'] != 'workshop')
	{
		$PAGE->jsOnLoad .= '$("#row_duration").hide();';
		$PAGE->jsOnLoad .= '$("#row_duration_other").hide();';
	}
	if (in_array($data['duration'], array_keys($inputs['duration']['options'])))
		$PAGE->jsOnLoad .= '$("#row_duration_other").hide();';
}


function submitEditWorkshopForm(&$wid, $values)
{
	global $USER, $DB, $PAGE;
	$new = ($wid==-1);

	$data = array(
		'title' => $values['title'],
		'description' => $values['description'],
		'type' => enumBlockType($values['type'])->id,
		'duration' => ($values['type'] == 'lightLecture') ? 1 : intval($values['duration']),
		'link' => trim($values['link']),
		'subjects_order' => empty($_POST['subjects']) ? 0 : subjectOrder($_POST['subjects'])
	);

	if (empty($data['link']))  $data['link'] = null;
	else if (strpos($data['link'], 'http://') !== 0  &&  strpos($data['link'], 'https://') !== 0)
		$data['link'] = 'http://'. $data['link'];

	if ($new)
	{
		$data = $data + array(
			'edition' => intval(getOption('currentEdition')),
			'status' => enumBlockStatus('new')->id
		);
		$DB->workshops[] = $data;
		$wid = $DB->workshops->lastValue();
		$DB->workshop_users[]= array('wid'=>$wid, 'uid'=>$USER['uid'],
			'participant'=>enumParticipantStatus('lecturer')->id);
		if (!empty($_POST['subjects']))
			foreach ($_POST['subjects'] as $d)
				$DB->workshop_subjects[]= array('wid'=>$wid, 'subject'=>$d);
		$PAGE->addMessage(_('Your proposal has been submitted.'), 'success');
		logUser('workshop new', $wid);
		callAction('editWorkshop', array($wid), true);
	}
	else
	{
		$DB->workshops[$wid]->update($data);
		$DB->query('DELETE FROM table_workshop_subjects WHERE wid=$1', $wid);
		if (!empty($_POST['subjects']))
			foreach ($_POST['subjects'] as $d)
				$DB->workshop_subjects[]= array('wid'=>$wid, 'subject'=>$d);
		$PAGE->addMessage(_('Saved.'), 'success');
		logUser('workshop edit', $wid);
	}
}

function actionEditWorkshopLecturers($wid)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);
	$lecturers = getLecturers($wid);
	if (!userCan('editWorkshop', $lecturers))  throw new PolicyException();

	$inputs = array();
	foreach ($lecturers as $lecturer)
		$inputs['uid'. $lecturer]= array(
			'name' => 'uid'. $lecturer,
			'type' => 'custom',
			'description' => getUserBadge($lecturer, true),
			'custom'=>"<a href='removeWorkshopLecturer($wid;$lecturer)'>[". _('remove'). "]</a>"
		);

	$inputs['lecturer']= array(
		'name'=>'lecturer',
		'type'=>'text',
		'description' => _('Full name or uid')
	);
	$inputs['lecturer']['autocomplete'] = $DB->query('
		SELECT name FROM table_users u
		WHERE EXISTS (SELECT * FROM table_edition_users eu
			WHERE eu.uid=u.uid AND eu.lecturer=1 AND eu.edition=$1)
		ORDER BY name', getOption('currentEdition'))->fetch_column();

	$PAGE->title = _('Add/remove lecturers');
	echo "<a class='back' href='editWorkshop($wid)'>". _('back') ."</a>";
	echo '<h3>'. $DB->workshops($wid)->get('title') .'</h3>';
	$form = new Form($inputs);
	if ($form->submitted())
	{
		$values = $form->fetchAndValidateValues();
		if ($form->valid)
			actionAddWorkshopLecturer($wid, $values['lecturer']);
	}
	$form->submitValue = _('Add');
	echo $form->getHTML();
}

function actionRemoveWorkshopLecturer($wid, $uid, $confirm = false)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);
	$uid = intval($uid);
	$lecturers = getLecturers($wid);
	if (!userCan('editWorkshop', $lecturers))  throw new PolicyException();

	if (!in_array($uid, $lecturers))
		$PAGE->addMessage(_('The user is not a lecturer.'), 'userError');
	else if (($uid == $USER['uid']) && !$confirm)
	{
		$PAGE->addMessage(_('Are you sure you want to remove yourself from lecturers?') .'<br/>'.
			"<a href='removeWorkshopLecturer($wid;$uid;1)' class='button'>". _('yes'). "</a> ".
			"<a href='editWorkshopLecturers($wid)' class='button'>". _('cancel'). "</a>",
			'warning');
	}
	else
	{
		$DB->query('DELETE FROM table_workshop_users WHERE wid=$1 AND uid=$2', $wid, $uid);
		$PAGE->addMessage(_('The user has been removed from lecturers here.'), 'success');
		if ($uid == $USER['uid'])
			callAction('listOwnWorkshops', null, true);
	}
	callAction('editWorkshopLecturers', array($wid));
}

function actionAddWorkshopLecturer($wid, $lecturer, $confirm = false)
{
	global $USER, $DB, $PAGE;
	$lecturers = getLecturers($wid);

	/* Convert textual $lecturer to $uid (a long and boring part). */
	if (is_numeric($lecturer))
	{
		$uid = intval($lecturer);
		if (!isset($DB->users[$uid]))  throw new KnownException(_('User not found.'));
	}
	else
	{
		// Try to match the name.
		$uid = $DB->query('SELECT uid FROM table_users WHERE name=$1', trim($lecturer))->fetch();
		if ($uid === null)
		{
			// Fuzzy match - find name with least levenshtein distance.
			$names = $DB->query('
				SELECT uid, name FROM table_users u
				WHERE EXISTS (SELECT * FROM table_edition_users eu
					WHERE eu.uid=u.uid AND eu.lecturer=1 AND eu.edition=$1)
				ORDER BY name', getOption('currentEdition'))->fetch_all();
			$best = 100000;
			$bestname = '';
			foreach ($names as $name)
				if (levenshtein($name['name'], $lecturer) < $best)
				{
					$best = levenshtein($name['name'], $lecturer);
					$uid = $name['uid'];
					$bestname = $name['name'];
				}
			$PAGE->addMessage(sprintf(_('Fuzzy-matched user #%d'), $uid) ." <i>$bestname</i>", 'info');
		}
	}

	if (in_array($uid, $lecturers))
		$PAGE->addMessage(_('The user you selected already is a lecturer here.'), 'userError');
	else
	{
		$DB->query('SELECT count(*) FROM table_workshop_users WHERE wid=$1 AND uid=$2', $wid, $uid);
		if ($DB->fetch() && ($confirm === false))
		{
			$PAGE->addMessage(
				_('The user already signed up as a participant. Sign him out and add to lecturers?') .
				'<br/>'.
				"<a href='addWorkshopLecturer($wid;$uid;1)' class='button'>". _('yes') ."</a> ".
				"<a href='editWorkshopLecturers($wid)' class='button'>". _('cancel'). "</a>",
				'warning');
		}
		else
		{
			if ($confirm !== false)
				$DB->query('DELETE FROM table_workshop_users WHERE wid=$1 AND uid=$2', $wid, $uid);
			$DB->workshop_users[]= array('uid'=>$uid, 'wid'=>$wid,
				'participant'=>enumParticipantStatus('lecturer')->id);
			$PAGE->addMessage(_('Added user to lecturers.'), 'success');
		}
	}

	callAction('editWorkshopLecturers', array($wid));
}


function actionSignUpForWorkshop($wid)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);
	if (!userCan('signUpForWorkshop'))  throw new PolicyException();

	$participant = 'candidate';
	if (userCan('autoQualifyForWorkshop'))  $participant = 'autoaccepted';
	$participant = enumParticipantStatus($participant)->id;

	$dbRow = $DB->workshop_users[array('wid'=>$wid,'uid'=>$USER['uid'])];
	if (!$dbRow->count())
		$DB->workshop_users[] = array('wid'=>$wid,'uid'=>$USER['uid'], 'participant'=>$participant);
	else
	{
		if ($dbRow->get('participant') != enumParticipantStatus('none')->id)
			throw new KnownException(_('You\'re already signed up for this workshop block.'));
		$dbRow->update(array('participant'=>$participant));
	}

	$PAGE->addMessage(_('Signed up for a workshop block.'), 'success');
	callAction('showWorkshop', array($wid));
	logUser('workshop participant signup', $wid);
}

function actionResignFromWorkshop($wid)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);
	$dbRow = $DB->workshop_users[array('wid'=>$wid,'uid'=>$USER['uid'])];
	$participant = enumParticipantStatus(intval($dbRow->get('participant')));
	if (!$participant->canResign)
		throw new KnownException(_('Your can\'t resign from your status: '). $participant->description);
	$dbRow->delete();
	$PAGE->addMessage(_('Resigned from workshop block.'), 'success');
	callAction('showWorkshop', array($wid));
	logUser('workshop participant resign', $wid);
}

function subjectOrder($subjects)
{
	$r = 0;
	foreach (enumSubject() as $subjectName=>$subject)
		if (in_array($subjectName, $subjects))
			$r += $subject->orderWeight;
	return $r;
}

function actionRecountSubjectOrder()
{
	global $DB;
	$wids = $DB->query('SELECT wid FROM table_workshops')->fetch_column();
	foreach ($wids as $wid)
	{
		$DB->query('SELECT subject FROM table_workshop_subjects WHERE wid=$1', $wid);
		$order = subjectOrder($DB->fetch_column());
		$DB->workshops[$wid]->update(array('subjects_order'=>$order));
	}
}
