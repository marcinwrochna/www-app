<?php
/*
	tasks.php
	Included in workshop.php
*/
// TODO review and translate.

// Called by actionShowWorkshop
function buildTaskList($wid)
{
	global $USER, $DB;
	$lecturers = getLecturers($wid);

	$template = new SimpleTemplate();
	echo '<h3>'. _('Qualification tasks') .'</h3>';

	$taskComment = $DB->workshops[$wid]->get('tasks_comment');

	if (userCan('editTasks', $lecturers) && userCan('editWorkshop', $lecturers))
	{
		echo '<span class="right">';
		echo '<a '.
			getTipJS(_('This is a place where you can add information concerning all tasks, how to '.
				'submit solutions, etc. You may, for example, attach a pdf with all the tasks. '.
				'If you won\'t be able to check tasks during some period, write about it here.'));
		echo '>[?]</a> ';
		echo getIcon('pencil.png', _('edit'), "editTasksComment($wid)");
		echo '</span>';
	}
	echo '<div class="descriptionBox">'. parseUserHTML($taskComment) .'</div>';

	$participant = $DB->workshop_users($wid, $USER['uid'])->get('participant');
	$participant = enumParticipantStatus(intval($participant));
	$isParticipant = $participant->inArray('candidate', 'accepted', 'rejected');

	echo '<table class="tasks">';
	$tasks = $DB->query('SELECT * FROM table_tasks WHERE wid=$1 ORDER BY tid', $wid);
	foreach ($tasks as $task)
	{
		echo '<tr class="'. alternate('even','odd') .'"><td>'. $task['tid'] .'</td>';
		echo '<td>'. parseUserHTML($task['description']) .'</td>';
		$params = '('. $wid .';'. $task['tid'] .')';
		if (userCan('editTasks', $lecturers) && userCan('editWorkshop', $lecturers))
		{
			echo '<td>';
			echo getIcon('plugin-edit.png', _('edit task'), 'editTask'. $params);
			echo getIcon('plugin-delete.png', _('delete task'), 'deleteTask'. $params);
			echo '</td>';
		}
		if ($isParticipant && userCan('sendTaskSolution'))
		{
			echo '<td>';
			$DB->query('SELECT status, grade FROM table_task_solutions
				WHERE wid=$1 AND uid=$2 AND tid=$3
				ORDER BY submitted DESC LIMIT 1',
				$wid, $USER['uid'], $task['tid']);
			$sol = $DB->fetch_assoc();

			if ($sol === false)  echo _('submit');
			else  echo EnumSolutionStatus($sol['status'])->description;
			echo ' ';
			echo getIcon('arrow-right.png', _('your solution'), 'editSolution'. $params);
			echo '</td>';
		}
		echo '</tr>';
	}
	if (userCan('editTasks', $lecturers) && userCan('editWorkshop', $lecturers))
		echo '<tr><td colspan="2">'.
			getButton(_('add a task'), "createTask($wid)", 'plugin-add.png').
			'</td></tr>';
	echo '</table><br/>';
	if (!userCan('editTasks', $lecturers) && userCan('editWorkshop', $lecturers))
		echo '<i>'. _('Your workshops await acceptance.') .'</i>';
	return $template->finish();
}

function actionCreateTask($wid)
{
	global $PAGE, $DB;
	$wid = intval($wid);
	$DB->query('SELECT MAX(tid) FROM table_tasks WHERE wid=$1', $wid);
	$tid = intval($DB->fetch())+1;
	actionEditTask($wid, $tid, true);
}

function actionEditTask($wid, $tid, $new = false)
{
	global $USER, $PAGE, $DB;
	$wid = intval($wid);
	$tid = intval($tid);
	$lecturers = getLecturers($wid);
	if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
		throw new PolicyException();

	echo '<h4>'. $DB->workshops[$wid]->get('title') .'</h4>';
	$form = new Form(parseTable('
		NAME        => TYPE;         tDESCRIPTION;
		description => richtextarea; the task;
	'));

	if ($new)
	{
		$PAGE->title = _('New qualification task');
		$form->values = array(
			'wid' => $wid,
			'tid' => $tid,
			'description' => ''
		);
	}
	else
	{
		$PAGE->title = _('Edit qualification task');
		$form->values = $DB->tasks($wid,$tid)->assoc('*');
	}

	echo '<a class="back" href="showWorkshopTasks('. $wid .')">' ._('back') .'</a>';
	if (!$form->submitted())
		return print $form->getHTML();


	$values = $form->fetchAndValidateValues();
	if (!$form->valid)
		return print $form->getHTML();

	if ($new)
		$DB->tasks[]= array(
			'wid' => $wid,
			'tid' => $tid,
			'description' => $values['description']
		);
	else
		$DB->tasks($wid,$tid)->update($values);
	logUser('task edit', $wid);
	$PAGE->addMessage('Saved.', 'success');
	callAction('showWorkshopTasks', array($wid));
}

function actionDeleteTask($wid, $tid, $confirmed = false)
{
	global $USER, $PAGE, $DB;
	$wid = intval($wid);
	$tid = intval($tid);
	$lecturers = getLecturers($wid);
	if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
		throw new PolicyException();

	if (!$confirmed)
	{
		$PAGE->addMessage(_('Are you sure you want to delete the following task?'). '<br/>'.
		 	'<a class="button" href="deleteTask('. $wid .';'. $tid .';true)">'. _('Yes') .'</a> '.
		 	'<a class="button" href="showWorkshopTasks('. $wid .')">'. _('Cancel') .'</a>',
		 	'warning');
		 $PAGE->title = _('Task deletion');
		 $description = $DB->tasks($wid,$tid)->get('description');
		 echo '<div class="contentBox">'. parseUserHTML($description) .'</div>';
	}
	else
	{
		$DB->tasks($wid,$tid)->delete();
		$PAGE->addMessage(sprintf(_('Task %d. deleted.'), $tid), 'success');
		logUser('task delete', $wid);
		callAction('showWorkshopTasks', array($wid));
	}
}

function actionEditSolution($wid, $tid)
{
	global $DB, $USER, $PAGE;
	$wid = intval($wid);
	$tid = intval($tid);

	$participant = $DB->workshop_users($wid, $USER['uid'])->get('participant');
	if (!$participant)  throw new PolicyException();
	$isCandidate = ($participant == enumParticipantStatus('candidate')->id);

	$task = $DB->tasks($wid, $tid)->assoc('*');
	$DB->query('SELECT * FROM table_task_solutions
		WHERE wid=$1 AND tid=$2 AND uid=$3
		ORDER BY submitted DESC',
		$wid, $tid, $USER['uid']
	);
	$solutions = $DB->fetch_all();


	if (!is_array($solutions) || empty($solutions))
	{
		$PAGE->title = _('Task solution');
		$data = array(
			'wid' => $wid,
			'tid' => $tid,
			'uid' => $USER['uid'],
			'submitted' => time(),
			'solution' => NULL,
			'grade' => NULL,
			'feedback' => NULL,
			'comment' => NULL
		);
		$feedback = '';
	}
	else
	{
		$PAGE->title = _('Edit your solution');
		$data = $solutions[0];
		$feedback = _('status') .': '.  EnumSolutionStatus($solutions[0]['status'])->description .'<br/>';
		if (!empty($solutions[0]['grade']))
			$feedback .= _('grade') .': '. htmlspecialchars($solutions[0]['grade']) .'<br/>';
		if (!empty($solutions[0]['feedback']))
			$feedback .= _('comment') .': <div class="descriptionBox">'. htmlspecialchars($solutions[0]['feedback']) .'</div><br/>';
	}


	echo '<a class="back" href="showWorkshopTasks('. $wid .')">'. _('back') .'</a>';
	echo sprintf(_('Task %d. from %s'), $tid,  '<b>'. $DB->workshops[$wid]->get('title') .'</b>') .'<br/>';
	echo '<div class="descriptionBox">'. parseUserHTML($task['description']) .'</div>';
	echo $feedback;
	if (!$isCandidate)
		return print _('solution') .' <div class="descriptionBox">'. $data['solution'] .'</div>';

	$inputs = parseTable('
		NAME     => TYPE;         tDESCRIPTION;
		solution => richtextarea; solution;
	');
	$inputs['solution']['description'] .= ' &nbsp; <small>('. _('The editor allows you to attach files.') .')</small>';
	$form = new Form($inputs);
	$form->values = $data;
	if (!$form->submitted())
		return print $form->getHTML();
	$values = $form->fetchAndValidateValues();
	if (!$form->valid)
		return print $form->getHTML();

	$DB->task_solutions[] = array(
		'wid' => $wid,
		'tid' => $tid,
		'uid' => $USER['uid'],
		'submitted' => time(),
		'solution' => $values['solution'],
		'status' => 1,
		'notified' => 0
	);
	$PAGE->addMessage(_('Saved.'), 'success');
	logUser('task solve', $wid);
	callAction('editSolution', array($wid, $tid));
}

// Called by actionShowWorkshop
function buildParticipantList($wid)
{
	global $DB, $PAGE;
	$wid = intval($wid);
	$template = new SimpleTemplate();
	$DB->query('SELECT wu.uid, wu.participant, wu.points, u.gender
		FROM table_workshop_users wu, table_users u
		WHERE wu.uid=u.uid AND wu.wid=$1 AND wu.participant>0
		ORDER BY u.ordername, u.uid', $wid);
	$participants = $DB->fetch_all();

	$DB->query('SELECT tid FROM table_tasks WHERE wid=$1 ORDER BY tid', $wid);
	$tasks = $DB->fetch_column();

	$counts = array();
	foreach (enumParticipantStatus() as $statusName => $status)
		$counts[$status->id] = 0;
	foreach ($participants as $participant)
		$counts[$participant['participant']]++;
	$countDescription = array();
	foreach (enumParticipantStatus() as $statusName => $status)
		if ($statusName != 'none' || $counts[$status->id])
		$countDescription[]= genderize($status->description, 'p') .': '. $counts[$status->id]; // TODO i18n polish plural -ych


	echo '<h3 style="display:inline-block">'. _('Signups') .'</h3>';
	echo '<table class="right"><tr><td>'. implode('</td><td>', $countDescription) .'</td></tr></table><br/>';

	echo '<table style="border-top: 1px black solid">';
	echo '<thead><tr><th>'. _('participant') .'</th>';
	foreach ($tasks as $tid)  echo "<th>$tid</th>";
	echo '<th>'. _('points') .' (0..6)</th><th>'. _('overall') .'</th></tr></thead><tbody>';
	$staff = array();
	foreach ($participants as $participant)
	{
		$uid = $participant['uid'];
		$status = $participant['participant'];
		if ($status == enumParticipantStatus('lecturer')->id)
			; // Do nothing.
		else if ($status == enumParticipantStatus('autoaccepted')->id)
				$staff[$uid]= getUserBadge($uid);
		else
		{
			echo '<tr class="'. alternate('even', 'odd') .'">';
			echo '<td>'. getUserBadge($uid, true) .'</td>';
			// Get latest solutions (solutions submitted later overwrite earlier ones).
			$r = $DB->query('SELECT tid,status FROM table_task_solutions
				WHERE wid=$1 AND uid=$2 ORDER BY tid, submitted ASC', $wid, $uid);
			$solutions = array();
			foreach ($tasks as $tid)  $solutions[$tid] = enumSolutionStatus('none')->id;
			foreach ($r as $solution)  $solutions[$solution['tid']] = $solution['status'];
			foreach ($tasks as $tid)
			{
				$s = enumSolutionStatus($solutions[$tid]);
				echo '<td>'. getIcon($s->icon, $s->description) .'</td>';
			}
			echo '<td>'. $participant['points'] .'</td>';
			$desc = genderize(enumParticipantStatus($status)->description, $participant['gender']);
			$icon = getIcon('arrow-right.png',	_('see & grade solutions'), "showTaskSolutions($wid;$uid)");
			echo '<td>'. $desc .'<span class="right">'. $icon . '</span>';
			echo '</td></tr>';
		}
	}

	echo '</tbody></table>';
	if (!empty($staff))
		echo _('from staff: '). implode(', ', $staff) . '<br/>';

	return $template->finish();
}

function actionShowPointsTable()
{
	global $DB, $PAGE;
	$PAGE->title = _('Summary of points');
	$DB->query('SELECT wu.uid, wu.wid, wu.participant, wu.points, w.title, u.name
		FROM table_workshops w, table_workshop_users wu, table_users u
		WHERE wu.uid=u.uid AND w.edition=$1 AND wu.wid=w.wid AND wu.points IS NOT NULL
		ORDER BY u.ordername, wu.wid',
		getOption('currentEdition'));
	$data = $DB->fetch_all();
	if (empty($data))
		return print _('No data (no one has been graded yet).');

	$dataByUid = array();
	$titles = array();
	foreach ($data as $row)
	{
		if (!isset($dataByUid[$row['uid']]) || !is_array($dataByUid[$row['uid']]))
			$dataByUid[$row['uid']] = array('points' => array(), 'name' => $row['name']);
		$dataByUid[$row['uid']]['points'][$row['wid']]= $row['points'];
		$titles[$row['wid']] = $row['title'];
	}
	ksort($titles);
	$wids = array_keys($titles);
	echo '<table style="text-align: center"><thead><tr><th></th>';
	foreach ($wids as $wid)
		echo '<th><a '. getTipJS($titles[$wid]) .'>'. $wid .'</th>';
	echo '</thead><tbody>';
	$class = 'third';
	foreach ($dataByUid as $uid => $user)
	{
		echo '<tr class="'. $class .'"><td>'. $user['name'] .'</td>';
		$tdclass = 'third';
		foreach ($wids as $wid)
		{
			echo '<td class="'. $tdclass .'">';
			echo isset($user['points'][$wid]) ? $user['points'][$wid] : '-';
			echo '</td>';
			$tdclass = ($tdclass=='even')?'odd':(($tdclass=='odd')?'third':'even');
		}
		$class = ($class=='even')?'odd':(($class=='odd')?'third':'even');
	}
	// A row to equalize column widths.
	echo '<tr style="visibility: hidden"><td></td><td></td>';
	foreach ($wids as $wid)
	echo '<td>0000</td>';
	echo '</tr>';

	echo '</tbody></table>';


	echo '<table><thead><tr><th>#</th><th>'. _('title') .'</th></thead><tbody>';
	foreach ($titles as $wid => $title)
		echo "<tr><td>$wid</td><td>$title</td></tr>";
	echo '</tbody></table>';
}

function actionEditTasksComment($wid)
{
	global $DB, $PAGE, $USER;
	$wid = intval($wid);
	checkUserCanEditTasks($wid);
	$PAGE->title = _('Comments concerning tasks');
	$inputs = parseTable('
		NAME          => TYPE;         tDESCRIPTION;
		tasks_comment => richtextarea;
	');
	$inputs['tasks_comment']['description'] = _(
		'This is a place where you can add information concerning all tasks, how to '.
		'submit solutions, etc. You may, for example, attach a pdf with all the tasks. '.
		'If you won\'t be able to check tasks during some period, write about it here.');
	$form = new Form($inputs);
	if ($form->submitted())
	{
		$values = $form->fetchAndValidateValues();
		if ($form->valid)
		{
			$DB->workshops[$wid]->update($values);
			$PAGE->addMessage('Saved.', 'success');
			callAction('showWorkshopTasks', array($wid));
		}
	}
	$form->values = $DB->workshops[$wid]->assoc('tasks_comment');
	return print $form->getHTML();
}

function actionShowTaskSolutions($wid, $uid)
{
	global $DB, $USER, $PAGE;
	$wid = intval($wid);
	$uid = intval($uid);

	checkUserCanEditTasks($wid);

	$statusOptions = enumParticipantStatus()->assoc('id', 'description');
	$g = $DB->users[$uid]->get('gender');
	foreach ($statusOptions as &$option)
		$option = genderize($option, $g);

	$inputs = parseTable('
		NAME         => TYPE;     tDESCRIPTION;             VALIDATION;
		participant  => select;   status;
		points       => text;     points;                   int;
		admincomment => textarea; a comment for admins only;
	');
	$inputs['points']['description'] .= ' <small>(0..6)</small>';
	$inputs['participant']['options'] = $statusOptions;
	echo '<h3>'. $DB->workshops[$wid]->get('title') .'</h3>';
	echo getUserBadge($uid, true);
	$form = new Form($inputs);
	if ($form->submitted())
	{
		$values = $form->fetchAndValidateValues();
		if ($form->valid)
		{
			$DB->workshop_users($wid,$uid)->update($values);
			$PAGE->addMessage(_('Saved.'), 'success');
			logUser('task qualify', $wid);
		}
	}
	$form->values = $DB->workshop_users($wid,$uid)->assoc('participant,admincomment,points');

	$PAGE->title = _('Task solutions');
	echo '<a class="back" href="showWorkshopTasks('. $wid .')">wróć</a>';
	echo '<h4>'. _('Overall') .'</h4>';
	echo $form->getHTML();
	echo '<br/>';

	$tasks = $DB->query('SELECT tid FROM table_tasks WHERE wid=$1 ORDER BY tid', $wid);
	foreach ($tasks as $task)
	{
		$tid = $task['tid'];
		echo '<h4>'. _('Task'). ' '. $tid .'</h4>';
		$DB->query('SELECT * FROM table_task_solutions
		            WHERE wid=$1 AND tid=$2 AND uid=$3 ORDER BY submitted DESC LIMIT 4',
			$wid, $tid, $uid);
		$sols = $DB->fetch_all();
		if (empty($sols))
			echo _('No solution.');
		else
		{
			$sol = array_shift($sols);
			echo _('submitted') .': '. strftime("%F %T", $sol['submitted']);
			$inputs = parseTable('
				NAME     => TYPE;     tDESCRIPTION;
				status   => select;   status;
				grade    => text;     grade;
				feedback => textarea; feedback;
			');
			$inputs['status']['options'] = enumSolutionStatus()->assoc('id','description');
			$form = new Form($inputs);
			$form->action = "editSolutionsGradeForm($wid;$uid;$tid;". $sol['submitted'] .")";
			$form->values = $sol;

			echo '<div class="descriptionBox">';
			echo parseUserHTML($sol['solution']);
			echo '</div>';
			echo $form->getHTML();
			echo '<br/>';

			if (!empty($sols))
			{
				// We could write (instead of display:none)
				// $PAGE->jsOnLoad .= '$("#task'. $tid .'oldsols").hide();';
				// but it would flicker at page load. So now javascript-disabled
				// browsers can't see old solutions.
				echo '<a onclick="$(\'#oldsols'. $tid .'\').toggle(400);" style="cursor:pointer;">';
				echo '+ '. _('older solutions') .'</a>';
				echo '<div id="oldsols'. $tid .'" style="display:none">';
				foreach ($sols as $sol)
				{
					echo _('submitted'). ': '. strftime("%F %T", $sol['submitted']) .'<br/>';
					//echo 'status: '. enumSolutionStatuses($sol['status'])->description .'<br/>';
					if (!empty($sol['grade']))
						echo _('grade') .': '. htmlspecialchars($sol['grade']) .'<br/>';
					echo '<div class="descriptionBox">';
					echo parseUserHTML($sol['solution']);
					echo '</div><br/>';
				}
				echo '</div>';
			}
		}
		echo '<br/><br/>';
	}
}

function actionEditSolutionsGradeForm($wid,$uid,$tid,$submitted)
{
	global $DB,$USER,$PAGE;
	$wid = intval($wid);
	$uid = intval($uid);
	$tid = intval($tid);
	$submitted = intval($submitted);

	checkUserCanEditTasks($wid);

	$DB->task_solutions($wid, $tid, $uid, $submitted)->update(array(
		'status' => intval($_POST['status']),
		'grade' => $_POST['grade'],
		'feedback' => $_POST['feedback'],
		'notified' => 2
	));
	$PAGE->addMessage(sprintf(_('Task %d graded.'), $tid), 'success');
	callAction('showTaskSolutions', array($wid, $uid));
	logUser('task grade', $wid);
}

function checkUserCanEditTasks($wid)
{
	$lecturers = getLecturers($wid);
	if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
		throw new PolicyException();
}
