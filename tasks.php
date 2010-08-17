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
function buildTaskList($wid, $participant)
{
	global $USER, $participantStatuses, $solutionStatuses;
	$result = db_query("SELECT uid
				FROM table_workshop_user
				WHERE lecturer>0 AND wid=". $wid);
	$lecturers = db_fetch_all_columns($result);
	
	$template = new SimpleTemplate();
	echo '<h3>Zadania kwalifikacyjne</h3>';	
	if ($participant && userCan('sendTaskSolution'))
		echo 'Twój status: <i>'. $participantStatuses[$participant] .'</i>';
	
	$taskComment = db_get('workshops', $wid, 'tasks_comment');
	
	if (userCan('editTasks', $lecturers) && userCan('editWorkshop', $lecturers))
	{		
		echo '<span class="right">(';
		$tip = 'Możesz tu wpisać informacje dotyczące wszystkich zadań i nadsyłania
			rozwiązań.<br/>Jeżeli nie będziesz w stanie sprawdzać rozwiązań przez jakiś
			czas, koniecznie tu napisz!<br/>Jeśli Ci wygodniej, możesz np. załączyć
			tu pdf-a z treścią wszystkich zadań.';
		$tip = addcslashes($tip, "\n\r");
		echo "<a onmouseout='tipoff()' onmouseover='tipon(this,\"$tip\")'>co to?";
		echo "</a>";
		echo ') ';
		echo getIcon('pencil.png', 'edytuj', "?action=editTasksComment&amp;wid=$wid");
		echo '</span>';		
	}
	echo '<div class="descriptionBox">'. parseUserHTML($taskComment) .'</div>';
	
	
	echo '<table class="tasks">';
	$result = db_query('SELECT * FROM table_tasks WHERE wid='. $wid .' ORDER BY tid');
	$class = 'even';
	while ($task = db_fetch_assoc($result))
	{
		echo '<tr class="'. $class .'"><td>'. $task['tid'] .'</td>';
		echo '<td>'. parseUserHTML($task['description']) .'</td>';		
		$where = "&wid=$wid&tid=". $task['tid'];
		if (userCan('editTasks', $lecturers) && userCan('editWorkshop', $lecturers))
		{
			echo '<td>';
			echo getIcon('plugin-edit.png', 'edytuj zadanie', '?action=editTask'. $where);
			echo getIcon('plugin-delete.png', 'usuń zadanie', '?action=deleteTask'. $where);			
			echo '</td>';
		}		
		if ($participant && userCan('sendTaskSolution'))
		{
			echo '<td>';
			$r = db_query("SELECT status, grade FROM table_task_solutions
				WHERE wid=$wid AND uid=". $USER['uid'] ." AND tid=". $task['tid'] ."
				ORDER BY submitted DESC LIMIT 1");
			$sol = db_fetch_assoc($r);
			
			if ($sol === false)  echo 'wyślij';
			else  echo $solutionStatuses[$sol['status']];
			echo ' ';
			echo getIcon('arrow-right.png', 'twoje rozwiązanie', '?action=editSolution'. $where);
			echo '</td>';
		}
		echo '</tr>';
		$class = ($class=='even')?'odd':'even';
	}
	if (userCan('editTasks', $lecturers) && userCan('editWorkshop', $lecturers))
		echo '<tr><td colspan="2">'.
			getButton('dodaj zadanie', '?action=createTask&wid='. $wid, 'plugin-add.png').
			'</td></tr>';
	echo '</table><br/>';
	return $template->finish();
}

function actionCreateTask()
{	
	if (isset($_POST['description']))
	{
		handleCreateTaskForm();
		unset($_POST['description']);
		actionEditTask();
		return;
	}
	
	global $PAGE;
	$PAGE->title = 'Nowe zadanie kwalifikacyjne';
	$wid = intval($_GET['wid']);
	$result = db_query('SELECT MAX(tid) FROM table_tasks WHERE wid='. $wid);
	$tid = intval(db_fetch($result))+1;
	actionEditTask($tid);
}

function actionEditTask($tid = null)
{
	global $USER, $PAGE;
	$wid = intval($_GET['wid']);
	$result = db_query("SELECT uid
				FROM table_workshop_user
				WHERE lecturer>0 AND wid=". $wid);
	$lecturers = db_fetch_all_columns($result);	
	if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
		throw new PolicyException();

	if (isset($_POST['description']))  handleEditTaskForm();

	$new = !is_null($tid);
	if ($new)
	{
		$data = array(
			'wid' => $wid,
			'tid' => $tid,
			'description' => '',
			'inline' => 1
		);
	}
	else
	{
		$tid = intval($_GET['tid']);
		$PAGE->title = 'Edycja zadania kwalifikacyjnego';
		$result = db_query('SELECT * FROM table_tasks
			WHERE wid='. $wid .' AND tid='. $tid);
		$data = db_fetch_assoc($result);
	}
	
	$inputs = array(
		array('type'=>'readonly', 'name'=>'title', 'description'=>'warsztaty',
			'default'=>db_get('workshops',$wid,'title')),
		array('type'=>'richtextarea', 'name'=>'description', 'description'=>'treść'),
	);
	
	$params = array(
		'wid' => $wid,
		'tid' => $tid,
		'action' => $new?'create':'edit'
	);
	$template = new SimpleTemplate($params);
	?>
	<a class="back" href="?action=showWorkshop&amp;wid=%wid%">wróć</a>
	
	<h2><?php echo $PAGE->title; ?></h2>
	<form method="post" action="?action=%action%Task&amp;wid=%wid%&amp;tid=%tid%" name="form"
		id="theform">
		<table><?php
			generateFormRows($inputs, $data);
		?></table>
		<input type="submit" value="zapisz" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function handleCreateTaskForm()
{
	$wid = intval($_GET['wid']);
	$result = db_query("SELECT uid
				FROM table_workshop_user
				WHERE lecturer>0 AND wid=". $wid);
	$lecturers = db_fetch_all_columns($result);	
	if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
		throw new PolicyException();
		
	$data = array(
		'wid' => $wid,
		'tid' => intval($_GET['tid']),
		'description' => $_POST['description'],
		'inline' => 1
	);
	db_insert('tasks', $data);
	logUser('task create', $wid);
	showMessage('Pomyślnie utworzono zadanie', 'success');
}

function handleEditTaskForm()
{
	$wid = intval($_GET['wid']);
	$tid = intval($_GET['tid']);
	
	$result = db_query("SELECT uid
				FROM table_workshop_user
				WHERE lecturer>0 AND wid=". $wid);
	$lecturers = db_fetch_all_columns($result);	
	if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
		throw new PolicyException();	
	db_update('tasks',	"WHERE wid=$wid AND tid=$tid", array(		
			'description' => $_POST['description'],
			'inline' => 1
	));
	logUser('task edit', $wid);
	showMessage('Pomyślnie zmieniono zadanie', 'success');
}

function actionDeleteTask()
{
	global $USER, $PAGE;
	$wid = intval($_GET['wid']);
	$tid = intval($_GET['tid']);
		
	if (!isset($_GET['confirmed']))
	{	
		showMessage('Czy na pewno chcesz usunąć poniższe zadanie?<br/>'.
		 	'<a class="button" href="?action=deleteTask&amp;confirmed&amp;wid='. $wid .
		 		'&amp;tid='. $tid .'">Tak</a> '.
		 	'<a class="button" href="?action=showWorkshop&amp;wid='. $wid .'">Anuluj</a>',
		 	'warning');
		 $PAGE->title = 'Usuwanie zadania';
		 $result = db_query("SELECT description FROM table_tasks WHERE wid=$wid AND tid=$tid");
		 $PAGE->content .= '<div class="contentBox">'. parseUserHTML(db_fetch($result)) .'</div>';
	}	
	else
	{
		$result = db_query("SELECT uid
					FROM table_workshop_user
					WHERE lecturer>0 AND wid=". $wid);
		$lecturers = db_fetch_all_columns($result);	
		if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
			throw new PolicyException();	
			
		db_query("DELETE FROM table_tasks WHERE wid=$wid AND tid=$tid");
		showMessage('Usunięto zadanie.', 'success');
		logUser('task delete', $wid);
		actionShowWorkshop();
	}
}

function actionEditSolution()
{
	global $USER, $PAGE, $solutionStatuses;
	$wid = intval($_GET['wid']);
	$tid = intval($_GET['tid']);
	$result = db_query("SELECT participant
				FROM table_workshop_user
				WHERE uid=". $USER['uid'] ." AND wid=$wid");
	$participant = db_fetch($result);	
	if (!$participant)  throw new PolicyException();

	$result = db_query("SELECT * FROM table_tasks WHERE wid=$wid AND tid=$tid");
	$task = db_fetch_assoc($result);
	$result = db_query("SELECT * FROM table_task_solutions
		WHERE wid=$wid AND tid=$tid AND uid=". $USER['uid'] ."
		ORDER BY submitted DESC"
	);
	$solutions = db_fetch_all($result);
	
	
	if (!is_array($solutions) || empty($solutions))
	{
		$PAGE->title = 'Rozwiązanie zadania';
		$data = array(
			'wid' => $wid,
			'tid' => $tid,
			'uid' => $USER['uid'],
			'sumbitted' => time(),
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
		$feedback = 'status: '. $solutionStatuses[$solutions[0]['status']] .'<br/>';
		if (!empty($solutions[0]['grade']))
			$feedback .= 'ocena: '. htmlspecialchars($solutions[0]['grade']) .'<br/>';
		if (!empty($solutions[0]['feedback']))
			$feedback .= 'komentarz: '. htmlspecialchars($solutions[0]['feedback']) .'<br/>';
	}
	
	$inputs = array(
		array('type'=>'richtextarea', 'name'=>'solution', 'description'=>'rozwiązanie'.
			' &nbsp; <small>(edytor umożliwia załączanie plików)</small>'),
	);
	
	$params = array(
		'wid' => $wid,
		'tid' => $tid,
		'title' => db_get('workshops', $wid, 'title'),
		'description' => parseUserHTML($task['description']),
		'feedback' => $feedback
	);
	$template = new SimpleTemplate($params);
	?>
	<a class="back" href="?action=showWorkshop&amp;wid=%wid%">wróć</a>
	
	<h2><?php echo $PAGE->title; ?></h2>
	Zadanie %tid%. z <b>%title%</b><br/>
	treść
	<div class="descriptionBox">%description%</div>
	%feedback%
	<form method="post" action="?action=editSolutionForm&amp;wid=%wid%&amp;tid=%tid%"
		name="form" id="theform">
		<table><?php
			generateFormRows($inputs, $data);
		?></table>
		<input type="submit" value="zapisz" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function actionEditSolutionForm()
{
	global $USER;
	$wid = intval($_GET['wid']);
	$tid = intval($_GET['tid']);
	
	$result = db_query("SELECT participant
				FROM table_workshop_user
				WHERE uid=". $USER['uid'] ." AND wid=$wid");
	$participant = db_fetch($result);	
	if (!$participant)  throw new PolicyException();
	
	db_insert('task_solutions', array(
		'wid' => $wid,
		'tid' => $tid,
		'uid' => $USER['uid'],
		'submitted' => time(),
		'solution' => $_POST['solution'],
		'status' => 1,
		'notified' => 0
	));
	showMessage('Pomyślnie zapisano rozwiązane.', 'success');
	actionEditSolution();
	logUser('task solve', $wid);
}

// Called by actionShowWorkshop
function buildParticipantList($wid)
{ 
	$wid = intval($wid);
	$template = new SimpleTemplate();
	$r = db_query('SELECT uid, participant FROM table_workshop_user '.
		'WHERE wid='. $wid .' AND participant>0 ORDER BY uid');
	$participants = array();
	while ($row = db_fetch_assoc($r))  $participants[$row['uid']] = $row['participant'];
	
	$r = db_query('SELECT tid FROM table_tasks WHERE wid='. $wid .' ORDER BY tid');
	$tasks = db_fetch_all_columns($r);
	
	echo '<h3 style="display:inline-block">Zapisani</h3>';
	echo '<span class="right">w sumie: '. count($participants) .'</span><br/>';
	echo '<table style="border-top: 1px black solid">';
	echo '<thead><tr><th>uczestnik</th>';
	foreach ($tasks as $tid)  echo "<th>$tid</th>";
	echo '<th>ogólnie</th></tr></thead><tbody>';
	$kadra = array();
	$class = 'even';
	foreach ($participants as $p=>$status)
	{
		$r = db_query("SELECT count(*) FROM table_user_roles
			WHERE uid=$p AND role='kadra'");
		$isKadra = db_fetch($r);
		if ($isKadra)
		{
			if ($status != enumParticipantStatus('lecturer')->id)
				$kadra[$p]= getUserBadge($p);
		}
		else
		{
			echo "<tr class='$class'>";
			echo "<td>". getUserBadge($p, true) ."</td>";
			$r = db_query("SELECT tid,status FROM table_task_solutions
				WHERE wid=$wid AND uid=$p ORDER BY tid");
			$r = db_fetch_all($r);
			$solutions = array();
			foreach ($r as $solution)  $solutions[$solution['tid']] = $solution['status'];
			global $solutionStatuses;			
			foreach ($tasks as $tid)
			{
				if (!empty($solutions[$tid]))
					echo "<td>". $solutionStatuses[$solutions[$tid]] ."</td>";
				else 
					echo "<td>brak</td>";
			}
			global $participantStatuses;
			echo "<td>" .$participantStatuses[$status];
			echo "<span class='right'>". getIcon('arrow-right.png',	'zobacz i oceń rozwiązania',
				'?action=showTaskSolutions&amp;wid='. $wid .'&amp;uid='. $p) . "</span>";
			echo "</td></tr>";
			$class = ($class=='odd')?'even':'odd';
		}
	}
	
	echo '</tbody></table>';
	if (!empty($kadra))
		echo 'z kadry: '. implode(', ', $kadra) . '<br/>';
	
	return $template->finish();
}

function actionEditTasksComment()
{
	global $PAGE, $USER;
	$wid = intval($_GET['wid']);
	checkUserCanEditTasks($wid);
	$desc = 'Możesz tu wpisać informacje dotyczące wszystkich zadań i nadsyłania
			rozwiązań.<br/>Jeżeli nie będziesz w stanie sprawdzać rozwiązań przez jakiś
			czas, koniecznie tu napisz!<br/>Jeśli Ci wygodniej, możesz np. załączyć
			tu pdf-a z treścią wszystkich zadań.';
	$inputs = array( array('type'=>'richtextarea', 'name'=>'tasks_comment', 'description'=>$desc) );	
	$PAGE->title = 'Komentarz do zadań'; //db_get('workshops', $wid, 'title');
	$data = array('tasks_comment'=>db_get('workshops', $wid, 'tasks_comment'));
	$template = new SimpleTemplate(array('wid'=>$wid));
	?>
	<h2><?php echo $PAGE->title; ?></h2>
	<form method="post" action="?action=editTasksCommentForm&amp;wid=%wid%" name="form" id="theform">
		<table><?php generateFormRows($inputs, $data);	?></table>
		<input type="submit" value="zapisz" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function actionEditTasksCommentForm()
{
	$wid = intval($_GET['wid']);
	checkUserCanEditTasks($wid);
	db_update('workshops', 'WHERE wid='. $wid, array('tasks_comment'=>$_POST['tasks_comment']));
	showMessage('Pomyślnie zapisano komentarz do zadań.', 'success');
	actionShowWorkshop();
}

function actionShowTaskSolutions()
{
	global $USER,$PAGE, $participantStatuses, $solutionStatuses;
	$wid = intval($_GET['wid']);
	$uid = intval($_GET['uid']);
	$workshop = db_get('workshops', $wid, 'title');
	
	checkUserCanEditTasks($wid);
	
	$r = db_query("SELECT participant, admincomment, points FROM table_workshop_user
		WHERE wid=$wid AND uid=$uid");
	$data = db_fetch_assoc($r);
		
	$inputs = array(
		array('type'=>'readonly', 'name'=>'workshop', 'description'=>'warsztaty',
			'default'=>$workshop),
		array('type'=>'readonly', 'name'=>'name', 'description'=>'uczestnik',
			'default'=>getUserBadge($uid, true)),
		array('type'=>'select', 'name'=>'participant', 'description'=>'status',
			'options'=>$participantStatuses),
		array('type'=>'int', 'name'=>'points', 'description'=>'punktów <small>(0..20)</small>'),
		array('type'=>'textarea', 'name'=>'admincomment',
			'description'=>'komentarz tylko dla organizatorów')
	);
	
	$PAGE->title = 'Rozwiązania zadań';
	$template = new SimpleTemplate(array('wid'=>$wid, 'uid'=>$uid));
	?>
	<a class="back" href="?action=showWorkshop&amp;wid=%wid%">wróć</a>
	<h2><?php echo $PAGE->title; ?></h2>	
	<h3>Ogólnie</h3>
	<form method="post" action="?action=showTaskSolutionsForm&amp;wid=%wid%&amp;uid=%uid%"
		name="form" id="theform">
		<table><?php generateFormRows($inputs, $data); ?></table>
		<input type="submit" value="zapisz" />
	</form><br/>
	<?php
	
		
	$r = db_query("SELECT tid FROM table_tasks WHERE wid=$wid ORDER BY tid");
	$tasks = db_fetch_all_columns($r);
	foreach ($tasks as $tid)
	{
		echo "<h3>Zadanie $tid.</h3>";
		$r = db_query("SELECT * FROM table_task_solutions
			WHERE wid=$wid AND tid=$tid AND uid=$uid ORDER BY submitted DESC LIMIT 4");
		$sols = db_fetch_all($r);
		if (empty($sols))
			echo 'Brak rozwiązania.';
		else
		{
			$sol = array_shift($sols);
			$inputs = array(
				array('type'=>'text', 'name'=>'submitted', 'description'=>'nadesłane', 'readonly'=>true,
					'default'=>strftime("%F %T", $sol['submitted'])),
				array('type'=>'select', 'name'=>'status', 'description'=>'status',
					'options'=>$solutionStatuses),
				array('type'=>'text', 'name'=>'grade', 'description'=>'ocena'),
				array('type'=>'textarea', 'name'=>'feedback', 'description'=>'komentarz'),
			);
			
			echo '<div class="descriptionBox">';
			echo parseUserHTML($sol['solution']);
			echo '</div>'
			?>
			<form method="post" action="?action=editSolutionsGradeForm&amp;<?php
					echo "wid=$wid&amp;uid=$uid&amp;tid=$tid&amp;submitted=". $sol['submitted'];
				?>">
				<table><?php generateFormRows($inputs, $sol); ?></table>
				<input type="submit" value="zapisz" />
			</form><br/>
			<?php
	
			if (!empty($sols))
			{
				// We could write (instead of display:none)
				// $PAGE->jsOnLoad .= '$("#task'. $tid .'oldsols").hide();';
				// but it would make a flicker at page load. So now javascript-disabled 
				// browsers can't see old solutions.
				echo '<a href="javascript:$(\'#task'. $tid .'oldsols\').toggle(400);">starsze rozwiązania</a>';
				echo '<div id="task'.$tid.'oldsols" style="display:none">';
				foreach ($sols as $sol)
				{
					echo 'nadesłane: '. strftime("%F %T", $sol['submitted']) .'<br/>';
					//echo 'status: '. $solutionStatuses[$sol['status']] .'<br/>';
					if (!empty($sol['grade']))
						echo 'ocena: '. htmlspecialchars($sol['grade']) .'<br/>';
					echo '<div class="descriptionBox">';
					echo parseUserHTML($sol['solution']);
					echo '</div>';
				}
				echo '</div>';
			}
		}
		echo '<br/><br/>';
	}
	
	
	$PAGE->content .= $template->finish();
}

function actionShowTaskSolutionsForm()
{
	global $USER,$PAGE, $participantStatuses;
	$wid = intval($_GET['wid']);
	$uid = intval($_GET['uid']);
	
	checkUserCanEditTasks($wid);
		
	db_update('workshop_user', "WHERE wid=$wid AND uid=$uid", array(
		'participant' => $_POST['participant'],
		'points' => (strlen(trim($_POST['points']))==0) ? NULL : intval($_POST['points']),
		'admincomment' => $_POST['admincomment']
	));
	showMessage('Pomyślnie zapisano informacje ogólne o rozwiązaniach.', 'success');
	logUser('task qualify', $wid);
	actionShowTaskSolutions();
}

function actionEditSolutionsGradeForm()
{
	global $USER,$PAGE;
	$wid = intval($_GET['wid']);
	$uid = intval($_GET['uid']);
	$tid = intval($_GET['tid']);
	$submitted = intval($_GET['submitted']);
	
	checkUserCanEditTasks($wid);
	
	db_update('task_solutions', "WHERE wid=$wid AND uid=$uid AND tid=$tid AND submitted=$submitted",
		array(
			'status' => $_POST['status'],
			'grade' => $_POST['grade'],
			'feedback' => $_POST['feedback']
		)
	);
	showMessage("Pomyślnie zapisano informacje o zadaniu $tid.", 'success');
	actionShowTaskSolutions();
	logUser('task grade', $wid);	
}

function checkUserCanEditTasks($wid)
{
	$result = db_query("SELECT uid
				FROM table_workshop_user
				WHERE lecturer>0 AND wid=". $wid);
	$lecturers = db_fetch_all_columns($result);	
	if (!userCan('editTasks', $lecturers) || !userCan('editWorkshop', $lecturers))
		throw new PolicyException();
}
