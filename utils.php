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

// Returns camelCase when given ALL_CAPS.
function allCapsToCamelCase($s)
{
	return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($s)))));
}

function addSiteMenuBox()
{
	global $PAGE;
	$PAGE->addMenuBox(_('WWWapp'). getOption('currentEdition'), parseTable('
		ACTION                           => tTITLE;           ICON;        bPERM;
		homepage                         => main page;        house.png;   true;
		http://warsztatywww.wikidot.com/ => wikidot;          wikidot.gif; true;
		reportBug                        => report a problem; bug.png;     true;
	'));
}

function actionHomepage()
{
	global $PAGE, $DB, $USER;
	$PAGE->title = _('Main page');
	$PAGE->headerTitle = '';
	echo getOption('homepage');

	$currentEdition = getOption('currentEdition');
	$row = $DB->edition_users($currentEdition, $USER['uid']);
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

	/* todo-list: participants. */
	// TODO oh the hell with the past tense, use imperative only, easier to translate, manage, and stuff.
	$did = parseTable('
		NAME               => tNOT_DONE_TEXT; tDONE_TEXT;    tCOMMON_TEXT;                    ACTION;
		applyAsParticipant => Apply;          You applied;   as a participant.;               applyAsParticipant;
		fillProfile        => Fill;           You filled;    your profile.;                   editProfile;
		signupForWorkshops => Sign up;        You signed up; for at least 4 workshop blocks.; listPublicWorkshops;
		writeMotLetter     => Write;          You wrote;     a motivation letter.;            editMotivationLetter;
		solveTasks         => Solve;          You solved;    qualification tasks;
		qualify            => Wait for results.;  You have been qualified.; ;
		fillAdditionalInfo => Fill;           You filled;    the additional info form.;       editAdditionalInfo;
	');
	$did['solveTasks']['commonText'] .= ' ('. _('before') .' '. '10 lipca' .').';

	$did['applyAsParticipant']['done'] = $didApplyAsParticipant;
	$did['applyAsParticipant']['enabled'] = !$didApplyAsLecturer;
	$did['fillProfile']['done'] = assertProfileFilled(true);
	$did['fillProfile']['enabled']
		= $did['signupForWorkshops']['enabled']
		= $did['writeMotLetter']['enabled']
		= $did['solveTasks']['enabled']
		= $didApplyAsParticipant;

	// Check number of workshops signed up for.
	$query = 'SELECT count(*)
		FROM table_workshops w, table_workshop_users wu
		WHERE w.wid=wu.wid AND w.edition=$1 AND wu.uid=$2 AND
			(wu.participant>=$3) AND w.status>=$4';
	$DB->query($query, $currentEdition, $USER['uid'],
		enumParticipantStatus('candidate')->id, enumBlockStatus('ok')->id);
	$did['signupForWorkshops']['done'] = $DB->fetch() >= 4;

	// Check motivation letter.
	$mLetter = $DB->users[$USER['uid']]->get('motivationletter');
	$words = str_word_count(strip_tags($mLetter));
	$did['writeMotLetter']['done'] = ($words > getOption('motivationLetterWords'));
	if (!$did['writeMotLetter']['done'] && $words)
		$did['writeMotLetter']['commonText'] =
			sprintf(_(' a longer motivation letter (you wrote %d < %d words)'),
				$words, getOption('motivationLetterWords'));
	// Check qualification tasks solved.
	$DB->query($query, $currentEdition, $USER['uid'],
		enumParticipantStatus('accepted')->id, enumBlockStatus('ok')->id);
	$did['solveTasks']['done'] = $DB->fetch() >= 4;
	$did['solveTasks']['enabled'] = $didApplyAsParticipant && $did['signupForWorkshops']['done'];


	$did['qualify']['done'] = $didQualify;
	$didEverything = $did['signupForWorkshops']['done'] && $did['writeMotLetter']['done'];
	$did['qualify']['enabled'] = ($didEverything || $didQualify) && $didApplyAsParticipant;

	$did['fillAdditionalInfo']['done'] = null; // TODO
	$did['fillAdditionalInfo']['enabled'] = $did['qualify']['done'] && $didApplyAsParticipant;

	echo '<h4>'. _('Want to participate in workshops only?') .'</h4><ul class="todoList">';
	if (!userIs('registered'))
		echo '<li><a href="register">'. ('Create an account'). '</a>/'. _('log in') .'.</li>';
	foreach ($did as &$d)
		echo buildTodoElement($d);
	echo '</ul><br/>';

	/* todo-list: lecturers. */
	$did = parseTable('
		NAME               => tNOT_DONE_TEXT; tDONE_TEXT;    tCOMMON_TEXT;          ACTION;
		applyAsLecturer    => Apply;          You applied;   as a lecturer.;        applyAsLecturer;
		fillProfile        => Fill;           You filled;    your profile.;         editProfile;
		proposeWorkshop    => Propose;        You proposed;  a workshop block.;     createWorkshop;
		qualify            => Wait for preliminary approval.; Your workshop block has been preliminarly approved.; ;
		writeDescription   => Write;          You wrote;     a decription for users on wikidot;
		checkTasks         => ;               ;              ;
	');
	$did['proposeWorkshop']['commonText'] .= ' ('. _('before') .' '. '15 kwietnia' .').';
	$did['writeDescription']['commonText'] .= ' ('. _('before') .' '. '1 maja' .').';
	$did['checkTasks']['notDoneText'] =
		sprintf(_('Write (before %s) and check solutions to (before %s)'), '10 maja', '10 lipca');
	$did['checkTasks']['doneText'] = _('You wrote and checked solutions to');
	$did['checkTasks']['commonText']  = _('qualification tasks.');

	$did['applyAsLecturer']['done'] = $didApplyAsLecturer;
	$did['applyAsLecturer']['enabled'] = !$didApplyAsParticipant;
	$did['fillProfile']['done'] = assertProfileFilled(true);
	$did['fillProfile']['enabled'] = $didApplyAsLecturer;
	// Check if any workshop block has been proposed.
	$DB->query('
		SELECT count(*)
		FROM table_workshops w, table_workshop_users wu
		WHERE w.wid=wu.wid AND w.edition=$1 AND wu.uid=$2 AND wu.participant=$3',
		$currentEdition, $USER['uid'], enumParticipantStatus('lecturer')->id
	);
	$did['proposeWorkshop']['done'] = $DB->fetch() > 0;
	$did['proposeWorkshop']['enabled'] = $didApplyAsLecturer;
	// Check if any workshop block has been accepted.
	/* $DB->query('
		SELECT count(*)
		FROM table_workshops w, table_workshop_users wu
		WHERE w.wid=wu.wid AND w.edition=$1 AND wu.uid=$2 AND wu.participant=$3 AND w.status>=$4',
		$currentEdition, $USER['uid'], enumParticipantStatus('lecturer')->id, enumBlockStatus('ok')->id
	);
	$didGetAccepted = $DB->fetch() > 0; */
	$did['qualify']['done'] = $didQualify && $didApplyAsLecturer;
	$did['qualify']['enabled'] = $did['proposeWorkshop']['done'];

	// Things that aren't (TODO: could be) automatically checked.
	$did['writeDescription']['done'] = null;
	$did['writeDescription']['enabled'] = $did['proposeWorkshop']['done'];
	$did['checkTasks']['done'] = null;
	$did['checkTasks']['enabled'] = $did['qualify']['done'];

	echo '<h4>'. _('Want to give a workshop block?') .'</h4><ul class="todoList">';
	if (!userIs('registered'))
		echo '<li><a href="register">'. ('Create an account'). '</a>/'. _('log in') .'.</li>';
	foreach ($did as &$d)
		echo buildTodoElement($d);
	echo '</ul>';
	echo _('If more than one lecturer is to give a workshop block, '.
	       'one of them should submit a proposition, '.
	       'and then attach the remaining lecturers.') .'<br/>';
	echo _('Wikidot accounts are separate - '.
	       'to edit anything there you\'ll have to use a wikidot account.');
}

function buildTodoElement($element)
{
	global $USER;
	// arguments: 'done', 'enabled', 'action', 'doneText', 'notDoneText', 'commonText'
	foreach ($element as $key => $val)
		$$key = $val;

	if (!empty($action))
		$enabled = $enabled && userCan($action);

	if (!is_null($done))
		$class = $enabled ? ($done ? 'done' : 'todo') : 'disabled';
	else
		$class = $enabled ? 'enabled' : 'disabled';
	if (!in_array('registered', $USER['roles']))
		$class  = 'disabled';

	if ($enabled && !empty($action))
		$notDoneText = "<a href='$action'>$notDoneText</a>";
	$result = ($done ? $doneText : $notDoneText) .' '. $commonText;
	$result = genderize($result);
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

	if ($DB->edition_users(getOption('currentEdition'), $USER['uid'])->count())
	{
		$PAGE->addMessage(_('You have already been signed up for this year\'s edition.'), 'userError');
		callAction('homepage');
		return;
	}

	$DB->edition_users[] = array(
		'edition'   => getOption('currentEdition'),
		'uid'       => $USER['uid'],
		'qualified' => 0,
		'lecturer'  => $lecturer ? 1 : 0
	);
	$DB->user_roles[] = array('uid' => $USER['uid'], 'role' => $lecturer ? 'kadra' : 'uczestnik');
	$USER['roles'][] = $lecturer ? 'kadra' : 'uczestnik';
	$PAGE->addMessage(_('Signed you up for this year\'s edition.'), 'success');
	callAction('homepage');
}

function actionReportBug()
{
	global $PAGE, $USER;
	$PAGE->title = _('Report a problem');
	$desc = _('Something doesn\'t work like it should? Things are unclear, overcomplicated, '.
		'ugly, inconvenient? You absolutely have to report this!') . '<br/>';
	if (!userIs('registered'))
		$desc .= '<small>'. _('Write how to contact you, if you expect an answer.'). '</small>';
	else
		$desc .= '<small>'. _('By default we\'ll reply by e-mail') .' ('. $USER['email'] .').</small>';

	$form = new Form(array(array('textarea', _('problem'), $desc)), 'reportBugForm'); // TODO deprecated form.
	echo $form->getHTML();
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
	$PAGE->addMessage('Wysłane. Dzięki!', 'success');
}


function actionAbout()
{
	global $PAGE;
	$PAGE->title = _('Credits');
	?>
		{{Coders}}: Marcin Wrochna<br/>
		{{Icons}}:
		<ul>
			<li><a href="http://www.fatcow.com/free-icons/">FatCow</a>
				(Creative Commons Attribution 3.0 License),</li>
			<li><a href="http://code.google.com/p/twotiny/">twotiny</a> (Artistic License/GPL,
				support the <a href="http://mojavemusic.ca/">Mojave</a> band)</li>
		</ul>
		WYSIWIG editor: <a href="http://tinymce.moxiecode.com/">TinyMCE</a> (LGPL)<br/>
	<?php
	// TODO add missing credits (uber-upload, mimetex, see README.txt)
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
	$PAGE->title = _('Settings');
	$form->action = 'editOptionsForm'; // TODO deprecated form.
	echo $form->getHTML();
}

function actionEditOptionsForm()
{
	global $DB, $PAGE;
	foreach ($_POST as $name=>$value)
		if ($DB->options[$name]->get('type') != 'readonly')
				$DB->options[$name] = array('value' => $value);
	$PAGE->addMessage(_('Saved settings.'), 'success');
	logUser('admin setting');
	callAction('editOptions');
}

function actionDatabaseRaw()
{
	global $DB, $USER, $PAGE;
	if (!in_array('admin', $USER['roles']))  throw new PolicyException();

	if (isset($_POST['query']))
	{
		$result = $DB->query($_POST['query']);
		$PAGE->addMessage('Rows affected: '. $result->affected_rows(), 'success');
		$PAGE->content .= '<b>SELECT result:</b><table>';
		foreach ($result as $i=>$row)
			$PAGE->content .= '<tr><td>'. $i .'</td><td>'. implode('</td><td>',$row) .'</td></tr>';
		$PAGE->content .= '</table>';
	}

	global $PAGE;
	$PAGE->title = 'Database';
	$form = new Form(array(array('textarea', 'query', '<b>query</b>'))); // TODO deprecated form.
	echo $form->getHTML();
}

/* DEPRECATED (use parseTable instead). Still used in form.php.
 *
 * Converts first count($keys) numerical indices to named keys (e.g. 2=>$value to $keys[2]=>$value),
 * additional indices are changed to keys with value true (e.g. 42=>$value to $value=>true).
 * So it's like php's array_combine(),
 * but handling extra values and leaving existing named (associative) keys unchanged.
 * Useful when you don't want to write the keys every time.
 * Example: arrayToAssoc(array('myName','myValue','readonly','custom'=>'c'), array('name','value'))
 * returns array('name'=>'myName', 'value'=>'myValue', 'custom'=>'c', 'readonly'=>true) */
function arrayToAssoc($array, $keys)
{
	$result = $array;
	for ($i = 0; $i < count($keys); $i++)
		if (array_key_exists($i, $result))
	{
			$result[$keys[$i]] = $result[$i];
			unset($result[$i]);
	}
	return $result;
}

/*	Parses a table description into an associative array of associative arrays.
 *	Columns can have modifiers: t for translating (gettext), # for integers (intval), b for booleans.
 *	Example: for $description='
 *		NAME  => TYPE; #VALUE;
 *		name1 => t1;   1v;    custom => c1;
 *		name2 => t2;   ;      something => s1; readonly;
 *		name3 => t3;
 *	')
 *	parseTable returns array(
 *		'name1' => array('name'=>'name1', 'type'=>'t1', 'value'=>intval('1v'), 'custom'=>'c1'),
 *		'name2' => array('name'=>'name2', 'type'=>'t2', 'value'=>'', 'something'=>s1', 'readonly'=>true),
 *		'name3' => array('name'=>'name3', 'type'=>'t3')
 *	);
 * The $gettext param is only used by the translator script to find strings to translate.
 */
function parseTable($description, $gettext = 'gettext')
{
	// Parse line by line.
	$rows = explode("\n", $description);
	$result = array();
	$i = 0;
	foreach ($rows as $row)
	{
		// Skip empty lines.
		$row = trim($row);
		if (!strlen($row))
			continue;
		// Parse "key => field1; field2; field3;" (fields may contain '=>' but may not contain ';')
		$row = explode('=>', $row, 2);
		$key = trim($row[0]);
		$fields = explode(';', $row[1]);
		array_pop($fields); // Empty space after last ';'.
		foreach($fields as &$field)
			$field = trim($field);
		// First row describes key names (headers) and column modifiers.
		if ($i == 0)
		{
			$keyHeader = allCapsToCamelCase($key);
			$headers = array();
			foreach ($fields as &$h)
			{
				if ($h[0] == '#' || $h[0] == 'b' || $h[0] == 't')
				{
					$h = array('name' => substr($h, 1), 'modifier' => $h[0]);
				}
				else
					$h = array('name' => $h, 'modifier' => ' ');
				$h['name'] = allCapsToCamelCase($h['name']);
				$headers[]= $h;
			}
		}
		// Following rows are saved accordingly (fields are modified if header applies,
		// split if they contain '=>').
		else
		{
			$row = array();
			foreach ($fields as $j => &$field)
			{
				if ($j < count($headers))
				{
					$f = trim($field);
					switch ($headers[$j]['modifier'])
					{
						case '#':  $f = intval($f); break;
						case 'b':  $f = $f === 'true' || $f === '1' | $f === 'True';  break;
						case 't':
							if (empty($f))
								$f = '';
							else if ($gettext == 'gettext')
								$f = gettext($f);
							else
								call_user_func($gettext, $f, $headers[$j]['name'], $key); break;
					}
					$row[$headers[$j]['name']] = $f;
				}
				else if (strpos($field, '=>'))
				{
					$f = explode('=>', $field, 2);
					$row[trim($f[0])] = trim($f[1]);
				}
				else
					$row[trim($field)] = true;
			}
			$row[$keyHeader] = $key;
			$result[$key] = $row;
		}
		$i++;
	}
	return $result;
}

function getUpdatedOrder($name, $headers, $default)
{
	$allowed = array();
	foreach ($headers as $h)
		if (!empty($h['order']))
			$allowed[]= $h['order'];

	$name = 'order_'. $name;
	$order = array();
	if (!empty($_GET['order']))
		if (in_array($_GET['order'], $allowed))
			$order[]= $_GET['order'];
	if (isset($_SESSION[$name]))
		foreach ($_SESSION[$name] as $o)
			if (in_array($o, $allowed))
				$order[]= $o;

	while (count($order) > 4)
		array_pop($order);

	if (empty($order))
		$order[]= $default;

	$_SESSION[$name] = $order;
	return $order;
}

function dbg($o)
{
	global $PAGE;
	$PAGE->addMessage(htmlspecialchars(json_encode($o)));
}
