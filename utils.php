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
	$PAGE->addMenuBox('Aplikacja WWW6', array(
		array('strona główna', 'homepage',                         'house.png',   true),
		array('wikidot',       'http://warsztatywww.wikidot.com/', 'wikidot.gif', true),
		array('zgłoś problem', 'reportBug',                        'bug.png',     true)
	));
}

function actionHomepage()
{
	global $PAGE, $DB;
	$PAGE->title = 'Strona główna';	
	$PAGE->headerTitle = '';	
	$PAGE->content .= getOption('homepage');
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
