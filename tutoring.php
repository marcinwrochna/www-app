<?php
/*
	tutoring.php
	Included in index.php
	Defined:
		buildTutoringBox()
		actionMyTutorial()
*/
// TODO review, translate, ..

function addTutoringMenuBox()
{
	global $USER, $PAGE;
	$items = parseTable('
		ACTION                   => tTITLE;        ICON;
		editTutoringApplication  => twoje podanie; application-my.png;
		viewTutoringApplications => lista podań;   application-list.png;
	');
	$items['editTutoringApplication']['perm'] = userCan('editProfile',$USER['uid']);
	$PAGE->addMenuBox('Tutorial', $items);
}

function actionEditTutoringApplication()
{
	global $DB, $USER, $PAGE;
	if (!userCan('editTutoringApplication'))  throw new PolicyException();
	if (!assertProfileFilled())  return;

	$user = $DB->users[$USER['uid']]->assoc('podanieotutora,tutoruid');

	if (empty($user['podanieotutora']))  $status = 'brak podania';
	else if ($user['tutoruid'] == USER_ANONYMOUS)  $status = 'oczekuje...';
	else $status = getName($user['tutoruid']) .' jest twoim tutorem :)';
	$PAGE->content .= '<b>status</b>: '. $status .'<br/>';

	$PAGE->title = 'Podanie o tutora';
	$form = new Form(array(
		array('richtextarea', 'podanieotutora',
				'To jest miejsce gdzie możesz napisać list motywacyjny - czego chcesz
				się nauczyć, czego oczekujesz od tutora, dlaczego akurat Ty, itd.
				Tutorzy będą przeglądać te listy i wybierać sobie podopiecznych -
				ktoś skontaktuje się z Tobą mailowo w ciągu tygodnia.')
	));
	$form->action = 'editTutoringApplicationForm';
	$form->values = $user;
	$form->submitValue = 'Zapisz';
	$PAGE->content .= $form->getHTML();
}

function actionEditTutoringApplicationForm()
{
	global $DB,$USER,$PAGE;
	if (!userCan('editTutoringApplication'))  throw new PolicyException();
	$application = trim($_POST['podanieotutora']);
	if (empty($_POST['podanieotutora']))  $application = NULL;
	$DB->users[$USER['uid']]->update(array(
			'podanieotutora' => $application,
			'tutoruid' => USER_ANONYMOUS
	));
	$PAGE->addMessage('Podanie pomyślnie zapisane.', 'success');
	logUser('tutor podanie');
	actionEditTutoringApplication();
}

function actionViewTutoringApplications()
{
	global $DB,$USER,$PAGE;
	if (!userCan('viewTutoringApplications'))  throw new PolicyException();

	$users = $DB->query('SELECT uid,name,email,podanieotutora,tutoruid  FROM table_users
		WHERE podanieotutora IS NOT NULL  ORDER BY uid');

	global $PAGE;
	$PAGE->title = 'Lista podań o tutora';

	if (!count($users))  $PAGE->content .= 'brak podań';
	else
	{
		$PAGE->content .= '<table><tr><th>imię i nazwisko</th><th>tutor</th><th>podanie</th></tr>';
		foreach ($users as $user)
			$PAGE->content .= '<tr class="'. alternate('even','odd') .'">'.
				'<td>'. getUserBadge($user['uid'], true) .'</td>'.
				'<td>'. getUserBadge($user['tutoruid'], false, 'nikt') .'</td>'.
				'<td>'. getIcon('arrow-right.png', 'zobacz podanie',
					'viewTutoringApplication('. $user['uid'] .')') .'</td></tr>';
		$PAGE->content .= '</table>';
	}
}

function actionViewTutoringApplication($uid)
{
	global $DB,$USER,$PAGE;
	if (!userCan('viewTutoringApplications', $uid))  throw new PolicyException();

	$uid = intval($uid);
	$user = $DB->users[$uid]->assoc('school,maturayear,podanieotutora,zainteresowania,tutoruid');
	$user['badge'] = getUserBadge($uid, true);

	global $PAGE;
	$PAGE->title = 'Podanie o tutora';
	$template = new SimpleTemplate($user);
	?>
	<a class="back" href="viewTutoringApplications">wróć</a>
	<b>czyje</b>: %badge%<br/>
	<b>tutor</b>: <?php
		echo getUserBadge($user['tutoruid'], false, 'nikt') .' ';
		if ($user['tutoruid'] == USER_ANONYMOUS)
			echo getButton('zostań tutorem', "considerTutoringApplication($uid;true)");
		if ($user['tutoruid'] == $USER['uid'])
			echo getButton('zrezygnuj',         "considerTutoringApplication($uid;false)");
		?><br/>
	<b>treść</b>: <br/><div class="descriptionBox">%podanieotutora%</div>
	<b>zainteresowania</b>: <br/><div class="descriptionBox">%zainteresowania%</div>
	<b>szkoła/kierunek studiów</b>: %school%<br/>
	<b>rok uzyskania matury</b>: %maturayear%<br/>
	<?php
	$PAGE->content .= $template->finish();
}

function actionConsiderTutoringApplication($uid, $agree)
{
	global $DB,$USER,$PAGE;
	if (!userCan('viewTutoringApplications'))  throw new PolicyException();
	$uid = intval($uid);
	if ($agree === 'false')  $agree = false;

	$currentTutor = $DB->users[$uid]->get('tutoruid');
	if ($agree && ($currentTutor != USER_ANONYMOUS))
	{
		$PAGE->addMessage("Użytkownik <i>". getName($uid). "</i> (uid:$uid) już ma tutora".
			" - <i>". getName($currentTutor). "</i> (uid:$currentTutor).", 'userError');
		return actionViewTutoringApplication($uid);
	}
	if (!$agree && ($currentTutor != $USER['uid']))
	{
		$PAGE->addMessage("Próbujesz zrezygnować z tutoringu, do którego Cię nie przypisano.",
			'userError');
		return actionViewTutoringApplication($uid);
	}

	$DB->users[$uid]->update(array('tutoruid' => ($agree ? $USER['uid'] : USER_ANONYMOUS)));
	$PAGE->addMessage('Pomyślnie '. ($agree ? 'za' : 'wy') .'rejestrowano Cię jako tutora.', 'success');
	logUser('tutor '. ($agree ? 'become' : 'resign'), $uid);
	actionViewTutoringApplication($uid);
}
