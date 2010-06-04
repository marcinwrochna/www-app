<?php
/*
	tutoring.php
	Included in index.php
	Defined:
		buildTutoringBox()
		actionMyTutorial()
*/

function buildTutoringBox()
{
	$template = new SimpleTemplate();
	?>
	<div class="menuBox" id="tutorBox">
		<h3>Tutorial</h3>
		<ul>
			<li><a href="?action=myTutorial">twoje podanie</a></li>
			<?php if (assertUser(ROLE_TUTOR,true) || assertUser(ROLE_ADMIN,true)) { ?>
				<li><a href="?action=listTutorings">lista podań</a></li>
			<?php } ?>
		</ul>
	</div>
	<?php
	return $template->finish();
}

function actionMyTutorial()
{
	global $USER;
	if (!assertUser())  return;
	
	if (!assertProfileFilled())  return;
	
	if (isset($_POST['podanieotutora']))
	{
		$_POST['podanieotutora'] = trim($_POST['podanieotutora']);
		if (empty($_POST['podanieotutora']))
			$_POST['podanieotutora'] = NULL;
		db_update('users', 'WHERE uid='.$USER['uid'], array(
			'podanieotutora' => $_POST['podanieotutora'],
			'tutoruid' => USER_ANONYMOUS
		), 'Nie udało się zapisać podania');
		showMessage('Podanie pomyślnie zapisane.', 'success');
		logUser('tutor podanie');
	}

	
	if (empty($r['podanieotutora']))  $status = 'brak podania';
	else if ($r['tutoruid']==USER_ANONYMOUS)  $status = 'oczekuje...';
	else $status = getName($r['tutoruid']) .' jest twoim tutorem :)';
	
	global $PAGE;
	$PAGE->title = 'Podanie o tutora';
	$template = new SimpleTemplate();
	?>
	<h2>Podanie o tutora</h2>
	<form method="post" action="">
		<b>status</b>: <?php echo $status; ?>
		<table>
		<?php
			generateFormRows(array(array(
				'<p>To jest miejsce gdzie możesz napisać list motywacyjny - czego chcesz
				się nauczyć, czego oczekujesz od tutora, dlaczego akurat Ty, itd.
				Tutorzy będą przeglądać te listy i wybierać sobie podopiecznych -
				ktoś skontaktuje się z Tobą mailowo w ciągu tygodnia. </p>',
				'podanieotutora', 'richtextarea')), $r);
		?></table>
		<input type="submit" value="zapisz" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function actionListTutorings()
{
	if (!(assertUser(ROLE_TUTOR,true) || assertUser(ROLE_ADMIN)))  return;
	
	$r = db_query('SELECT * FROM table_users WHERE podanieotutora IS NOT NULL ORDER BY uid',
		'Nie udało się odczytać informacji o użytkownikach.');
	$r = db_fetch_all($r);
	
	global $PAGE;
	$PAGE->title = 'Lista podań o tutora';
	$template = new SimpleTemplate();
	?>
	<h2>Lista podań o tutora</h2>
	<form method="post" action="">
		<table>
		<thead><th>imię i nazwisko</th><th>email</th><th>tutor</th><th>podanie</th></thead>
		<?php
			if (!empty($r))
			foreach ($r as $row)
			{
				echo "<tr><td>${row['name']}</td>".
					"<td>${row['email']}</td>".
					"<td>". getName($row['tutoruid'], 'nikt'). "</td>".
					"<td><a href='?action=viewPodanieotutora&uid=${row['uid']}'>".
					"link</a></td></tr>";
			}
			else echo '<tr><td>brak podań</td></tr>';
		?></table>
		<input type="submit" value="zapisz" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function actionViewPodanieotutora()
{
	global $USER;
	if (!(assertUser(ROLE_TUTOR,true) || assertUser(ROLE_ADMIN)))  return;
	
	$uid = intval($_GET['uid']);
	
	if (isset($_GET['agree']))
	{
		if ($_GET['agree'])
		{
			db_query('UPDATE table_users SET tutoruid='. $USER['uid']
				.' WHERE tutoruid='. USER_ANONYMOUS.' AND uid='. $uid,
				'Nie udało się zarejestrować tutora');				
			showMessage('Pomyślnie zarejestrowano tutora.', 'success');
			logUser('tutor become', $uid);
		}
		else
		{
			db_query('UPDATE table_users SET tutoruid='. USER_ANONYMOUS
				.' WHERE tutoruid='. $USER['uid'].' AND uid='. $uid,
				'Nie udało się wyrejestrować tutora');	
			showMessage('Pomyślnie wyrejestrowano tutora.', 'success');
			logUser('tutor resign', $uid);
		}
	}
	
	
	$sqlQuery = 'SELECT * FROM table_users WHERE uid='. $uid;
	$r = db_query($sqlQuery, 'Nie udało się odczytać podania');
	$r = db_fetch_assoc($r);
	foreach ($r as $k=>$v)  if (!in_array($k,array('podanieotutora','zainteresowania')))
		$r[$k] = nl2br(htmlspecialchars($v));
	
	global $PAGE;
	$PAGE->title = 'Podanie o tutora';
	$template = new SimpleTemplate($r);
	?>
	<h2>Podanie o tutora</h2>
	<b>czyje</b>: %name% (%login%, %email%)<br/>
	<b>tutor</b>: <?php
		echo getName($r['tutoruid'], 'nikt');
		if ($r['tutoruid'] == USER_ANONYMOUS)
			echo " <a href='?action=viewPodanieotutora&uid=$uid&agree=1'>[przyjmij tutorial]</a>";
		if ($r['tutoruid'] == $USER['uid'])
			echo " <a href='?action=viewPodanieotutora&uid=$uid&agree=0'>[zrezygnuj]</a>";
		?><br/>
	<b>treść</b>: <br/>
	%podanieotutora%<br/>
	<b>zainteresowania</b>: <br/>
	%zainteresowania%<br/>
	<b>szkoła/kierunek studiów</b>: %school%<br/>
	<b>rok uzyskania matury</b>: %maturayear%<br/>
	<?php
	$PAGE->content .= $template->finish();
}
