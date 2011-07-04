<?php
/*
 * utils.php
 * Included in index.php
 */
 
function nvl($value, $default)
{
	return is_null($value) ? $default : $value;
}

function is_assoc(&$array) {
	$next = 0;
	foreach ($array as $k=>$v) {
		if ($k !== $next)
			return true;
		$next++;
	}
	return false;
}

function addSiteMenuBox()
{
	global $PAGE;
	$PAGE->addMenuBox('Aplikacja WWW'. getOption('currentEdition'), array(
		array('strona główna', 'homepage',                         'house.png',   true),
		array('wikidot',       'http://warsztatywww.wikidot.com/', 'wikidot.gif', true),
		array('zgłoś problem', 'reportBug',                        'bug.png',     true)
	));
}

function actionHomepage()
{
	global $PAGE, $DB, $USER;
	$PAGE->title = 'Strona główna';	
	$PAGE->headerTitle = '';	
	$PAGE->content .= getOption('homepage');
	$isProfileFilled = assertProfileFilled(true);	
	$currentEdition = getOption('currentEdition');
	$row = $DB->edition_user($currentEdition, $USER['uid']);
	$didApplyAsLecturer = false;
	$didApplyAsParticipant = false;		
	$didQualify = false;
	if ($row->count())
	{
		if ($row->get('lecturer'))
			$didApplyAsLecturer = true;
		else
			$didApplyAsParticipant = true;
		$didQualify = $row->get('qualified');
	}
	$didApply = $didApplyAsParticipant || $didApplyAsLecturer;
	
	$template = new SimpleTemplate();
	
	/* todo-list: participants. */
	$query = 'SELECT count(*)
		FROM table_workshops w, table_workshop_user wu
		WHERE w.wid=wu.wid AND w.edition=$1 AND wu.uid=$2 AND
			(wu.participant>=$3) AND w.status>=$4';
	$DB->query($query, $currentEdition, $USER['uid'],
		enumParticipantStatus('candidate')->id, enumBlockStatus('ok')->id);
	$didSignup = $DB->fetch() >= 4;
	$DB->query($query, $currentEdition, $USER['uid'],
		enumParticipantStatus('accepted')->id, enumBlockStatus('ok')->id);
	$didTasks = $DB->fetch() >= 4;
	$mLetter = $DB->users[$USER['uid']]->get('motivationletter');	
	$words = str_word_count(strip_tags($mLetter));
	$didWriteMLetter = ($words > getOption('motivationLetterWords'));
	$MLetterText = ' list motywacyjny.';
	if (!$didWriteMLetter && $words)
		$MLetterText =' dłuższy list motywacyjny. (napisał'. gender('e') .'ś '. 
			$words .' < '. getOption('motivationLetterWords') .' słów)';
	$didEverything = (($didSignup && $didWriteMLetter) || $didQualify) && $didApplyAsParticipant;
	
	$elements = applyDefaultHeaders(
		array('done',                 'enabled',           'action',                     'notDoneText', 'doneText', 'commonText'),
		array(
			array($didApplyAsParticipant, !$didApply,             'applyAsParticipant',      'Zgłoś', 'Zgłosił%ś', ' się jako uczestnik.'),
			array($isProfileFilled,       $didApplyAsParticipant, 'editProfile',             'Wypełnij', 'Wypełnił%ś', ' profil.'),
			array($didSignup,             $didApplyAsParticipant, 'listPublicWorkshops',     'Zapisz', 'Zapisał%ś', ' się na co najmniej 4 bloki warsztatowe.'),		
			array($didWriteMLetter,       true,                   'showQualificationStatus', 'Napisz', 'Napisał%ś', $MLetterText, ),
			array($didTasks,              $didSignup,             null,                      'Rozwiąż', 'Rozwiązał%ś', ' zadania kwalifikacyjne (do 10 lipca).'),
			array($didQualify,            $didEverything,         null,                      'Czekaj na wyniki.', 'Został%ś zakwalifikowan%.', ''),
		)
	);
	foreach ($elements as $i => &$element)
		if($i && !is_null($element['done']))
			$element['done'] = $element['done'] && $didApplyAsParticipant;
	
	echo '<h4>Chcesz tylko uczestniczyć w warsztatach?</h4><ul class="todoList">';
	if (!in_array('registered', $USER['roles']))
		echo '<li><a href="register">Załóż konto</a>/zaloguj się.</li>';
	foreach ($elements as &$element)
		echo buildTodoElement($element);	
	echo '</ul><br/>';	
		
	/* todo-list: lecturers. */		
	$DB->query('
		SELECT count(*)
		FROM table_workshops w, table_workshop_user wu
		WHERE w.wid=wu.wid AND w.edition=$1 AND wu.uid=$2 AND wu.participant=$3',
		$currentEdition, $USER['uid'], enumParticipantStatus('lecturer')->id
	);
	$didCreateWorkshop = $DB->fetch() > 0;
	/*$DB->query('
		SELECT count(*)
		FROM table_workshops w, table_workshop_user wu
		WHERE w.wid=wu.wid AND w.edition=$1 AND wu.uid=$2 AND wu.participant=$3 AND w.status>=$4',
		$currentEdition, $USER['uid'], enumParticipantStatus('lecturer')->id, enumBlockStatus('ok')->id
	);
	$didGetAccepted = $DB->fetch() > 0;*/	
	$didCreateWorkshop = $didCreateWorkshop && $didApplyAsLecturer;
	$didQualify = $didQualify && $didApplyAsLecturer;
	
	$elements = applyDefaultHeaders(
		array('done',              'enabled',           'action',          'notDoneText', 'doneText', 'commonText'),
		array(
			array($didApplyAsLecturer, !$didApply,          'applyAsLecturer', 'Zgłoś', 'Zgłosił%ś', ' się jako kadra.'),
			array($isProfileFilled, $didApplyAsLecturer, 'editProfile',     'Wypełnij', 'Wypełnił%ś', ' profil.'),
			array($didCreateWorkshop,  true,                'createWorkshop',  'Zaproponuj', 'Zaproponował%ś', ' blok warsztatowy (do 15 kwietnia).'),
			array($didQualify,         $didCreateWorkshop,  null,              'Czekaj na wstępną akceptację.', 'Wstępnie zaakceptowano Twój blok warsztatowy.', ''),
			array(null,                $didCreateWorkshop,  null,              'Napisz', 'Napisał%ś', ' opis dla uczestników na wikidocie (do 1 maja).'),
			array(null,                $didQualify,         null,              '', '', 'Napisz (do 10 maja) i sprawdź (do 10 lipca) zadania kwalifikacyjne.', ),
		)
	);
	foreach ($elements as $i => &$element)
		if($i && !is_null($element['done']))
			$element['done'] = $element['done'] && $didApplyAsLecturer;
	echo '<h4>Chcesz poprowadzić warszaty?</h4><ul class="todoList">';
	if (!in_array('registered', $USER['roles']))
		echo '<li><a href="register">Załóż konto</a>/zaloguj się.</li>';	
	foreach ($elements as &$element)
		echo buildTodoElement($element);
	echo '</ul>';	
	echo 'Jeżeli blok warsztatowy ma prowadzić więcej osób, to jedna z nich powinna dodać propozycję
		i podpiąć do niej pozostałych prowadzących. Na wikidot trzeba mieć wikidotowe konto.';
	
	$PAGE->content .= $template->finish();
}

function buildTodoElement($element)
{
	global $USER;
	// arguments: 'done', 'enabled', 'action', 'doneText', 'notDoneText', 'commonText'
	foreach ($element as $key => $val)
		$$key = $val;
		
	if ($action)
		$enabled = $enabled && userCan($action);
		
	if (!is_null($done))
		$class = $done ? 'done' : ($enabled ? 'todo' : 'disabled');
	else 
		$class = $enabled ? 'enabled' : 'disabled';
	if (!in_array('registered', $USER['roles']))
		$class  = 'disabled';		
	
	if ($enabled && $action)
		$notDoneText = "<a href='$action'>$notDoneText</a>";
	$result = ($done ? $doneText : $notDoneText) . $commonText;
	$result = genderize	($result);
	return "<li class='$class'>$result</li>";
}


function actionApplyAsLecturer()
{
	if (!userCan('applyAsLecturer'))  throw new PolicyException();
	applyForCurrentWorkshopEdition(true);
}
	
function actionApplyAsParticipant()
{
	if (!userCan('applyAsParticipant'))  throw new PolicyException();
	applyForCurrentWorkshopEdition(false);
}

function applyForCurrentWorkshopEdition($lecturer)
{
	global $DB, $PAGE, $USER;
	
	if ($DB->edition_user(getOption('currentEdition'), $USER['uid'])->count())
	{
		$PAGE->addMessage('Już zgłoszono Cię na tę edycję warsztatów.', 'userError');
		actionHomepage();
		return;
	}
		
	$DB->edition_user[] = array(
		'edition'   => getOption('currentEdition'),
		'uid'       => $USER['uid'],
		'qualified' => 0,
		'lecturer'  => $lecturer ? 1 : 0
	);
	$DB->user_roles[] = array('uid' => $USER['uid'], 'role' => $lecturer ? 'kadra' : 'uczestnik');
	$USER['roles'][] = $lecturer ? 'kadra' : 'uczestnik';
	$PAGE->addMessage('Pomyślnie zgłoszono Cię jako '. ($lecturer ? 'kadrę.' : 'uczestnika.'), 'success');
	actionHomepage();
}

function actionReportBug()
{
	global $PAGE, $USER;
	$PAGE->title = 'Zgłoś problem';
	$desc = 'Coś nie działa tak jak powinno? Coś jest niejasne, niepotrzebnie skomplikowane,
		brzydkie, niewygodne? Zgłoś to koniecznie.<br/>';
	if (!in_array('registered', $USER['roles']))
		$desc .= '<small>Napisz jakiś kontakt, jeśli chcesz dostać odpowiedź.</small>';
	else 
		$desc .= '<small>Domyślnie odpowiem Ci na maila ('. $USER['email'] .').</small>';
		
	$form = new Form();
	$form->action = 'reportBugForm';
	$form->addRow('textarea', 'problem', $desc);
	$PAGE->content .= $form->getHTML();
}

function actionReportBugForm()
{
	global $USER, $PAGE;
	$PAGE->title = 'Zgłoszono problem';	
	$template = new SimpleTemplate($_SERVER);
	?><html><body>
	Zgłoszono problem na <i>%HTTP_HOST%</i>:<br/>
	<pre><?php echo htmlspecialchars($_POST['problem']); ?></pre><hr/><br/>
	<b>Czas:</b> <?php echo strftime('%F %T (%s)'); ?><br/>
	<b>HTTP_USER_AGENT:</b> %HTTP_USER_AGENT%<br/>
	<b>HTTP_REFERER:</b> %HTTP_REFERER%<br/>
	<b>USER:</b><br/>
	<pre><?php print_r($USER) ?></pre>
	</body></html><?php
	
	logUser('bugreport'); // Log first, in case of email failure.
	sendMail('Zgłoszono problem', $template->finish(), BUGREPORT_EMAIL_ADDRESS, true);
	showMessage('Wysłane. Dzięki!', 'success');
}


function actionAbout()
{
	global $PAGE;	
	$PAGE->title = 'Credits';	
	$template = new SimpleTemplate();
	?>
		Koderzy: Marcin Wrochna<br/>
		Ikonki
		<ul>
			<li><a href="http://www.fatcow.com/free-icons/">FatCow</a>
				(Creative Commons Attribution 3.0 License),</li>
			<li><a href="http://code.google.com/p/twotiny/">twotiny</a> (Artistic License/GPL,
				support the <a href="http://mojavemusic.ca/">Mojave</a> band)</li>
		</ul>
		Edytor WYSIWIG: <a href="http://tinymce.moxiecode.com/">TinyMCE</a> (LGPL)<br/>
	<?php
	$PAGE->content .= $template->finish();
}


function getOption($name)
{
	global $DB;
	$option = $DB->options[$name]->assoc('value, type');
	if ($option['type'] == 'int')  return intval($option['value']);
	return $option['value'];
}

function actionEditOptions()
{
	if (!userCan('editOptions'))  throw new PolicyException();	
	$form = new Form();
	
	global $DB;
	$options = $DB->query('SELECT * FROM table_options ORDER BY name');	
	foreach ($options as $r)
	{
		$form->addRow($r['type'], $r['name'], $r['description']);
		$form->values[$r['name']] = $r['value'];
	}
	if (isset($form->values['gmailOAuthAccessToken']))
		$form->values['gmailOAuthAccessToken'] =
			substr($form->values['gmailOAuthAccessToken'],0, 16) . '...';
	
	global $PAGE;
	$PAGE->title = 'Ustawienia';
	$form->action = 'editOptionsForm';
	$form->submitValue = 'Zapisz';
	$PAGE->content .= $form->getHTML();
}

function actionEditOptionsForm()
{
	global $DB, $PAGE;
	foreach ($_POST as $name=>$value)
		if ($DB->options[$name]->get('type') != 'readonly')
				$DB->options[$name] = array('value' => $value);
	$PAGE->addMessage('Pomyślnie zapisano ustawienia.', 'success');
	logUser('admin setting');
	actionEditOptions();
}

function actionDatabaseRaw()
{	
	global $DB, $USER, $PAGE;
	if (!in_array('admin', $USER['roles']))  throw new PolicyException();
	
	if (isset($_POST['query']))
	{
		$result = $DB->query($_POST['query']);
		$PAGE->addMessage('Rows affected: '. $result->affected_rows(), 'success');
		$PAGE->content .= '<b>Wynik selecta:</b><table>';
		foreach ($result as $i=>$row)
			$PAGE->content .= '<tr><td>'. $i .'</td><td>'. implode('</td><td>',$row) .'</td></tr>';
		$PAGE->content .= '</table>';
	}
	
	global $PAGE;	
	$PAGE->title = 'Baza danych';
	$form = new Form();
	$form->addRow('textarea', 'query', '<b>zapytanie</b>');
	$PAGE->content .= $form->getHTML();
}

function assertOrFail($bool, $message, &$correct)
{
	if (!$bool)
	{
		$correct = false;
		global $PAGE;
		if (!empty($message))			
			$PAGE->addMessage($message, 'userError');
	}
}

// TODO: change to ($keys, &$arrays)
function applyDefaultKeys(&$array, $keys)
{
	$i = 0;
	while (array_key_exists($i, $array))
	{
		$array[$keys[$i]] = $array[$i];
		unset($array[$i]);
		$i++;
	}		
	return $array;
}

function applyDefaultHeaders($keys, $rows)
{
	$result = $rows; // $rows can't be a reference, so we copy.
	foreach($result as $key => &$row)
	{
		applyDefaultKeys($row, $keys);
		$row['key'] = $key;
	}
	return $result;		
}

// by Douglas Lovell
// http://www.linuxjournal.com/article/9585?page=0,3
function validEmail($email)
{
	$isValid = true;
	$atIndex = strrpos($email, "@");
	if (is_bool($atIndex) && !$atIndex)  return false;
	
    $domain = substr($email, $atIndex+1);
    $local = substr($email, 0, $atIndex);
    $localLen = strlen($local);
    $domainLen = strlen($domain);
    if ($localLen < 1 || $localLen > 64)  return false;          // local part length exceeded
    if ($domainLen < 1 || $domainLen > 255)  return false; // domain part length exceeded
    if ($local[0] == '.' || $local[$localLen-1] == '.') return false; // local part starts or ends with '.'
    if (preg_match('/\\.\\./', $local))  return false; // local part has two consecutive dots
    if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))  return false; // character not valid in domain part
    if (preg_match('/\\.\\./', $domain))  return false; // domain part has two consecutive dots
    if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local)))
    {
       // character not valid in local part unless 
       // local part is quoted
       if (!preg_match('/^"(\\\\"|[^"])+"$/',
           str_replace("\\\\","",$local)))  return false;
    }
    if (DEBUG<1 && !(checkdnsrr($domain,"MX") ||  checkdnsrr($domain,"A")))  return false; // domain not found in DNS
    return true;
}

function dbg($o)
{
	global $PAGE;
	$PAGE->addMessage(htmlspecialchars(json_encode($o)));
}
