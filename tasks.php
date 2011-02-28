<?php
/*
	tasks.php
	Included in warsztaty.php
*/
$participantStatuses = array(
	0 => 'niezapisany',
	1 => 'wstępnie zapisany',
	2 => 'uczestnik nie spełnia wymagań',
	3 => 'zakwalifikowany',
	4 => 'zapisany (kadra)'
);
$solutionStatuses = array(
	1 => 'czeka na sprawdzenie',
 	2 => 'do poprawy',
 	3 => 'ocenione'
);

// Called by actionShowWorkshop
function buildTaskList($wid)
{
	global $USER, $DB;
	$lecturers = getLecturers($wid); 
	
	$template = new SimpleTemplate();
	echo '<h3>Zadania kwalifikacyjne</h3>';	
	
	$taskComment = $DB->workshops[$wid]->get('tasks_comment');
	
	if (userCan('editTasks', $lecturers) && userCan('editWorkshop', $lecturers))
	{		
		echo '<span class="right">';
		echo '<a '.
			getTipJS('Możesz tu wpisać informacje dotyczące wszystkich zadań i nadsyłania
				rozwiązań.<br/>Jeżeli nie będziesz w stanie sprawdzać rozwiązań przez jakiś
				czas, koniecznie tu napisz!<br/>Jeśli Ci wygodniej, możesz np. załączyć
				tu pdf-a z treścią wszystkich zadań.');
		echo '>[co to?]</a> ';
		echo getIcon('pencil.png', 'edytuj', "editTasksComment($wid)");
		echo '</span>';		
	}
	echo '<div class="descriptionBox">'. parseUserHTML($taskComment) .'</div>';
	
	
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
			echo getIcon('plugin-edit.png', 'edytuj zadanie', 'editTask'. $params);
			echo getIcon('plugin-delete.png', 'usuń zadanie', 'deleteTask'. $params);			
			echo '</td>';
		}		
		if ($participant && userCan('sendTaskSolution'))
		{
			echo '<td>';
			$DB->query('SELECT status, grade FROM table_task_solutions
				WHERE wid=$1 AND uid=$2 AND tid=$3
				ORDER BY submitted DESC LIMIT 1',
				$wid, $USER['uid'], $task['tid']);
			$sol = $DB->fetch_assoc();
			
			if ($sol === false)  echo 'wyślij';
			else  echo EnumSolutionStatus($sol['status'])->description;
			echo ' ';
			echo getIcon('arrow-right.png', 'twoje rozwiązanie', 'editSolution'. $params);
			echo '</td>';
		}
		echo '</tr>';
	}
	if (userCan('editTasks', $lecturers) && userCan('editWorkshop', $lecturers))
		echo '<tr><td colspan="2">'.
			getButton('dodaj zadanie', "createTask($wid)", 'plugin-add.png').
			'</td></tr>';
	echo '</table><br/>';
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
	
	$form = new Form(array(
		array('readonly',     'title',       'warsztaty', 'default'=>$DB->workshops[$wid]->get('title')),
		array('richtextarea', 'description', 'treść'),
	));
	$form->action = ($new?'create':'edit') ."TaskForm($wid;$tid)";	
		
	if ($new)
	{
		$PAGE->title = 'Nowe zadanie kwalifikacyjne';		
		$form->values = array(
			'wid' => $wid,
			'tid' => $tid,
			'description' => '',
			'inline' => 1
		);
	}
	else
	{
		$PAGE->title = 'Edycja zadania kwalifikacyjnego';
		$form->values = $DB->tasks($wid,$tid)->assoc('*');
	}
	
	$form->submitValue = 'Zapisz';
	$PAGE->content .= '<a class="back" href="showWorkshop('. $wid .')">wróć</a>';	
	$PAGE->content .= $form->getHTML(); 
}

function actionCreateTaskForm($wid, $tid)
{
	global $DB, $PAGE;
	$wid = intval($wid);
	$tid = intval($tid);
	$lecturers = getLecturers($wid);
	if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
		throw new PolicyException();
		
	$DB->tasks[]= array(
		'wid' => $wid,
		'tid' => $tid,
		'description' => $_POST['description'],
		'inline' => 1
	);
	
	logUser('task create', $wid);
	$PAGE->addMessage('Pomyślnie utworzono zadanie', 'success');
	actionEditTask($wid, $tid);
}

function actionEditTaskForm($wid, $tid)
{
	global $DB, $PAGE;
	$wid = intval($wid);
	$tid = intval($tid);
	
	$lecturers = getLecturers($wid);
	if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
		throw new PolicyException();	
	
	$DB->tasks($wid,$tid)->update(array(		
			'description' => $_POST['description'],
			'inline' => 1
	));
	logUser('task edit', $wid);
	$PAGE->addMessage('Pomyślnie zmieniono zadanie', 'success');
	actionEditTask($wid, $tid);	
}

function actionDeleteTask($wid, $tid, $confirmed = false)
{
	global $USER, $PAGE, $DB;
	$wid = intval($wid);
	$tid = intval($tid);
		
	if (!$confirmed)
	{	
		$PAGE->addMessage('Czy na pewno chcesz usunąć poniższe zadanie?<br/>'.
		 	'<a class="button" href="deleteTask('. $wid .';'. $tid .';true)">Tak</a> '.
		 	'<a class="button" href="showWorkshop('. $wid .')">Anuluj</a>',
		 	'warning');
		 $PAGE->title = 'Usuwanie zadania';
		 $description = $DB->tasks($wid,$tid)->get('description');
		 $PAGE->content .= '<div class="contentBox">'. parseUserHTML($description) .'</div>';
	}	
	else
	{
		$lecturers = getLecturers($wid);
		if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
			throw new PolicyException();	
			
		$DB->tasks($wid,$tid)->delete();
		$PAGE->addMessage('Usunięto zadanie.', 'success');
		logUser('task delete', $wid);
		actionShowWorkshop($wid);
	}
}

function actionEditSolution($wid, $tid)
{
	global $DB, $USER, $PAGE;
	$wid = intval($wid);
	$tid = intval($tid);
	
	$participant = $DB->workshop_user($wid, $USER['uid'])->get('participant');
	if (!$participant)  throw new PolicyException();

	$task = $DB->tasks($wid, $tid)->assoc('*');
	$DB->query("SELECT * FROM table_task_solutions
		WHERE wid=$1 AND tid=$2 AND uid=$3
		ORDER BY submitted DESC",
		$wid, $tid, $USER['uid']
	);
	$solutions = $DB->fetch_all();
	
	
	if (!is_array($solutions) || empty($solutions))
	{
		$PAGE->title = 'Rozwiązanie zadania';
		$data = array(
			'wid' => $wid,
			'tid' => $tid,
			'uid' => $USER['uid'],
			'submitted' => time(),
			'grade' => NULL,
			'feedback' => NULL,
			'comment' => NULL
		);
		$feedback = '';
	}
	else
	{		
		$PAGE->title = 'Edycja rozwiązania';
		$data = $solutions[0];
		$feedback = 'status: '. EnumSolutionStatus($solutions[0]['status'])->description .'<br/>';
		if (!empty($solutions[0]['grade']))
			$feedback .= 'ocena: '. htmlspecialchars($solutions[0]['grade']) .'<br/>';
		if (!empty($solutions[0]['feedback']))
			$feedback .= 'komentarz: '. htmlspecialchars($solutions[0]['feedback']) .'<br/>';
	}
	
	$form = new Form(array(
		array('richtextarea', 'solution', 'rozwiązanie &nbsp; <small>(edytor umożliwia załączanie plików)</small>')
	));
	$form->action = "editSolutionForm($wid;$tid)";
	$form->submitValue = 'Zapisz';
	$form->values = $data;
	
	$PAGE->content .= '<a class="back" href="showWorkshop('. $wid .')">wróć</a>';
	$PAGE->content .= 'Zadanie '. $tid .' z <b>'. $DB->workshops[$wid]->get('title') .'</b><br/>';
	$PAGE->content .= 'treść <div class="descriptionBox">'. parseUserHTML($task['description']) .'</div>';
	$PAGE->content .= $feedback;	
	$PAGE->content .= $form->getHTML();
}

function actionEditSolutionForm($wid, $tid)
{
	global $DB, $USER, $PAGE;
	$wid = intval($wid);
	$tid = intval($tid);
	
	$participant = $DB->workshop_user($wid, $USER['uid'])->get('participant');
	if (!$participant)  throw new PolicyException();
	
	$DB->task_solutions[] = array(
		'wid' => $wid,
		'tid' => $tid,
		'uid' => $USER['uid'],
		'submitted' => time(),
		'solution' => $_POST['solution'],
		'status' => 1,
		'notified' => 0
	);
	$PAGE->addMessage('Pomyślnie zapisano rozwiązane.', 'success');
	actionEditSolution($wid, $tid);
	logUser('task solve', $wid);
}

// Called by actionShowWorkshop
function buildParticipantList($wid)
{ 
	global $DB, $PAGE;
	$wid = intval($wid);
	$template = new SimpleTemplate();
	$DB->query('SELECT wu.uid, wu.participant, u.gender
		FROM table_workshop_user wu, table_users u
		WHERE wu.uid=u.uid AND wu.wid=$1 AND wu.participant>0
		ORDER BY regexp_replace(u.name,\'.*\ ([^\ ]+)\',\'\\\\1\'), u.uid', $wid);
	$participants = $DB->fetch_all();
	
	$DB->query('SELECT tid FROM table_tasks WHERE wid=$1 ORDER BY tid', $wid);
	$tasks = $DB->fetch_column();
	
	$counts = array();
	foreach(enumParticipantStatus() as $statusName => $status)
		$counts[$status->id] = 0;
	foreach ($participants as $participant)
		$counts[$participant['participant']]++;
	$countDescription = array();
	foreach(enumParticipantStatus() as $statusName => $status)
		if ($statusName != 'none' || $counts[$status->id])
		$countDescription[]= str_replace('%','ych',$status->description) .': '. $counts[$status->id];
				/*$row['participants'] .= '<a '. getTipJS($tip) .'>';
				$row['participants'] .= ($row['count_accepted'] + $row['count_autoaccepted']) .'</a>';*/
		
	
	echo '<h3 style="display:inline-block">Zapisani</h3>';
	echo '<table class="right"><tr><td>'. implode('</td><td>', $countDescription) .'</td></tr></table><br/>';
	echo '<table style="border-top: 1px black solid">';
	echo '<thead><tr><th>uczestnik</th>';
	foreach ($tasks as $tid)  echo "<th>$tid</th>";
	echo '<th>ogólnie</th></tr></thead><tbody>';
	$kadra = array();
	foreach ($participants as $participant)
	{
		$uid = $participant['uid'];
		$status = $participant['participant'];
		if (count($DB->user_roles($uid, 'kadra')))
		{			
			if ($status != enumParticipantStatus('lecturer')->id)
				$kadra[$uid]= getUserBadge($uid);
		}
		else
		{
			echo '<tr class="'. alternate('even', 'odd') .'">';
			echo '<td>'. getUserBadge($uid, true) .'</td>';
			// Get latest solutions (solutions submitted later overwrite earlier ones).
			$r = $DB->query('SELECT tid,status FROM table_task_solutions
				WHERE wid=$1 AND uid=$2 ORDER BY tid, submitted ASC', $wid, $uid);
			$solutions = array();
			foreach ($tasks as $tid)  $solutions[$tid] = EnumSolutionStatus('none')->id;
			foreach ($r as $solution)  $solutions[$solution['tid']] = $solution['status'];
			foreach ($tasks as $tid)
			{
				$s = EnumSolutionStatus($solutions[$tid]);
				echo '<td>'. getIcon($s->icon, $s->description) .'</td>';
			}
			$desc = EnumParticipantStatus($status)->description;
			$desc = str_replace('%', gender('y','a', $participant['gender']), $desc);
			$icon = getIcon('arrow-right.png',	'zobacz i oceń rozwiązania', "showTaskSolutions($wid;$uid)");
			echo '<td>'. $desc .'<span class="right">'. $icon . '</span>';
			echo '</td></tr>';
		}
	}
	
	echo '</tbody></table>';
	if (!empty($kadra))
		echo 'z kadry: '. implode(', ', $kadra) . '<br/>';
	
	return $template->finish();
}

function actionEditTasksComment($wid)
{
	global $PAGE, $USER;
	$wid = intval($wid);
	checkUserCanEditTasks($wid);
	$PAGE->title = 'Komentarz do zadań'; //db_get('workshops', $wid, 'title');
	$form = new Form(array(
		array('richtextarea', 'tasks_comment', 
			'Możesz tu wpisać informacje dotyczące wszystkich zadań i nadsyłania
			rozwiązań.<br/>Jeżeli nie będziesz w stanie sprawdzać rozwiązań przez jakiś
			czas, koniecznie tu napisz!<br/>Jeśli Ci wygodniej, możesz np. załączyć
			tu pdf-a z treścią wszystkich zadań.')
	));
	$form->action = "editTasksCommentForm($wid)";	
	$form->values = array('tasks_comment'=>db_get('workshops', $wid, 'tasks_comment'));
	$PAGE->content .= $form->getHTML();
}

function actionEditTasksCommentForm($wid)
{
	global $DB, $PAGE;
	$wid = intval($wid);
	checkUserCanEditTasks($wid);
	$DB->workshops[$wid]->update(array('tasks_comment'=>$_POST['tasks_comment']));
	$PAGE->addMessage('Pomyślnie zapisano komentarz do zadań.', 'success');
	actionShowWorkshop($wid);
}

function actionShowTaskSolutions($wid, $uid)
{
	global $DB, $USER, $PAGE;
	$wid = intval($wid);
	$uid = intval($uid);
	
	checkUserCanEditTasks($wid);
	
	$statusOptions = enumParticipantStatus()->assoc('id','description');
	$g = $DB->users[$uid]->get('gender');
	foreach ($statusOptions as &$option)
		$option = str_replace('%', gender('y','a',$g), $option);
	
	$form = new Form(array(
		array('readonly', 'workshop',     'warsztaty', 'default' => $DB->workshops[$wid]->get('title')),
		array('readonly', 'name',         'uczestnik', 'default' => getUserBadge($uid, true)),
		array('select',   'participant',  'status',	'options' => $statusOptions),
		array('int',      'points',       'punktów <small>(0..20)</small>'),
		array('textarea', 'admincomment', 'komentarz tylko dla organizatorów')
	));
	$form->action = "showTaskSolutionsForm($wid;$uid)";
	$form->values = $DB->workshop_user($wid,$uid)->assoc('participant,admincomment,points');
	$form->submitValue = 'Zapisz';
	
	$PAGE->title = 'Rozwiązania zadań';
	$PAGE->content .= '<a class="back" href="showWorkshop('. $wid .')">wróć</a>';
	$PAGE->content .= '<h3>Ogólnie</h3>';
	$PAGE->content .= $form->getHTML();
	$PAGE->content .= '<br/>';
	
	$template = new SimpleTemplate();
	$tasks = $DB->query('SELECT tid FROM table_tasks WHERE wid=$1 ORDER BY tid', $wid);
	foreach ($tasks as $task)
	{
		$tid = $task['tid'];
		echo "<h3>Zadanie $tid.</h3>";
		$DB->query('SELECT * FROM table_task_solutions
			WHERE wid=$1 AND tid=$2 AND uid=$3 ORDER BY submitted DESC LIMIT 4', $wid, $tid, $uid);
		$sols = $DB->fetch_all();
		if (empty($sols))
			echo 'Brak rozwiązania.';
		else
		{
			$sol = array_shift($sols);
			$form = new Form(array(
				array('text',     'submitted', 'nadesłane', 'readonly'=>true,
					'default'=>strftime("%F %T", $sol['submitted'])                  ),
				array('select',   'status',    'status',
					'options'=>enumSolutionStatus()->assoc('id','description')),
				array('text',     'grade',     'ocena'                               ),
				array('textarea', 'feedback',  'komentarz'                           )
			));
			$form->action = "editSolutionsGradeForm($wid;$uid;$tid;${sol['submitted']})";
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
				// but it would make a flicker at page load. So now javascript-disabled 
				// browsers can't see old solutions.
				echo '<a onclick="$(\'#oldsols'. $tid .'\').toggle(400);">starsze rozwiązania</a>';
				echo '<div id="oldsols'. $tid .'" style="display:none">';
				foreach ($sols as $sol)
				{
					echo 'nadesłane: '. strftime("%F %T", $sol['submitted']) .'<br/>';
					//echo 'status: '. EnumSolutionStatuses($sol['status'])->description .'<br/>';
					if (!empty($sol['grade']))
						echo 'ocena: '. htmlspecialchars($sol['grade']) .'<br/>';
					echo '<div class="descriptionBox">';
					echo parseUserHTML($sol['solution']);
					echo '</div><br/>';
				}
				echo '</div>';
			}
		}
		echo '<br/><br/>';
	}
		
	$PAGE->content .= $template->finish();
}

function actionShowTaskSolutionsForm($wid,$uid)
{
	global $DB,$USER,$PAGE;
	$wid = intval($wid);
	$uid = intval($uid);
	
	checkUserCanEditTasks($wid);
		
	$DB->workshop_user($wid,$uid)->update(array(
		'participant' => enumParticipantStatus($_POST['participant'])->id,
		'points' => (strlen(trim($_POST['points']))==0) ? NULL : intval($_POST['points']),
		'admincomment' => $_POST['admincomment']
	));
	$PAGE->addMessage('Pomyślnie zapisano informacje ogólne o rozwiązaniach.', 'success');
	logUser('task qualify', $wid);
	actionShowTaskSolutions($wid, $uid);
}

function actionEditSolutionsGradeForm($wid,$uid,$tid,$submitted)
{
	global $DB,$USER,$PAGE;
	$wid = intval($wid);
	$uid = intval($uid);
	$tid = intval($tid);
	$submitted = intval($submitted);
	
	checkUserCanEditTasks($wid);
	
	$DB->task_solutions($wid, $uid, $tid, $submitted)->update(array(
		'status' => $_POST['status'],
		'grade' => $_POST['grade'],
		'feedback' => $_POST['feedback']
	));
	$PAGE->addMessage("Pomyślnie zapisano informacje o zadaniu $tid.", 'success');
	actionShowTaskSolutions($wid, $uid);
	logUser('task grade', $wid);	
}

function checkUserCanEditTasks($wid)
{
	$lecturers = getLecturers($wid);
	if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
		throw new PolicyException();
}
