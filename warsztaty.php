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
	$sum = $DB->query('SELECT SUM(w.duration)
		FROM table_workshops w, table_workshop_user wu
		WHERE w.status=4 AND wu.wid=w.wid AND wu.uid=$1', $USER['uid'])->fetch();
	if ($sum)
	{
		$msg = "Zapisał". gender('e') ."ś się na $sum × 1,5 godzin warsztatów.";
		if ($sum>30)  $msg .= "<br/>Pamiętaj, że w sumie WWW ma około 36 × 1,5 godzin.";
		$PAGE->addMessage($msg, 'info');
	}
	
	// Listuj 'warsztaty' (a nie 'luźne') 'świetne' (a nie np. 'ujdzie').
	listWorkshops('Public', '(type=1 AND status=4)',
		array('wid','lecturers','title','domains','duration','participants'));
}

function actionListOwnWorkshops()
{
	global $USER, $PAGE;
	if (!userCan('listOwnWorkshops'))  throw new PolicyException();
	$PAGE->title = 'Twoje warsztaty';
	$where = 'EXISTS (SELECT wu.uid FROM table_workshop_user wu WHERE wu.uid='. $USER['uid'].'
		AND wu.wid = w.wid AND wu.lecturer>0)';
	listWorkshops('Own', $where,
		array('wid','lecturers','title','type','domains','duration','status','participants'));
}

function actionListAllWorkshops()
{
	global $PAGE;
	if (!userCan('listAllWorkshops'))  throw new PolicyException();
	$PAGE->title = 'Wszystkie warsztaty';
	listWorkshops('All', '',
		array('wid','lecturers','title','type','domains','duration','status','participants'));
}

function listWorkshops($which, $where, $columns)
{
	global $USER, $DB;
	
	$cols = array(
		'wid'           => array('th'=>'#',          'order'=>'w.wid'),
		'lecturers'     => array('th'=>'prowadzący', 'order'=>'regexp_replace(u.name,\'.*\ ([^\ ]+)\',\'\\\\1\')'),
		'title'         => array('th'=>'tytuł',      'order'=>'w.title'),
		'type'          => array('th'=>'rodzaj',     'order'=>'w.type'),
		'domains'       => array('th'=>'dziedziny',  'order'=>'w.domain_order'),
		'duration'      => array('th'=>'czas [1,5h]','order'=>'w.duration DESC'),
		'status'        => array('th'=>'status',     'order'=>'w.status'),
		'participants'  => array('th'=>'zapisy',     'order'=>'count DESC'),
	);
	
	// ORDER BY
	$allowed = array();
	foreach ($cols as $col)  $allowed[]= $col['order'];	
	if (!isset($_SESSION['workshopOrder']))  $_SESSION['workshopOrder'] = array();
	if (isset($_GET['order']))  $_SESSION['workshopOrder'][]= $_GET['order'];
	while (count($_SESSION['workshopOrder'])>3)
		array_shift($_SESSION['workshopOrder']);
	$orderby = 'w.title';
	foreach ($_SESSION['workshopOrder'] as $o)
		if (in_array($o, $allowed, true))
			$orderby = "$o,$orderby";
	
	// WHERE
	if (!empty($where))  $where = "AND $where";	
	if (isset($_GET['domain']) && in_array($_GET['domain'], explode(',', getOption('domains'))))
		$where .= ' AND EXISTS (SELECT * FROM table_workshop_domain wd
			WHERE wd.wid=w.wid AND wd.domain=\''. $_GET['domain'] .'\')';
	
	$workshops = $DB->query('
		SELECT w.wid, w.proposer_uid, w.title, w.status, w.type, w.duration, w.link,
			(SELECT COUNT(*) FROM table_workshop_user wu
			 WHERE wu.wid=w.wid AND participant>0) AS count,
			(SELECT participant FROM table_workshop_user wu
			 WHERE wu.wid=w.wid AND wu.uid=$1) AS participant
		FROM table_workshops w, table_users u
		WHERE u.uid=w.proposer_uid '. $where .'
		ORDER BY '. $orderby, $USER['uid']);
	
	global $PAGE;
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
		$class = 'even';
		foreach ($workshops as $row)
		{
			$row['status'] = describeWorkshopStatus($row['status']);
			$row['status'] = str_replace(' ','&nbsp;', $row['status']);
			
			$lecturers = $DB->query('
				SELECT uid
				FROM table_workshop_user
				WHERE lecturer>0 AND wid=$1', $row['wid']);
			$row['lecturers'] = array();
			foreach ($lecturers as $lecturer)		
				$row['lecturers'][]= getUserBadge($lecturer['uid']);
			$row['lecturers'] = implode(',<br/>', $row['lecturers']);
			
			$row['type'] = workshopTypeToString($row['type']);
			$domains = $DB->query('
				SELECT domain FROM table_workshop_domain
				WHERE wid=$1 ORDER BY domain', $row['wid'])->fetch_column();
			$row['domains'] = '';
			$icon = array('matematyka' => 'm', 'fizyka' => 'f', 'astronomia' => 'a',
				'informatyka teoretyczna' => 'it', 'informatyka praktyczna' => 'ip');
			$href = "list${which}Workshops?domain=";
			foreach ($domains as $d)
				$row['domains'] .= getIcon('subject-'. $icon[$d] .'.png', $d, $href . $d);
			
			$row['title'] = "<a href='showWorkshop(${row['wid']})'>${row['title']}</a>";
			
			$row['participants'] = '';
			if (userCan('showWorkshopParticipants', $lecturers))
				$row['participants'] .= $row['count'];			
			switch ($row['participant'])
			{
				case 1:
					$tip = 'Jesteś zapisan'. gender() .' (wstępnie; pamiętaj o zadaniach kwalifikacyjnych).';
					$row['participants'] .= ' '. getIcon('tick-yellow.png', $tip, null);
					break;
				case 2:
					$tip = 'Nie spełnił'. gender('e','a') .'ś wymagań.';
					$row['participants'] .= ' '. getIcon('cross.png', $tip, null);				
					break;
				case 3:
					$tip = 'Zakwalifikował'. gender('e','a'). 'ś się.';
					$row['participants'] .= ' '. getIcon('tick.png', $tip, null);				
					break;
				case 4:
					$tip = 'Jesteś zapisan'. gender('y','a'). ' (zakwalifikowany jako kadra)';
					$row['participants'] .= ' '. getIcon('tick.png', $tip, null);				
					break;
			}			
						
			echo "<tr class='$class'>";
			$class = ($class=='even')?'odd':'even';
			foreach ($columns as $c)
				echo "<td style='text-align:left'>${row[$c]}</td>";			
			echo "</tr>";
		}
		echo "</table>";
	$PAGE->content .= $template->finish();
}

function workshopTypeToString($type)
{
	switch ($type)
	{
		case 0: return 'luźny';
		case 1: return 'warsztaty';
		default: return '';
	}
}

function actionShowWorkshop($wid = null)
{
	global $USER, $DB, $PAGE;
	if (is_null($wid))  throw new KnownException('Nie podano numeru bloku warsztatowego.');
	$wid = intval($wid);
	
	$data = $DB->workshops[$wid]->assoc('*');
	$data['title'] = htmlspecialchars($data['title']);
	$data['type'] = workshopTypeToString($data['type']);
	$data['description'] = parseUserHTML($data['description']);	
	$data['proposer'] = getUserBadge($data['proposer_uid']);
	
	$participant = $DB->workshop_user[array('wid'=>$wid, 'uid'=>$USER['uid'])]->get('participant');
	
	$lecturers = $DB->query('SELECT uid FROM table_workshop_user WHERE lecturer>0 AND wid=$1', $wid);
	$lecturers = $lecturers->fetch_column();
	$data['by'] = array();		
	$displayEmail = $participant || userCan('editWorkshop', $lecturers);
	foreach ($lecturers as $lecturer)
		$data['by'][]= getUserBadge($lecturer, $displayEmail);	
	$data['by'] =  implode(', ', $data['by']);
	
	if (!userCan('showWorkshop', $lecturers))  throw new PolicyException();	
	
	if (empty($data['link']))
		$data['link'] = 'http://warsztatywww.wikidot.com/www6:'. urlencode($data['title']);
		
	$domains = $DB->query('SELECT domain FROM table_workshop_domain WHERE wid=$1', $wid);
	$data['domains'] = implode(', ', $domains->fetch_column());
			
	$PAGE->title = $data['title'] .' - opis';
	$PAGE->headerTitle = '';
	$template = new SimpleTemplate($data);
	?>		
		<span class='left'>%wid%.&nbsp;</span><h2>%title%</h2>
		(%type%: %domains%)<br/>
		by %by%<br/>
		%duration% × 1,5 godz.<br/>
		<a href="%link%" title="opis warsztatów na wikidot">zobacz opis</a><br/>
		<br/>		
	<?php
	
	// Lista zapisanych uczestników.
	if (userCan('showWorkshopParticipants', $lecturers))	
		echo buildParticipantList($wid);
	// Przycisk zapisz/wypisz się.
	if ($participant)
		echo getButton('wypisz się', '?action=resignFromWorkshop&wid='. $wid, 'cart-remove.png')  .'<br/>';
	else if (userCan('signUpForWorkshop', $lecturers))
		echo getButton('zapisz się', '?action=signUpForWorkshop&wid='. $wid, 'cart-put.png') .'<br/>';
	echo '<br/><br/>';
		
	// Lista zadań kwalifikacyjnych.
	echo buildTaskList($wid, $participant);
		
	// Opis propozycji.
	if (userCan('showWorkshopDetails', $lecturers)) {
		echo '<hr/><br/>';
		echo 'Opis propozycji: <div class="descriptionBox">%description%</div>';
	}
	// Status przyjęcia warsztatów.
	if (userCan('changeWorkshopStatus', $lecturers))
	{
		echo 'Status:';
		echo '<form method="post" action="?action=changeWorkshopStatus&amp;wid=%wid%">';
		echo '<table>';
		$options = array();
		for ($i=MIN_WORKSHOP_STATUS; $i<=MAX_WORKSHOP_STATUS; $i++)
			$options[$i] = describeWorkshopStatus($i);
		buildFormRow('select', 'status', '', $data['status'], $options);
		echo '<tr><td></td><td>'.
			'<input type="submit" value="zapisz" class="submit"/>'.
			'</td></tr></table></form><br/>';
	}
	else if (userCan('showWorkshopDetails', $lecturers)) {
		 echo 'Status: '. describeWorkshopStatus($data['status']) .'<br/><br/>';
	}	
	// Przycisk edycji warsztatów.
	if (userCan('editWorkshop', $lecturers))
		echo getButton('edytuj warsztaty', '?action=editWorkshop&wid='. $wid, 'brick-edit.png');
	$PAGE->content .= $template->finish();
}

function actionChangeWorkshopStatus()
{
	if (!isset($_GET['wid']))  throw new KnownException('Nie podano numeru bloku warsztatowego.');
	$wid = intval($_GET['wid']);
	if (!userCan('changeWorkshopStatus'))  throw new PolicyException();
	db_update('workshops', 'WHERE wid='. $wid, array('status'=>$_POST['status']), 'Nie udało się zmienić statusu bloku.');
	showMessage('Pomyślnie zmieniono status bloku warsztatowego.');
	logUser('workshop status chg', $wid); 
	actionShowWorkshop();
}

define('MIN_WORKSHOP_STATUS', 0);
define('NEW_WORKSHOP_STATUS', 0);
define('MAX_WORKSHOP_STATUS', 4);
function describeWorkshopStatus($status)
{
	if (userCan('changeWorkshopStatus'))
	{
		switch($status)
		{
			case  0: return 'nowy';
			case  1: return 'prośba o szczegóły';
			case  2: return 'beznadziejne';
			case  3: return 'ujdzie';
			case  4: return 'świetne';
			default: return 'nieznany';
		}
	}
	else
	{
		switch($status)
		{
			case  0: return 'nie rozpatrzono';
			case  1: return 'prośba o szczegóły';
			case  2:
			case  3:
			case  4: return 'wstępnie rozpatrzono';
			default: return 'nieznany';
		}
	}
}

function actionCreateWorkshop()
{
	actionEditWorkshop();
}

function actionEditWorkshop()
{
	if (!assertProfileFilled())  return;
	global $USER, $PAGE;	
	if (isset($_POST['title']))  $wid = handleEditWorkshopForm();
	else $wid = isset($_GET['wid']) ? intval($_GET['wid']) : -1;
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
			'status' => NEW_WORKSHOP_STATUS,
			'materials' => '',
			'domains' => array(),
			'type' => 1,
			'duration' => 3*2, //3*3*60min
			'duration_min' => 90,
			'link' => 'http://warsztatywww.wikidot.com/www6:tytul-warsztatow'
		);
	}
	else
	{
		$result = db_query('SELECT * FROM table_workshops WHERE wid='. $wid);
		$data = db_fetch_assoc($result);
		$result = db_query('SELECT uid FROM table_workshop_user	WHERE lecturer>0 AND wid='. $wid);
		$lecturers = db_fetch_all_columns($result);	
		if (!userCan('editWorkshop', $lecturers))  throw new PolicyException();
		$result = db_query('SELECT domain FROM table_workshop_domain WHERE wid='. $wid, 'Nie udało się sprawdzić dziedziny warsztatów.');
		$data['domains'] = db_fetch_all_columns($result);
	}
	
	
	$comment = 'Poruszana tematyka, trudność, zakres materiału, na czym będą polegać (zadania teoretyczne, kodowanie,
		przeprowadzanie doświadczeń). Zachęcamy do wysyłania możliwie wyczerpujących opisów.';
	$comment = "<br/><small>$comment</small>";
	
	$domains = explode(',', getOption('domains'));
	$domainOptions = array();
	foreach ($domains as $d)  $domainOptions[$d] = $d;
	
	// Uwaga na nadchodzący syf.
	
	$durationOptions = array(0=>'ε', 1=>'1,5h', 2=>'3h', 3=>'4,5h', 4=>'6h', 5=>'7,5h', 6=>'9h',
		8=>'4*3h', 9=>'5*3h', 10=>'6*3h', 11=>'7*3h', 12=>'9*3h');
	//for ($i=1; $i<=9; $i++)  $durationOptions[strval($i*3*60)] = "$i*3h";
	
	$duration = isset($_POST['duration']) ? intval($_POST['duration']) : $data['duration'];
	$durationControl = '<input type="text" name="duration" value="'. $duration .'" size="3" id="durTxt"/>'.
		'<small id="durComment">razy 1,5h</small>'.
		'<input type="hidden" name="duration_hidden" value="'. $duration .'" id="durHid"/>';
	$durationControl =
		'<a href="javascript:durationChange(-1)" style="display:none;" class="smallButton" id="durm">-</a>'.
		$durationControl .
		'<a href="javascript:durationChange(+1)" style="display:none;" class="smallButton" id="durp">+</a>';		
	$PAGE->jsOnLoad .= '
		$("#durComment").hide();
		$("#durm").show();
		$("#durp").show();
		$("#durTxt").attr("disabled","disabled");
		$("#durTxt").attr("name","duration_string");
		$("#durTxt").attr("size", 7);
		$("#durTxt").addClass("light");
		$("#durHid").attr("name","duration");
		durationChange(0);
	';
	$PAGE->js .= '
		function durationChange(d) {
			descs = {0:"ε"};
			for (i=1;i<=9;i++)  descs[2*i] = (3*i)+"h";
			for (i=1;i<=9;i++)  descs[2*i-1] = (3*i-2)+"h 30min";
			v = parseInt($("#durHid").val());
			v+=d;
			if (!(v>=0 && v<=18))  v=6;
			$("#durHid").val(v);
			$("#durTxt").val(descs[v]);
			if (v>0)  $("#durm").show();
			else $("#durm").hide();
			if (v<18)  $("#durp").show();
			else $("#durp").hide();
		}
	';
	
	
	$PAGE->js .= '
		function formTypeChange(optionId) {
			if (optionId == 1) $("#row_duration").show();
			else $("#row_duration").hide();
		}
	';
	if ($data['type'] != 1)
	{
		$PAGE->jsOnLoad .= 'document.getElementById("row_duration").style.display = "none";';
		$PAGE->jsOnLoad .= 'document.getElementById("row_duration_other").style.display = "none";';
	}
	
	$inputs = array(
		array('tytuł', 'title', 'text'),
		array('opis'. $comment, 'description', 'richtextarea'),
		array('type'=>'checkboxgroup', 'name'=>'domains', 'description'=>'Dziedzina', 
			'options' => $domainOptions),
		array('type'=>'select', 'name'=>'type', 'description'=>'Rodzaj',
			'options' => array('Luźny wykład', 'Warsztaty'),
			'properties'=>'onChange="formTypeChange(this.selectedIndex)"'),
		array('type'=>'custom', 'name'=>'duration', 'description'=>'Czas trwania',
			'custom'=>$durationControl),
		array('type'=>'text', 'name'=>'link', 'description'=>'Link do opisu na wikidot'),
		/*array('type'=>'select', 'name'=>'duration', 'description'=>'Czas trwania',
			'options'=>$durationOptions, 'other'=>'<small>1,5h razy</small>'),*/
		//array('materiały do zajęć', 'materials', 'richtextarea')
	);		
	
	$PAGE->title = $new ? 'Dodaj warsztaty' : 'Edytuj warsztaty';
	$template = new SimpleTemplate(array('wid'=>$wid));
	
	if ($wid != -1 && userCan('showWorkshop', $data['proposer_uid']))	{ ?>
		<a class="back" href="?action=showWorkshop&amp;wid=%wid%">zobacz</a>
	<?php } ?>
	
	<h2><?php echo $PAGE->title; ?></h2>
	<form method="post" action="?action=editWorkshop&amp;wid=%wid%" name="form">
		<table><?php
			generateFormRows($inputs, $data);
		?></table>
		<input type="submit" value="zapisz" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}


function handleEditWorkshopForm()
{
	global $USER;
	$wid = isset($_GET['wid']) ? intval($_GET['wid']) : -1;
	$new = ($wid==-1);	
	
	if ($_POST['type'] == 0)  $duration = 1;
	else  $duration = intval($_POST['duration']);
	
	$link = $_POST['link'];
	if (empty($link))  $link = null;
	else if (strpos($link, 'http://')!== 0 && strpos($link, 'https://')!== 0)
		$link = 'http://'. $link;
	
	if ($new)
	{
		if (!userCan('createWorkshop'))  throw new PolicyException();

		if (!empty($_POST['domains']))
			$order = domainOrder($_POST['domains']);
		else
			$order = 0;

		$data = array(
			'proposer_uid' => $USER['uid'],
			'title' => $_POST['title'],
			'description' => $_POST['description'],
			'status' => NEW_WORKSHOP_STATUS,
			'type' => intval($_POST['type']),
			'duration' => $duration,
			'link' => '',
			'domain_order' => $order
			//'materials' => $_POST['materials'],
		);
		$wid = db_insert('workshops', $data, 'Nie udało się dodać warsztatów.', 'wid');
		if (!empty($_POST['domains']))
			foreach ($_POST['domains'] as $d)
				db_insert('workshop_domain', array('wid'=>$wid, 'domain'=>$d, 'level'=>1), 'Nie udało się dodać dziedziny do warsztatów');
			db_insert('workshop_user', array('wid'=>$wid, 'uid'=>$USER['uid'],
				'lecturer'=>1, 'participant'=>0));
		showMessage('Pomyślnie dodano propozycję warsztatów. Będzie widoczna na liście po wstępnym zaakceptowaniu.', 'success');
		logUser('workshop new', $wid);
		return $wid;
	}
	else
	{
		$result = db_query('SELECT uid FROM table_workshop_user	WHERE lecturer>0 AND wid='. $wid);
		$lecturers = db_fetch_all_columns($result);	
		if (!userCan('editWorkshop', $lecturers))  throw new PolicyException();
		
		if (!empty($_POST['domains']))
			$order = domainOrder($_POST['domains']);
		else
			$order = 0;
			
		$data = array(
			'title' => $_POST['title'],
			'description' => $_POST['description'],
			'type' => intval($_POST['type']),
			'duration' => $duration,
			'link' => $link,
			'domain_order' => $order
			//'materials' => $_POST['materials'],
		);
		db_update('workshops', 'WHERE wid='. $wid, $data, 'Nie udało się zmienić warsztatów.');
		db_query('DELETE FROM table_workshop_domain WHERE wid='. $wid, 'Nie udało się wyczyścić dziedziny warsztatów.');
		if (!empty($_POST['domains']))
			foreach ($_POST['domains'] as $d)
				db_insert('workshop_domain', array('wid'=>$wid, 'domain'=>$d, 'level'=>1), 'Nie udało się dodać dziedziny do warsztatów.');
		showMessage('Pomyślnie zmieniono warsztaty', 'success');
		logUser('workshop edit', $wid);
		return $wid;
	}
}

function actionSignUpForWorkshop()
{
	global $USER;
	$wid = intval($_GET['wid']);
	if (!userCan('signUpForWorkshop'))  throw new PolicyException();
	
	$participant = 1;
	if (in_array('kadra', $USER['roles']))  $participant = 4;
	
	$result = db_query('SELECT participant FROM table_workshop_user '.
		'WHERE wid='. $wid .' AND uid='. $USER['uid']);
	$result = db_fetch_assoc($result);
	if ($result === false)
		db_insert('workshop_user', array('wid'=>$wid, 'uid'=>$USER['uid'], 'participant'=>$participant));
	else
		db_update('workshop_user', 'WHERE wid='. $wid .' AND uid=' .$USER['uid'],
			array('participant'=>$participant));
	
	showMessage("Pomyślnie zapisano na warsztaty #$wid.", 'success');
	actionShowWorkshop();
	logUser('workshop participant signup', $wid); 
}

function actionResignFromWorkshop()
{
	global $USER;
	if (!isset($_GET['wid']))  throw new KnownException('Nie podano numeru bloku warsztatowego.');
	$wid = intval($_GET['wid']);
	$result = db_query('SELECT lecturer FROM table_workshop_user '.
		'WHERE wid='. $wid .' AND uid='. $USER['uid']);
	$result = db_fetch_assoc($result);
	if ($result['lecturer'])
		db_update('workshop_user', 'WHERE wid='. $wid .' AND uid=' .$USER['uid'],
			array('participant'=>0));
	else
		db_query('DELETE FROM table_workshop_user WHERE wid='. $wid .' AND uid='. $USER['uid']);
	showMessage("Pomyślnie wypisano z warsztatów #$wid.", 'success');
	actionShowWorkshop();
	logUser('workshop participant resign', $wid); 
}

function domainOrder($domains)
{
	$r = 0;
	if (in_array('matematyka', $domains))               $r-=1;
	if (in_array('informatyka teoretyczna', $domains))  $r+=2;
	if (in_array('informatyka praktyczna', $domains))   $r+=4;
	if (in_array('fizyka', $domains))                   $r+=8;
	if (in_array('astronomia', $domains))               $r+=16;
	return $r;
}

function actionRecountDomainOrder()
{
	global $DB;
	$wids = $DB->query('SELECT wid FROM table_workshops')->fetch_column();
	foreach ($wids as $wid)
	{
		$domains = $DB->query('SELECT domain FROM table_workshop_domain WHERE wid=$1', $wid);
		$order = domainOrder($domains->fetch_column());
		$DB->workshops[$wid]->update(array('domain_order'=>$order));
	}
}
