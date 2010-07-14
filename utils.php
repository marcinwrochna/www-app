<?php
/*
	utils.php
	Included in common.php
*/


function buildSiteBox()
{
	$menu = array(
		array('title'=>'strona główna','action'=>'homepage','perm'=>true,'icon'=>'house.png'),
		array('title'=>'wikidot','action'=>'http://warsztatywww.wikidot.com/','perm'=>true,'icon'=>'wikidot.gif'),
		array('title'=>'zgłoś problem','action'=>'reportBug','perm'=>true,'icon'=>'bug.png'),
	);
	return buildMenuBox('Aplikacja WWW6', $menu);	
}

function actionReportBug()
{
	global $PAGE, $USER;
	
	$desc = 'Coś nie działa tak jak powinno? Coś jest niejasne, niepotrzebnie skomplikowane,
		brzydkie, niewygodne? Zgłoś to koniecznie.<br/>';
	if ($USER['anonymous'])
		$desc .= '<small>Napisz jakiś kontakt, jeśli chcesz dostać odpowiedź.</small>';
	else 
		$desc .= '<small>Domyślnie odpowiem Ci na maila ('. $USER['email'] .').</small>';
	$inputs = array( array('type'=>'textarea', 'name'=>'problem', 'description'=>$desc) );	
	$PAGE->title = 'Zgłoś problem';	
	$template = new SimpleTemplate();
	?>
	<h2><?php echo $PAGE->title; ?></h2>
	<form method="post" action="?action=reportBugForm" name="form" id="theform">
		<table><?php generateFormRows($inputs);	?></table>
		<input type="submit" value="Wyślij" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function actionReportBugForm()
{
	logUser('bugreport');
	global $USER, $PAGE;
	$PAGE->title = 'Zgłoszono problem';	
	$mail = "Zgłoszono problem na ". $_SERVER['HTTP_HOST'] ."\n\"\"\"\n";
	$mail .= $_POST['problem'];
	$mail .= "\n\"\"\"\n";
	$mail .= json_encode($USER);
	$mail .= "\n\n";
	$mail .= strftime('%F %T (%s)');
	$mail .= "\n\n";
	sendMail('Zgłoszono problem', $mail, 'mwrochna@gmail.com');
	showMessage('Wysłane. Dzięki!', 'success');
}


function actionAbout()
{
	global $PAGE;
	
	$PAGE->title = 'Credits';	
	$template = new SimpleTemplate();
	?>
	Za wszystko co działa i nie działa należy obarczać winą Marcina Wrochnę.<br/>
	Ikonki:<br/>
		<a href="http://www.fatcow.com/free-icons/">FatCow</a> (Creative Commons Attribution 3.0 License),<br/>
		<a href="http://code.google.com/p/twotiny/">twotiny</a> (Artistic License/GPL, support the <a href="http://mojavemusic.ca/">Mojave</a> band)<br/>
	Edytor WYSIWIG: <a href="http://tinymce.moxiecode.com/">TinyMCE</a> (LGPL)<br/>
	<?php
	$PAGE->content .= $template->finish();
}


function actionEditOptions()
{
	if (!userCan('editOptions'))  throw new PolicyException();
	handleManageOptionsForm();
	
	$sqlQuery = 'SELECT * FROM table_options ORDER BY name';
	$result = db_query($sqlQuery, 'Błąd przy wczytywaniu ustawień.');
	
	global $PAGE;
	$PAGE->title = 'Ustawienia';
	$template = new SimpleTemplate();
	?>
		<div class="contentBox panel panelManageOptions">
			<h3>Ustawienia</h3>
			<br/>
			<form method="post" action="">
			<table>
			<?php
				$rows = array();
				$values = array();
				while ($r = db_fetch_assoc($result))
				{
					$rows []= array($r['description'], $r['name'], $r['type']);
					$values[$r['name']] = $r['value'];
				}
				echo generateFormRows($rows, $values);
			?>
			</table>
				<input type="submit" name="sent" value="Zapisz" />
			</form>
		</div>
	<?php
	$PAGE->content .= $template->finish();
}

function getOption($name)
{
	$sqlQuery = 'SELECT value, type FROM table_options WHERE name=$1';
	$result = db_query_params($sqlQuery, array($name), 'Błąd wczytywaniu ustawienia.');
	if (!db_num_rows($result))  throw new KnownException('Nie odnaleziono ustawienia.');
	$result = db_fetch_assoc($result);
	if ($result['type'] == 'int')  return intval($result['value']);
	return $result['value'];
}

function handleManageOptionsForm()
{
	if (!isset($_POST['sent']))  return;
	if (!userCan('editOptions'))  throw new PolicyException();
	foreach ($_POST as $name=>$value)
	{
		$sqlQuery = 'UPDATE table_options SET value=$1 WHERE name=$2';
		db_query_params($sqlQuery, array($value,$name), 'Błąd zapisywaniu ustawień.');
	}
	showMessage('Pomyślnie zapisano ustawienia.');
	logUser('admin setting');
}

function actionDatabase()
{	
	if (!assertUser('admin'))  return;
	
	if (isset($_POST['query']))
	{
		$result = db_query($_POST['query'], 'Nie udało się.');
		showMessage('Rows affected: '. pg_affected_rows($result), 'success');
		showMessage('Wynik selecta: '.json_encode(@db_fetch_all($result)));
	}
	
	global $PAGE;	
	$PAGE->title = 'Baza danych';
	$template = new SimpleTemplate();
	?>
	<h2>Surowy dostęp do bazy danych</h2>
	<form method="post" action="">
	<table><?php generateFormRows(array(array('zapytanie', 'query', 'textarea'))); ?></table>
		<input type="submit" value="wyślij" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}


function sendMail($subject, $content, $to)
{
	$from = 'noreply@warsztatywww.nstrefa.pl';
	$content .= "\n__\nEmail automatycznie wysłany z ". $_SERVER['HTTP_HOST'];
	//mail($to, "[WWW][app] $subject", $content , "From: $from", "-f$from");
	require_once('phpmailer/class.phpmailer.php');
	$mail = new PHPMailer();
	$mail->IsSMTP();
	$mail->SMTPAuth = true;
	$mail->Username = "mwrochna@gmail.com";
	$mail->Password = "xx23ssw";
	$mail->From = "noreply@warsztatywww.nstrefa.pl";
	$mail->FromName = "Aplikacja WWW";
	if (is_array($to))
		foreach ($to as $t)
			$mail->addAddress($t[1],$t[0]);
	else  $mail->addAddress($to);
	$mail->addReplyTo("noreply@warsztatywww.nstrefa.pl", "noreply");
	$mail->Subject = "[WWW][app] $subject";
	$mail->Body = $content;
	$mail->CharSet = 'utf-8';
	if(!$mail->Send())
		throw new KnownException('Błąd przy wysyłaniu maila: '. $mail->ErrorInfo);
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
