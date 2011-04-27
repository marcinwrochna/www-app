<?php
/*
	warsztaty.php
	Included in common.php
*/
require_once('tasks.php');

function addWarsztatyMenuBox()
{
	global $PAGE;
	$PAGE->addMenuBox('Warsztaty', array(
		array('lista warsztatów',     'listPublicWorkshops', 'brick.png'      ),
		array('twoje warsztaty',      'listOwnWorkshops',    'brick-green.png'),
		array('zaproponuj warsztaty', 'createWorkshop',      'brick-add.png'  )
	));
}

function actionListPublicWorkshops()
{
	global $PAGE, $USER, $DB;
	if (!userCan('listPublicWorkshops'))  throw new PolicyException();
	$PAGE->title = 'Lista warsztatów';
	if (!assertProfileFilled())  return;
	
	// Wypisz liczbę godzin, na jakie się zapisał.
	$DB->query('SELECT SUM(w.duration)
		FROM table_workshops w, table_workshop_user wu
		WHERE w.status=$1 AND w.edition=$2 AND wu.wid=w.wid AND wu.uid=$3',
		enumBlockStatus('great')->id, getOption('currentEdition'), $USER['uid']);
	$sum = intval($DB->fetch());
	if ($sum)
	{
		$msg = "Zapisał". gender('e') ."ś się na $sum × 1,5 godzin warsztatów.";
		if ($sum>30)  $msg .= "<br/>Pamiętaj, że w sumie WWW ma około 36 × 1,5 godzin.";
		$PAGE->addMessage($msg, 'info');
	}
	
	// Listuj 'warsztaty' (a nie 'luźne') 'świetne' (a nie np. 'ujdzie').
	listWorkshops('Public', 
		'(type='.  enumBlockType('workshop')->id .' AND '.
		'status='. enumBlockStatus('great')->id .')',
		array('wid','lecturers','title','subject','duration','participants'));
}

function actionListOwnWorkshops()
{
	global $USER, $PAGE;
	if (!userCan('listOwnWorkshops'))  throw new PolicyException();
	$PAGE->title = 'Twoje warsztaty';
	$where = 'EXISTS (SELECT wu.uid FROM table_workshop_user wu WHERE wu.uid='. $USER['uid'].'
		AND wu.wid = w.wid AND wu.participant='. enumParticipantStatus('lecturer')->id .')';
	listWorkshops('Own', $where,
		array('wid','lecturers','title','type','subject','duration','status','participants'));
}

function actionListAllWorkshops()
{
	global $PAGE;
	if (!userCan('listAllWorkshops'))  throw new PolicyException();
	$PAGE->title = 'Wszystkie warsztaty';
	listWorkshops('All', '',
		array('wid','lecturers','title','type','subject','duration','status','participants'));
}

function listWorkshops($which, $where, $columns)
{
	global $USER, $DB, $PAGE;
	
	$cols = array(
		'wid'           => array('th'=>'#',          'order'=>'w.wid'),
		'lecturers'     => array('th'=>'prowadzący', 'order'=>'lecturers'),
		'title'         => array('th'=>'tytuł',      'order'=>'w.title'),
		'type'          => array('th'=>'rodzaj',     'order'=>'w.type'),
		'subject'       => array('th'=>'dziedziny',  'order'=>'w.domain_order'),
		'duration'      => array('th'=>'czas [1,5h]','order'=>'w.duration DESC'),
		'status'        => array('th'=>'status',     'order'=>'w.status'),
		'participants'  => array('th'=>'zapisy',     'order'=>'count_accepted DESC'),
	);
	
	$from = '';
	$whereClauses = array();
	$orderby = array();
	// ORDER BY
	$allowed = array();
	foreach ($cols as $col)  if ($col['order'] != 'lecturers')  $allowed[]= $col['order'];	
	if (!isset($_SESSION['workshopOrder']))  $_SESSION['workshopOrder'] = array();
	if (isset($_GET['order']) && $_GET['order'] =='lecturers')
	{
		$orderby[] = 'regexp_replace(u.name,\'.*\ ([^\ ]+)\',\'\\\\1\')';
		$from = ', table_users u';
		$whereClauses[] = 'EXISTS (SELECT * FROM table_workshop_user wu
			WHERE w.wid=wu.wid AND u.uid=wu.uid
				AND participant='. enumParticipantStatus('lecturer')->id .')';
		$PAGE->addMessage('Przy sortowaniu po prowadzących wspólne warsztaty pokazywane są wielokrotnie.', 'info');
	}
	else if (isset($_GET['order']))
		$_SESSION['workshopOrder'][]= $_GET['order'];			
	while (count($_SESSION['workshopOrder'])>3)
		array_shift($_SESSION['workshopOrder']);
	
	$orderClauses = array_reverse($_SESSION['workshopOrder']);
	$orderClauses[]= 'w.title';
	foreach ($orderClauses as $o)
		if (in_array($o, $allowed, true) && !in_array($o, $orderby))
			$orderby[]= $o;
	$orderby = implode(',', $orderby);
	
	// WHERE
	if (!empty($where))  $whereClauses[]= $where;	
	$whereClauses[]= "w.edition=". intval(getOption('currentEdition'));
	if (isset($_GET['subject']) && enumSubject()->exists($_GET['subject']))
		$whereClauses[]= 'EXISTS (SELECT * FROM table_workshop_domain wd
			WHERE wd.wid=w.wid AND wd.domain=\''. $_GET['subject'] .'\')';
	if (!empty($whereClauses))  $where = 'WHERE '. implode(' AND ', $whereClauses);
	else $where = '';
	
	$selectParticipants = '';
	foreach(enumParticipantStatus() as $statusName => $status)
		$selectParticipants .= '(SELECT COUNT(*) FROM table_workshop_user wu
			WHERE wu.wid=w.wid AND participant='. $status->id .') AS count_'. $statusName .',';
	//$selectParticipants .=  '(SELECT COUNT(*) FROM table_workshop_user wu
	//	WHERE wu.wid=w.wid) AS count,';
		
	
	$workshops = $DB->query('
		SELECT w.wid, w.title, w.status, w.type, w.duration, w.link,
			'. $selectParticipants .'
			(SELECT participant FROM table_workshop_user wu
			 WHERE wu.wid=w.wid AND wu.uid=$1) AS participant
		FROM table_workshops w'. $from .'
		'. $where .'
		ORDER BY w.edition DESC, '. $orderby, $USER['uid']);
	
	$PAGE->headerTitle = "<h2><a href='list${which}Workshops'>". $PAGE->title . "</a></h2>";
	$template = new SimpleTemplate();
		echo "<table class='workshopList'>";
		echo "<thead>";
		foreach ($columns as $c)
		{
			$th = $cols[$c]['th'];
			$order = htmlspecialchars(urlencode($cols[$c]['order']), ENT_QUOTES);
			echo "<th><a href='list${which}Workshops?order=$order'>$th</a></th>";
		}
		echo "</thead>";
		foreach ($workshops as $row)
		{
			$status = enumBlockStatus(intval($row['status']));
			$row['status'] = userCan('changeWorkshopStatus') ? $status->decision : $status->status;
			$row['status'] = str_replace(' ','&nbsp;', $row['status']);
			
			
			$row['lecturers'] = array();
			foreach (getLecturers($row['wid']) as $lecturer)		
				$row['lecturers'][]= getUserBadge($lecturer);
			$row['lecturers'] = implode(',<br/>', $row['lecturers']);
			
			$row['type'] = enumBlockType(intval($row['type']))->short;
			
			$subjects = $DB->query('
				SELECT domain FROM table_workshop_domain
				WHERE wid=$1 ORDER BY domain', $row['wid'])->fetch_column();
			$row['subject'] = '';
			foreach ($subjects as $subject)
			{
				$s = enumSubject($subject);
				$row['subject'] .= getIcon('subject-'. $s->icon .'.png', $s->description,
				 "list${which}Workshops?subject=$subject");
			}
			
			$row['title'] = "<a href='showWorkshop(${row['wid']})'>${row['title']}</a>";
			
			$row['participants'] = '';
			if (userCan('showWorkshopParticipants', getLecturers($row['wid'])))
			{
				$tip = '';
				foreach(enumParticipantStatus() as $statusName => $status)
					$tip .= str_replace('%','ych',$status->description) .': '. $row["count_$statusName"] .'<br/>';
				$row['participants'] .= '<a '. getTipJS($tip) .'>';
				$row['participants'] .= ($row['count_accepted'] + $row['count_autoaccepted']) .'</a>';
			}
			
			$participant = enumParticipantStatus(intval($row['participant']));
			if (isset($participant->icon))
			{
				$description = str_replace('% ', gender().' ', $participant->explanation);
				$description = str_replace('%ś', gender('eś','aś'), $description);
				$row['participants'] .= ' '. getIcon($participant->icon, $description);
			}
					
			$class = alternate('even', 'odd');
			echo "<tr class='$class'>";
			
			foreach ($columns as $c)
				echo "<td>${row[$c]}</td>";			
			echo "</tr>";
		}
		echo "</table>";
	$PAGE->content .= $template->finish();
}

function getLecturers($wid)
{
	global $DB;
	$lecturers = $DB->query('SELECT uid FROM table_workshop_user
		WHERE participant=$1 AND wid=$2',
		enumParticipantStatus('lecturer')->id, $wid);
	return $lecturers->fetch_column();
}

function actionShowWorkshop($wid = null)
{
	global $USER, $DB, $PAGE;
	if (is_null($wid))  throw new KnownException('Nie podano numeru bloku warsztatowego.');
	$wid = intval($wid);
	
	$data = $DB->workshops[$wid]->assoc('*');
	$data['title'] = htmlspecialchars($data['title']);
	$data['type'] = ucfirst(enumBlockType(intval($data['type']))->description);
	$data['description'] = parseUserHTML($data['description']);	
	
	$participant = $DB->workshop_user[array('wid'=>$wid, 'uid'=>$USER['uid'])]->get('participant');
	$participant = intval($participant);
	
	$data['by'] = array();		
	$lecturers = getLecturers($wid);
	$displayEmail = $participant || userCan('editWorkshop', $lecturers);
	foreach ($lecturers as $lecturer)
		$data['by'][]= getUserBadge($lecturer, $displayEmail);	
	if (count($data['by']) == 1)
		$data['by'] =  'Prowadzi: '. $data['by'][0];
	else
		$data['by'] =  'Prowadzą: '. implode(', ', $data['by']);
	
	if (!userCan('showWorkshop', $lecturers))  throw new PolicyException();	

	if (empty($data['link']))
		$data['link'] = 'http://warsztatywww.wikidot.com/www'. intval(getOption('currentEdition')).
			':'. urlencode($data['title']);
		
	$subjects = $DB->query('SELECT domain FROM table_workshop_domain WHERE wid=$1', $wid);
	$data['subjects'] = array();
	foreach($subjects as $subject)
		$data['subjects'][]= enumSubject($subject['domain'])->description;
	$data['subjects'] = implode(', ', $data['subjects']);
			
	$PAGE->title = $data['title'] .' - opis';
	$PAGE->headerTitle = '';
	$template = new SimpleTemplate($data);
	?>		
		<span class='left'>%wid%.&nbsp;</span><h2>%title% <div class='tabs'>
			<a class='selected'>opis</a>
			<a href="showWorkshopTasks(%wid%)">zapisy i zadania</a>
		</div></h2>
		%type%: %subjects%.<br/>
		%by%<br/>
		Czas: %duration% × 1,5 godz.<br/>
		<a href="%link%" title="opis warsztatów na wikidot">zobacz opis na wikidot</a><br/>
		<br/>		
	<?php
	
	// Przycisk zapisz/wypisz się (taki sam w showWorkshopTasks).
	if (enumParticipantStatus($participant)->inArray(array('candidate', 'autoaccepted')))
		echo getButton('wypisz się', "resignFromWorkshop($wid)", 'cart-remove.png')  .'<br/>';
	else if (!$participant && userCan('signUpForWorkshop', $lecturers))
		echo getButton('zapisz się', "signUpForWorkshop($wid)", 'cart-put.png') .'<br/>';
	echo '<br/>';
		
	// Opis propozycji.
	if (userCan('showWorkshopDetails', $lecturers)) {
		echo 'Opis propozycji: <div class="descriptionBox">%description%</div>';
	}
	// Status przyjęcia warsztatów.
	if (userCan('changeWorkshopStatus', $lecturers))
	{
		echo 'Status:';
		$options = array();
		foreach (enumBlockStatus() as $statusName => $statusItem)
			$options[$statusName] = $statusItem->decision;
		$inputs = array(array('select', 'status', '', 'options'=>$options));
		$form = new Form($inputs, "changeWorkshopStatus($wid)");
		$form->values['status'] = enumBlockStatus(intval($data['status']))->key;
		$form->submitValue = 'Zapisz';
		echo $form->getHTML();
		echo '<br/>';		
	}
	else if (userCan('showWorkshopDetails', $lecturers)) {
		 echo 'Status: '. enumBlockStatus($data['status'])->status .'<br/><br/>';
	}	
	// Przycisk edycji warsztatów.
	if (userCan('editWorkshop', $lecturers))
		echo getButton('edytuj warsztaty', "editWorkshop($wid)", 'brick-edit.png');
	$PAGE->content .= $template->finish();
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
	$participant = $DB->workshop_user[array('wid'=>$wid, 'uid'=>$USER['uid'])]->get('participant');
	$participant = intval($participant);
	
	$PAGE->title = $data['title'] .' - zapisy i zadania';
	$PAGE->headerTitle = '';
	$template = new SimpleTemplate($data);
	?>		
		<span class='left'>%wid%.&nbsp;</span><h2>%title% <div class='tabs'>
			<a href='showWorkshop(%wid%)'>opis</a>
			<a class='selected'>zapisy i zadania</a>
		</div></h2>
	<?php
	
	$description = str_replace('% ', gender().' ', enumParticipantStatus($participant)->explanation);
	$description = str_replace('%ś', gender('eś','aś'), $description);
	echo 'Twój status: <i>'. $description .'</i><br/>';
	
	// Przycisk zapisz/wypisz się (taki sam w showWorkshop).
	if (enumParticipantStatus($participant)->inArray(array('candidate', 'autoaccepted')))
		echo getButton('wypisz się', "resignFromWorkshop($wid)", 'cart-remove.png')  .'<br/>';
	else if (!$participant && userCan('signUpForWorkshop', $lecturers))
		echo getButton('zapisz się', "signUpForWorkshop($wid)", 'cart-put.png') .'<br/>';
	echo '<br/>';
	
	// Lista zapisanych uczestników.
	if (userCan('showWorkshopParticipants', $lecturers))	
		echo buildParticipantList($wid) . '<br/>';
		
	// Lista zadań kwalifikacyjnych.
	echo buildTaskList($wid);
	
	$PAGE->content .= $template->finish();
}

function actionChangeWorkshopStatus($wid)
{
	global $DB, $PAGE;
	$wid = intval($wid);
	if (!userCan('changeWorkshopStatus'))  throw new PolicyException();
	$status = enumBlockStatus($_POST['status'])->id;
	$DB->workshops[$wid]->update(array('status'=>$status));
	$PAGE->addMessage('Pomyślnie zmieniono status bloku warsztatowego.');
	logUser('workshop status chg', $wid); 
	actionShowWorkshop($wid);
}

function actionCreateWorkshop()
{
	actionEditWorkshop(-1);
}

function actionEditWorkshop($wid)
{
	if (!assertProfileFilled())  return;
	global $USER, $PAGE, $DB;	
	$wid = intval($wid);
	$new = ($wid==-1);	
	
	if ($new)
	{
		showMessage('Minął termin zgłaszania propozycji, wypełniasz formularz na własną odpowiedzialność.', 'warning');
		
		if (!userCan('createWorkshop'))  throw new PolicyException();
		$data = array(
			'wid' => -1,
			'proposer_uid' => $USER['uid'],
			'title' => '',
			'description' => '',
			'status' => 'new',
			'materials' => '',
			'subjects' => array(),
			'type' => 'workshop',
			'duration' => 3*2, //3*3*60min
			'duration_min' => 90,
			'link' => 'http://warsztatywww.wikidot.com/www'. getOption('currentEdition') .':tytul-warsztatow'
		);
		$lecturers = array($USER['uid']);
	}
	else
	{
		$data = $DB->workshops[$wid]->assoc('*');
		$lecturers = getLecturers($wid);
		if (!userCan('editWorkshop', $lecturers))  throw new PolicyException();
		//$data['status'] = enumBlockStatus($
		$data['type'] = enumBlockType(intval($data['type']))->key;
		$data['subjects'] = $DB->query('SELECT domain FROM table_workshop_domain WHERE wid=$1', $wid);
		$data['subjects'] = $data['subjects']->fetch_column();
	}
	
	foreach ($lecturers as $lecturer)
		$data['lecturers'][]= getUserBadge($lecturer, $displayEmail);	
	$data['lecturers'] =  implode(', ', $data['lecturers']);
	$lecturersDescription = (count($lecturers) > 1) ? 'Prowadzą' : 'Prowadzi';
	if ($new)
		$data['lecturers'] .= ' <small class="right">Więcej prowadzących będzie można dodać później edytując warsztaty.</small>';
	else 
		$lecturersDescription .= " <a href='editWorkshopLecturers($wid)'>[zmień]</a>";
	
	$comment = 'Poruszana tematyka, trudność, zakres materiału, na czym będą polegać (zadania teoretyczne, kodowanie,
		przeprowadzanie doświadczeń). Zachęcamy do wysyłania możliwie wyczerpujących opisów.';
	$comment = "<br/><small>$comment</small>";
	
	$subjectOptions = array();
	foreach (enumSubject() as $subjectName => $subject)	
		$subjectOptions[$subjectName] = $subject->description;
	
	$durationOptions = array(6=>'9h (6x1.5h)', 9=>'13.5h (9x1.5h)');
	
	if ($data['type'] != 'workshop')
	{
		$PAGE->jsOnLoad .= '$("#row_duration").hide();';
		$PAGE->jsOnLoad .= '$("#row_duration_other").hide();';
	}
	if (in_array($data['duration'], array_keys($durationOptions)))
		$PAGE->jsOnLoad .= '$("#row_duration_other").hide();';
	
	$typeOptions = array();
	foreach (enumBlockType() as $typeName => $type)
		$typeOptions[$typeName] = $type->description;
	
	$inputs = array(
		array('text',          'title',       'Tytuł'                                     ),
		array('readonly',      'lecturers',   $lecturersDescription                       ),
		array('richtextarea',  'description', 'Opis'. $comment                            ),
		array('checkboxgroup', 'subjects',    'Dziedzina',    'options' => $subjectOptions),
		array('select',        'type',        'Rodzaj',       'options' => $typeOptions,
		                     'properties'=>'onchange="$(\'#row_duration\').toggle(this.selectedIndex=='. enumBlockType('workshop')->id .');"'),
		array('select',        'duration',    'Czas trwania', 'options' => $durationOptions, 'other' => '[x1,5h]<span class="left">(niestandardowy czas należy<br/> skonsultować z organizatorami)</span>'),
		array('text',          'link',        'Link do opisu na wikidot'                  ),
	);		
	
	$PAGE->title = $new ? 'Dodaj warsztaty' : 'Edytuj warsztaty';
	if ((!$new) && userCan('showWorkshop', $lecturers))
		$PAGE->content .= "<a class='back' href='showWorkshop($wid)'>zobacz</a>";
	$form = new Form($inputs, "editWorkshopForm($wid)");
	$form->values = $data;
	$form->submitValue = 'Zapisz';
	$PAGE->content .= $form->getHTML(); 
	// <form name="form" ...
}


function actionEditWorkshopForm($wid)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);
	$new = ($wid==-1);	
	
	$data = array(
		'title' => $_POST['title'],
		'description' => $_POST['description'],
		'type' => enumBlockType($_POST['type'])->id,
		'duration' => ($_POST['type'] == 'lightLecture') ? 1 : intval($_POST['duration']),
		'link' => trim($_POST['link']),
		'domain_order' => empty($_POST['subjects']) ? 0 : subjectOrder($_POST['subjects'])
	);
	
	if ($data['duration'] == VALUE_OTHER) 
		$data['duration'] = intval($_POST['duration_other']);
	
	if (empty($data['link']))  $data['link'] = null;
	else if (strpos($data['link'], 'http://') !== 0  &&  strpos($data['link'], 'https://') !== 0)
		$data['link'] = 'http://'. $data['link'];		
	
	if ($new)
	{
		if (!userCan('createWorkshop'))  throw new PolicyException();

		$data = $data + array(
			'edition' => intval(getOption('currentEdition')),
			'status' => enumBlockStatus('new')->id			
		);
		$DB->workshops[] = $data;
		$wid = $DB->workshops->lastValue();
		$DB->workshop_user[]= array('wid'=>$wid, 'uid'=>$USER['uid'],
			'participant'=>enumParticipantStatus('lecturer')->id);
		if (!empty($_POST['subjects']))
			foreach ($_POST['subjects'] as $d)
				$DB->workshop_domain[]= array('wid'=>$wid, 'domain'=>$d, 'level'=>1);
		$PAGE->addMessage('Pomyślnie dodano propozycję warsztatów. Będzie widoczna na liście po wstępnym zaakceptowaniu.', 'success');
		logUser('workshop new', $wid);
		actionEditWorkshop($wid);
	}
	else
	{
		if (!userCan('editWorkshop', getLecturers($wid)))  throw new PolicyException();
			
		$DB->workshops[$wid]->update($data);
		$DB->query('DELETE FROM table_workshop_domain WHERE wid=$1', $wid);
		if (!empty($_POST['subjects']))
			foreach ($_POST['subjects'] as $d)
				$DB->workshop_domain[]= array('wid'=>$wid, 'domain'=>$d, 'level'=>1);
		$PAGE->addMessage('Pomyślnie zmieniono warsztaty', 'success');
		logUser('workshop edit', $wid);
		actionEditWorkshop($wid);
	}
}

function actionEditWorkshopLecturers($wid)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);
	$lecturers = getLecturers($wid);
	if (!userCan('editWorkshop', $lecturers))  throw new PolicyException();
	
	
	
	foreach ($lecturers as $lecturer)
		$inputs[]= 
			array('custom', 'uid'. $lecturer, getUserBadge($lecturer, $displayEmail),
				'custom'=>"<a href='removeWorkshopLecturer($wid;$lecturer)'>[usuń]</a>");	
	//$data['lecturers'] =  implode(', ', $data['lecturers']);
	$autoCompleteData = $DB->query('
		SELECT name FROM table_users u
		WHERE EXISTS (SELECT * FROM table_user_roles r WHERE r.uid=u.uid AND r.role=$1)
		ORDER BY name', 'kadra')->fetch_column();
	//throw new KnownException(json_encode($autoCompleteData));
	$inputs[]=
		array('text', 'lecturer', 'Imię Nazwisko lub uid', 'autocomplete' => $autoCompleteData);
	
	$PAGE->title = 'Dodaj/usuń prowadzących';
	$PAGE->content .= "<a class='back' href='editWorkshop($wid)'>wróć</a>";
	$PAGE->content .= '<h3>'. $DB->workshops($wid)->get('title') .'</h3>';
	$form = new Form($inputs, "addWorkshopLecturer($wid)");
	$form->submitValue = 'Dodaj';
	$PAGE->content .= $form->getHTML(); 
}

function actionRemoveWorkshopLecturer($wid, $uid, $confirm = false)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);	
	$uid = intval($uid);
	$lecturers = getLecturers($wid);
	if (!userCan('editWorkshop', $lecturers))  throw new PolicyException();
	
	if (!in_array($uid, $lecturers))
		$PAGE->addMessage('Wskazany użytkownik nie należy do prowadzących.', 'userError');
	else if (($uid == $USER['uid']) && !$confirm)
	{
		$PAGE->addMessage('Czy na pewno chcesz usunąć samego siebie z prowadzących?<br/>'.
			"<a href='removeWorkshopLecturer($wid;$uid;1)' class='button'>tak</a> ".
			"<a href='editWorkshopLecturers($wid)' class='button'>anuluj</a>",
			'warning');
	}
	else
	{
		$DB->query('DELETE FROM table_workshop_user WHERE wid=$1 AND uid=$2', $wid, $uid);
		$PAGE->addMessage('Pomyślnie usunięto użytkownika z prowadzących.', 'success');
	}
	actionEditWorkshopLecturers($wid);
}

function actionAddWorkshopLecturer($wid, $confirm = false)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);
	$lecturers = getLecturers($wid);
	if (!userCan('editWorkshop', $lecturers))  throw new PolicyException();
	
	// lecturer usually passed in POST, passed here if confirming.
	$lecturer = ($confirm === false) ? $_POST['lecturer'] : $confirm;
	if (is_numeric($lecturer))
	{
		$uid = intval($lecturer);
		$cnt = $DB->query('SELECT count(*) FROM table_users WHERE uid=$1', $uid)->fetch();
		if ($cnt != 1)  throw new KnownException('Niepoprawny uid.');
	}
	else
	{
		$uid = $DB->query('SELECT uid FROM table_users WHERE name=$1', trim($lecturer))->fetch();
		if ($uid === null)
		{
			$names = $DB->query('
				SELECT uid, name FROM table_users u
				WHERE EXISTS (SELECT * FROM table_user_roles r WHERE r.uid=u.uid AND r.role=$1)
				ORDER BY name', 'kadra')->fetch_all();		
			$best = 100000;
			$bestname = '';
			foreach($names as $name)
				if (levenshtein($name['name'], $lecturer) < $best)
				{
					$best = levenshtein($name['name'], $lecturer);
					$uid = $name['uid'];					
					$bestname = $name['name'];
				}
			$PAGE->addMessage("Niedokładnie dopasowano użytkownika #$uid <i>$bestname</i>", 'info');
		}
	}
	
	if (in_array($uid, $lecturers))
		$PAGE->addMessage('Wskazany użytkownik już jest prowadzącym.', 'userError');
	else
	{
		$DB->query('SELECT count(*) FROM table_workshop_user WHERE wid=$1 AND uid=$2', $wid, $uid);
		if ($DB->fetch() && ($confirm === false))
		{
			$PAGE->addMessage('Użytkownik jest już zapisany jako uczestnik. Wypisać i dodać do prowadzących?<br/>'.
				"<a href='addWorkshopLecturer($wid;$uid)' class='button'>tak</a> ".
				"<a href='editWorkshopLecturers($wid)' class='button'>anuluj</a>",
				'warning');
		}
		else
		{
			if ($confirm !== false)
				$DB->query('DELETE FROM table_workshop_user WHERE wid=$1 AND uid=$2', $wid, $uid);
			$DB->workshop_user[]= array('uid'=>$uid, 'wid'=>$wid,
				'participant'=>enumParticipantStatus('lecturer')->id);
			$PAGE->addMessage('Pomyślnie dodano użytkownika do prowadzących.', 'success');
		}
	}
	actionEditWorkshopLecturers($wid);
}


function actionSignUpForWorkshop($wid)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);
	if (!userCan('signUpForWorkshop'))  throw new PolicyException();
	
	$participant = 'candidate';
	if (userCan('autoQualifyForWorkshop'))  $participant = 'autoaccepted';
	$participant = enumParticipantStatus($participant)->id;
	
	$dbRow = $DB->workshop_user[array('wid'=>$wid,'uid'=>$USER['uid'])];
	if (!$dbRow->count())
		$DB->workshop_user[] = array('wid'=>$wid,'uid'=>$USER['uid'], 'participant'=>$participant);
	else if ($dbRow->get('participant') != enumParticipantStatus('none')->id)
		throw new KnownException('Nie możesz zmienić swojego statusu.');
	$dbRow->update(array('participant'=>$participant));
	
	$PAGE->addMessage("Pomyślnie zapisano na warsztaty #$wid.", 'success');
	actionShowWorkshop($wid);
	logUser('workshop participant signup', $wid); 
}

function actionResignFromWorkshop($wid)
{
	global $USER, $DB, $PAGE;
	$wid = intval($wid);
	$dbRow = $DB->workshop_user[array('wid'=>$wid,'uid'=>$USER['uid'])];
	$participant = enumParticipantStatus(intval($dbRow->get('participant')));
	if (!$participant->canResign)
		throw new KnownException('Nie możesz zrezygnować ze swojego statusu: '. $participant->description);
	$dbRow->delete();
	$PAGE->addMessage("Pomyślnie wypisano z warsztatów #$wid.", 'success');
	actionShowWorkshop($wid);
	logUser('workshop participant resign', $wid); 
}

function subjectOrder($subjects)
{
	$r = 0;
	foreach(enumSubject() as $subjectName=>$subject)
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
		$domains = $DB->query('SELECT domain FROM table_workshop_domain WHERE wid=$1', $wid);
		$order = subjectOrder($domains->fetch_column());
		$DB->workshops[$wid]->update(array('domain_order'=>$order));
	}
}
