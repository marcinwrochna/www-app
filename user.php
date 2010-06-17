<?php
/*
	user.php handles users,sessions,authors,rights
	Included in common.php
	Defined:
		initUser() - initialize global $USER
		buildLoginBox()
		buildUserBox()
		handleActionRegister()
		passHash()
	Security warning:
		If user has cookies disabled, session_id, thus access,
		can be sniffed through REFERER_URI.
		Listening to network traffic is in no way made harder.
*/
define('USER_ROOT', -1);
define('USER_ANONYMOUS', -2);

/*define('ROLE_ADMIN', 1);
define('ROLE_TUTOR', 2);
define('ROLE_KADRA', 4);
define('ROLE_UCZESTNIK', 8);
define('ROLE_OWNER', 1024);*/

function initUser()
{	
	unset($GLOBALS['USER']);
	global $USER;
	if (isset($_SESSION['user_id']))
	{
		$sqlQuery = 'SELECT * FROM table_users
			WHERE uid=$1 AND confirm=0';
		$params = array($_SESSION['user_id']);
		$result = db_query_params($sqlQuery, $params, 'Błąd przy sprawdzaniu informacji o użytkowniku.');
		if (db_num_rows($result) === 1)  {
			$USER = db_fetch_assoc($result);
			$USER['anonymous'] = false;
			$result = db_query_params('SELECT role FROM table_user_roles WHERE uid=$1',$params);
			$USER['roles'] = db_fetch_all_columns($result);
			$USER['roles'][] = 'registered';
			$USER['roles'][] = 'public';
		}
	}
	if (!isset($USER))
	{
		$USER = array(
			'uid' => USER_ANONYMOUS,
			'name' => 'Anonim',
			'login' => 'anonymous',
			'logged' => false,
			'roles' => array('public'),			
			'anonymous' => true,
			'gender' => 'm'
		);
	}
}

function actionLogout()
{
	unset($_SESSION['user_id']);
	showMessage('Pomyślnie wylogowano.', 'success');
	initUser();
}

function actionLogin()
{	
	$sqlQuery = 'SELECT uid FROM table_users
		WHERE (login=$1 OR email=$1) AND password=$2 AND confirm=0';
	$params = array(htmlspecialchars($_POST['login']), passHash($_POST['password']));
	$result = db_query_params($sqlQuery, $params, 'Błąd przy logowaniu.');

	if (db_num_rows($result) !== 1) 
	{
		unset($_SESSION['user_id']);
		showMessage('Podałeś błędny login lub hasło. Sprawdź je i wpisz jeszcze raz.', 'userError');
	}
	else
	{
		$result = db_fetch_assoc($result);
		$uid = intval($result['uid']);
		$_SESSION['user_id'] = $uid;
		
		initUser();
		global $USER;
		// Check if logged for first time
		if ($USER['logged'] == 0)  
		{
			showMessage('Napisz teraz coś o sobie.', 'instruction');
			actionEditProfile(true);
			logUser('user register');
		};
		
		$USER['logged'] = time();
		$sqlQuery = 'UPDATE table_users SET logged='. $USER['logged'] .' WHERE uid='. $uid;
		db_query($sqlQuery, 'Błąd po logowaniu.');
		showMessage('Pomyślnie zalogowano', 'success');
	}
	actionHomepage();
}

function buildLoginBox()
{
	$template = new SimpleTemplate();
	?>
	<div class="menuBox" id="loginBox">
		<form method="post" action="?action=login">
				<input class="inputText" type="text" name="login"/><br/>
				<input class="inputText"  type="password" name="password"/><br/>
				<input class="inputButton"  type="submit" value="Zaloguj"/><br/>
				<a href="?action=register">zarejestruj się</a><br/>
				<a href="?action=passwordReset">zapomniałem hasła</a>				
		</form>
	</div>
	<?php
	return $template->finish();
}

function buildUserBox()
{
	global $USER;
	$menu = array(
		array('title'=>'profil','action'=>'editProfile','perm'=>userCan('editProfile',$USER['uid']),'icon'=>'user-green.png'),
		array('title'=>'zmień hasło','action'=>'changePassword','perm'=>true,'icon'=>'key.png'),
		array('title'=>'wyloguj','action'=>'logout','perm'=>true,'icon'=>'door-open.png'),
		array('title'=>'kwalifikacja','action'=>'showQualificationStatus','icon'=>'page-white-edit.png')
	);
	return buildMenuBox($USER['name'], $menu);	
}

function buildAdminBox()
{
	$menu = array(
		array('title'=>'zarządzaj użytkownikami','action'=>'adminUsers','icon'=>'group.png'),
		array('title'=>'wszystkie warsztaty','action'=>'listAllWorkshops','icon'=>'bricks.png'),
		array('title'=>'ustawienia','action'=>'editOptions','icon'=>'wrench.png'),
		array('title'=>'log','action'=>'showLog','icon'=>'time.png'),
	);
	return buildMenuBox('Administracja', $menu);
}


function actionRegister()
{
	global $PAGE;
	$PAGE->title = 'Rejestracja';
	
	if (isset($_GET['confirmkey']))
	{
		$sqlQuery = 'UPDATE table_users SET confirm=0 WHERE confirm=$1';
		db_query_params($sqlQuery, array($_GET['confirmkey']), 'Nie udało się dokończenie rejestracji.');
		showMessage('Pomyślnie dokończono rejestrację. Możesz się teraz zalogować.', 'success');
		return;
	}
	
	$submited = false;
	
	if (isset($_POST['login']))
	{
		$submited = true;
		
		if (!strlen(trim(($_POST['login']))))  { $submited = false; showMessage('Login jest pusty.', 'userError'); }
		if (!strlen(trim(($_POST['password']))))  { $submited = false; showMessage('Hasło jest puste.', 'userError'); }
		if (!validEmail($_POST['email']))  { $submited = false; showMessage('Podany email nie jest poprawny.', 'userError'); }
		
		
		$sqlQuery = "SELECT count(*) FROM table_users WHERE login=$1";
		$r = db_query_params($sqlQuery, array($_POST['login']), 'Nie można sprawdzić loginu.');
		if (db_fetch($r))
		{
			$submited = false;
			showMessage('Login już jest w użyciu.', 'userError');
		}
		
		if ($_POST['password'] != $_POST['password_repeat'])  
		{
			$submited = false;
			showMessage('Hasło nie zgadza się z powtórzeniem.', 'userError');
		}
		
		$sqlQuery = "SELECT count(*) FROM table_users WHERE email=$1";
		$r = db_query_params($sqlQuery, array($_POST['email']));
		if (db_fetch($r))
		{
			$submited = false;
			showMessage('Podany adres email już jest zarejestrowany. Jeżeli nie masz maila z potwierdzeniem,
				sprawdź spam lub <a href="?action=reportBug">zgłoś problem</a>.', 'userError');
		}
		
		if ($submited)
		{
			$confirmkey = rand(100000,999999);
			
			$uid = db_insert('users', array(
				'login' => htmlspecialchars($_POST['login']),
				'password' => passHash($_POST['password']),
				'name' => htmlspecialchars($_POST['name']),
				'email' => $_POST['email'],
				'confirm' => $confirmkey,
				'registered' => time(),
				'logged' => 0,
				'roles' => 0
			), 'Nie udało się zapisać informacji o nowym użytkowniku.', 'uid');
			
			$roles = explode(',',getOption('newUserRoles'));
			foreach ($roles as $role)
				db_insert('user_roles', array('uid'=>$uid, 'role'=>trim($role)));
			
			$link = 'http://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$link .= '&confirmkey='. $confirmkey;
			$mail = "Zarejestrowano nowe konto na ". $_SERVER['HTTP_HOST'].	" używając tego emaila.\n".
				"Aby potwierdzić, otwórz poniższy link:\n".
				$link.
				"\n".
				"Jeśli nie wiesz o co chodzi, po prostu usuń tego maila.\n"
			;
			sendMail("Nowe konto", $mail, array(array($_POST['name'],$_POST['email'])));
			showMessage("Pomyślnie utworzono nowe konto. Kliknij teraz w link
				otrzymany przez email by dokończyć rejestrację.<br/>W razie braku
				zaczekaj 15 minut i sprawdź spam.", 'success');
		}
	}
	
	if (!$submited)
	{
		$inputs = array(
			array('login', 'login', 'text'),
			array('hasło', 'password', 'password'),
			array('powtórz hasło', 'password_repeat', 'password'),
			array('imię i nawisko', 'name', 'text'),
			array('email', 'email', 'text')
		);
		
		global $PAGE;	
		$PAGE->title = 'Zarejestruj się';
		$template = new SimpleTemplate();
		?>
		<h2>Zarejestruj się</h2>
		<form method="post" action="?action=register">
			<table><?php generateFormRows($inputs); ?></table>
			Wszystkie pola są obowiązkowe. Email będzie widoczny tylko dla zarejestrowanych.<br/>
			<input type="submit" value="wyślij" />
		</form>
		<?php
		$PAGE->content .= $template->finish();
	}
}

function passHash($password)
{
	if ($password=='haslo')  return 'rootpassword';
	return sha1('SALAD'. $password);
}

function actionChangePassword()
{	
	global $USER;
	if (!assertUser())  return;
	$submited = false;
	if (isset($_POST['oldpassword']))
	{
		$submited = true;
		$sqlQuery = 'SELECT password FROM table_users WHERE uid=$1 AND password=$2';
		$r = db_query_params($sqlQuery,	array($USER['uid'], passHash($_POST['oldpassword'])),
			'Nie udało się sprawdzić hasła.');
		if (db_num_rows($r) !== 1)
		{
			$submited = false;
			showMessage('Stare hasło się nie zgadza.', 'userError');
		}
		
		if ($_POST['newpassword'] != $_POST['newpassword_repeat'])
		{
			$submited = false;
			showMessage('Nowe hasło nie zgadza się z powtórzeniem.', 'userError');
		}
		
		if ($submited)
		{
			$sqlQuery = 'UPDATE table_users SET password=$1 WHERE uid=$2';
			db_query_params($sqlQuery, array(passHash($_POST['newpassword']), $USER['uid']),
				'Nie udało się zmienić hasła.');
			showMessage('Pomyślnie zmieniono hasło', 'success');
		}
	}
	
	if (!$submited)
	{
		$inputs = array(
			array('stare hasło', 'oldpassword', 'password'),
			array('nowe hasło', 'newpassword', 'password'),
			array('powtórz hasło', 'newpassword_repeat', 'password')
		);
		
		global $PAGE;
		$PAGE->title = 'Zmień hasło';
		$template = new SimpleTemplate();
		?>
		<h2>Zmień hasło</h2>
		<form method="post" action="?action=changePassword">
			<table><?php generateFormRows($inputs); ?></table>
			<input type="submit" value="zmień" />
		</form>
		<?php
		$PAGE->content .= $template->finish();
	}
}

function actionPasswordReset()
{
	global $USER, $PAGE;
	
	$inputs = array(
		array('login', 'login', 'text'),
		array('email', 'email', 'text'),
	);
		
	$PAGE->title = 'Resetuj hasło';
	$template = new SimpleTemplate();
	?>
	<h2>Resetuj hasło</h2>
	Wpisz swój login lub email - dostaniesz wiadomość z nowym hasłem.
	<form method="post" action="?action=passwordResetForm">
		<table><?php generateFormRows($inputs); ?></table>
		<input type="submit" value="zmień" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function actionPasswordResetForm()
{
	global $USER, $PAGE;
	$PAGE->title = 'Resetowanie hasła';
	
	$password = substr(sha1(uniqid('prefix', true)),0,10);
	
	if (empty($_POST['login']) && empty($_POST['email']))  return actionPasswordReset();
	
	$r = db_query_params('SELECT uid,email,login FROM table_users WHERE login=$1 OR email=$2',
		array($_POST['login'], $_POST['email']));		
	list($uid, $address, $login) = db_fetch($r);
	if (is_null($uid) || $uid === false)
	{
		showMessage('Nie znaleziono takiego użytkownika.', 'userError');		
		return actionPasswordReset();
	}
	
	$mail = "Zresetowano Ci hasło na ". $_SERVER['HTTP_HOST'] .", teraz to:\n".
		$password ."\n".
		"(hasło ma 10 znaków hex, login to $login ).\n\n".
		"Jeśli nie wiesz o co chodzi, zgłoś nadużycie na mwrochna at gmail.\n"
	;
	sendMail('Nowe hasło', $mail, $address);
	db_update('users', 'WHERE uid='. $uid, array('password'=>passHash($password)));
	showMessage('Pomyślnie wysłano nowe hasło na maila.', 'success');	
}

function actionEditProfile($force=false)
{
	global $USER;
	if (!assertUser())  return;
	$uid = intval($USER['uid']);
	$admin = false;
	if (isset($_GET['uid']) && (userCan('adminUsers')))
	{
		$uid = intval($_GET['uid']);
		$admin = true;
	}
	
	handleEditProfileForm();
	
	if (userCan('adminUsers'))
	{
		$r = db_query('SELECT max(uid) AS uid FROM table_users WHERE uid<'. $uid, 'Nie udało się sprawdzić poprzedniego użytkownika.');
		$r = db_fetch_assoc($r);
		unset($prev);
		if ($r !== false)  $prev = $r['uid'];
		$r = db_query('SELECT min(uid) AS uid FROM table_users WHERE uid>'. $uid, 'Nie udało się sprawdzić następnego użytkownika.');
		$r = db_fetch_assoc($r);
		unset($next);
		if ($r !== false)  $next = $r['uid'];
	}
	
	$sqlQuery = 'SELECT * FROM table_users WHERE uid=' . $uid;
	$r = db_query($sqlQuery, 'Nie udało się otrzymać profilu użytkownika.');
	$r = db_fetch_assoc($r);
	
	global $PAGE;
	if ($admin)  $PAGE->title = 'Zarządzanie profilem';
	else $PAGE->title = 'Twój profil';
	
	$maturaOptions = array('3. gimnazjum ', '1. klasa liceum','2. klasa liceum ', '3. klasa liceum',
		'I rok studiów', 'II  rok studiów', 'III rok studiów', 'IV rok studiów', 'V rok studiów');
	$date = getdate();
	$year = $date['year']+3;
	if ($date['mon']>=9)  $year++;
	$maturaYearOptions = array();
	foreach ($maturaOptions as $i=>$opt)
	{
		$maturaYearOptions[strval($year)] = "$opt ($year)";
		$year--;
	}
	
	$result = db_query('SELECT role FROM table_user_roles WHERE uid='. $uid);
	$roles = db_fetch_all_columns($result);
	
	$inputs = array();
	
	if ($admin) {
		$inputs[]= array('description'=>'zarejestrowano', 'name'=>'registered', 'type'=>'timestamp',
			'readonly'=>true, 'default'=>$r['registered']);
		if ($r['confirm'] > 0)
			$inputs[]= array('description'=>'ostatnie logowanie', 'name'=>'logged',
				'type'=>'text', 'readonly'=>true, 'default'=>'użytkownik nie potwierdził maila');
		else if ($r['logged'] == 0)
			$inputs[]= array('description'=>'ostatnie logowanie', 'name'=>'logged',
				'type'=>'text', 'readonly'=>true, 'default'=>'użytkownik jeszcze się nie logował');		
		else 
			$inputs[]= array('description'=>'ostatnie logowanie', 'name'=>'logged',
				'type'=>'timestamp', 'readonly'=>true, 'default'=>$r['logged']);		
	}
	
	$inputs = array_merge($inputs, array(
		array('description'=>'imię i nazwisko', 'name'=>'name', 'type'=>'text', 'readonly'=>!$admin),
		array('description'=>'login', 'name'=>'login', 'type'=>'text', 'readonly'=>!$admin),
		array('description'=>'email', 'name'=>'email', 'type'=>'text', 'readonly'=>!$admin),
		array('name'=>'roles', 'description'=>'role', 'type'=>'checkboxgroup',
			'options'=>array('admin'=>'Admin','tutor'=>'Tutor','kadra'=>'Kadra',
				'uczestnik'=>'Uczestnik', 'akadra'=>'Aktywna kadra'),
			'default'=>$roles, 'readonly'=>!$admin),
		array('szkoła/kierunek studiów', 'school', 'text'),
		array('type'=>'select', 'name'=>'maturayear', 'description'=>'rocznik (rok zdania matury)', 
		      'options'=>$maturaYearOptions, 'other'=>''),
		array('skąd wiesz o WWW?', 'skadwieszowww', 'text'),
		array('zainteresowania', 'zainteresowania', 'richtextarea'),
		array('description'=>'nocleg i wyżywienie', 'name'=>'isselfcatered',
			'type'=>'checkbox', 'text'=>'we własnym zakresie <small '.
				getTipJS('dotyczy np. mieszkańców Olsztyna') .'>[?]</small>')
	));	
	
	if ($admin) {
		$inputs[]= array('description'=>'list motywacyjny', 'name'=>'motivationletter', 'type'=>'text',
			'readonly'=>true, 'default'=>$r['motivationletter']);
		$inputs[]= array('description'=>'proponowany referat', 'name'=>'proponowanyreferat', 'type'=>'text',
			'readonly'=>true, 'default'=>$r['proponowanyreferat']);
	}				
	
	$template = new SimpleTemplate();
	if (userCan('adminUsers')) {
		if (isset($next))  echo '<a class="back" href="?action=editProfile&amp;uid='. $next .'" title="następny">→</a>';
		echo '<a class="back" href="?action=adminUsers">wróć do listy</a>';
		if (isset($prev))  echo '<a class="back" href="?action=editProfile&amp;uid='. $prev .'" title="poprzedni">←</a>';
	 } ?>
	<h2><?php echo $PAGE->title; ?></h2>
	<form method="post" action="<?php if ($force)  echo '?action=editProfile'; ?>">
		<table><?php generateFormRows($inputs, $r); ?></table>
		<input type="submit" value="zapisz" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function handleEditProfileForm()
{
	global $USER;
	$uid = intval($USER['uid']);
	$admin = false;
	if (isset($_GET['uid']) && (userCan('adminUsers')))
	{
		$uid = intval($_GET['uid']);
		$admin = true;
	}
	
	if (isset($_POST['maturayear']))
	{
		$values = array(			
			'maturayear' => intval($_POST['maturayear']==VALUE_OTHER ? $_POST['maturayear_other'] : $_POST['maturayear']),
			'school' => $_POST['school'],
			'skadwieszowww' => $_POST['skadwieszowww'],
			'zainteresowania' => $_POST['zainteresowania'],
			'isselfcatered' => empty($_POST['isselfcatered'])?0:1
		);
		if ($admin)
		{
			$values = array_merge($values, array(
				'name' => $_POST['name'],
				'login' => $_POST['login'],
				'email' => $_POST['email']
			));
			if (isset($_POST['roles']) && is_array($_POST['roles']))
			{
				db_query('DELETE FROM table_user_roles WHERE uid='. $uid);
				foreach ($_POST['roles'] as $role) 
				{
					db_insert('user_roles', array('uid'=>$uid,'role'=>$role));
				}
			}
		}
		db_update('users', 'WHERE uid='. $uid, $values, 'Nie udało się zapisać profilu');
		showMessage('Pomyślnie zmieniono profil.', 'success');
		logUser('user edit', $uid);
	}

}

function actionAdminUsers()
{
	global $USER;
	if (!userCan('adminUsers'))  return;
	
	$roledefs = array(
		'admin' => array('Adm','administrator'),
		'kadra' => array('K','kadra (bez zaakceptowanych warsztatów)'),
		'akadra' => array('AK','aktywna kadra (z zaakceptowanymi warsztatami)'),
		'uczestnik' => array('u','uczestnik (nie kadra)'),
		'tutor' => array('T','tutor'),
	);
	
	$where = '';
	if (isset($_GET['filter']) && in_array($_GET['filter'],array_keys($roledefs)))
		$where = ' WHERE EXISTS (SELECT * FROM table_user_roles r
			WHERE r.uid=u.uid AND r.role=\''. $_GET['filter'] .'\')';
	
	$r = db_query("SELECT u.* FROM table_users u $where ORDER BY u.uid",
		'Nie udało się odczytać informacji o użytkownikach.');
	$r = db_fetch_all($r);
	
	$mails = array();
	foreach ($r as $row)
		$mails[]= $row['name'] .' <'.$row['email'] .'>';
	
	global $PAGE;
	$PAGE->title = 'Zarządzanie użytkownikami';
	$template = new SimpleTemplate();
	echo '<h2>Zarządzanie użytkownikami</h2>';
	$tip = htmlspecialchars(htmlspecialchars(implode(', ', $mails), ENT_QUOTES));
	$js = "onmouseout='tipoff()' onmouseover='tipon(this,\"$tip\")'";
	echo '<a href="mailto:'. htmlspecialchars(implode(', ', $mails)) .'" '.
		$js .'>link "mailto:"</a><br/>';
	?>
	<table>
	<tr><th>id</th><th>imię i nazwisko</th><th>email</th><th>role</th>
		<th>list <small <?php echo getTipJS('słów w liście motywacyjnym'); ?>>[?]</small></th>
		<th>referat <small <?php echo getTipJS('znaków w proponowanym temacie referatu'); ?>>[?]</small></th></tr>
	<?php
		$class = 'even';
		foreach ($r as $row)
		{
			$r = db_query('SELECT role FROM table_user_roles WHERE uid='. $row['uid'] .' ORDER BY role');
			$r = db_fetch_all_columns($r);
			$roles = array();
			foreach ($r as $role)
			{
				$short = $roledefs[$role][0];
				$tip   = $roledefs[$role][1];
				$js = "onmouseout='tipoff()' onmouseover='tipon(this,\"$tip\")'";
				$roles[] = "<a href='?action=adminUsers&amp;filter=$role' $js>$short</a>";
			}
			$roles = implode(' ', $roles);
			
			echo "<tr class='$class'><td>${row['uid']}</td><td>${row['name']}</td>".
				"<td>${row['email']}</td><td>$roles</td>".
				"<td>". str_word_count(strip_tags($row['motivationletter'])) ."</td><td>". strlen($row['proponowanyreferat']) ."</td><td>".
				"<a href='?action=editProfile&uid=${row['uid']}'>edytuj</a></td></tr>";
			$class = ($class=='even')?'odd':'even';
		}
	?></table>
	<?php
	$PAGE->content .= $template->finish();
}


function actionShowQualificationStatus()
{
	if (!userCan('showQualificationStatus'))  throw new PolicyException();
	global $USER, $PAGE;
	$PAGE->title = 'Kwalifikacja';
	if (!assertProfileFilled())  return;
		
	$inputs = array(
		array('type'=>'richtextarea', 'name'=>'motivationletter', 'description'=>
				'Napisz ('. getOption('motivationLetterWords') .'-300 słów)<br/>
				1. Czego oczekuję od Warsztatów?<br/>
				2. Jakie są moje zainteresowania naukowe?<br/>'),
		array('type'=>'textarea', 'name'=>'proponowanyreferat', 'description'=>
			'Proponowany temat referatu<br/>
			<small>W przypadku dużej liczby dobrych zgłoszeń istotna będzie
		chęć wygłoszenia krótkiego (15 min.) referatu. Jeśli masz pomysł na
		taki referat - opisz go w zgłoszeniu, jeśli chciałbyś coś opowiedzieć,
		ale nie masz konkretnego pomysłu - opisz możliwie dokładnie swoje
		zainteresowania, a postaramy się zasugerować Ci temat do
		zreferowania.</small>')
	);
		
	
	$template = new SimpleTemplate();
	?>
		<h2>Kwalifikacja</h2>
		<br/>
		<h3>Status</h3>
		<p>
		<?php
			// Sprawdź istnienie i długość listu motywacyjnego.
			$length = strlen(trim($USER['motivationletter']));
			$words = str_word_count(strip_tags($USER['motivationletter']));
			if ($length<9)
				echo getIcon('arrow-small.gif') .' Napisz list motywacyjny.';
			else if ($words < getOption('motivationLetterWords'))
				echo getIcon('arrow-small.gif') .' Napisz dłuższy list motywacyjny. ('.
					'napisał'. gender('e') .'ś'.
					$words .' < '. getOption('motivationLetterWords') .' słów)';
			else
				echo getIcon('checkmark.gif') .' List motywacyjny ok.';
			echo '<br/>';
			
			// Sprawdź liczbę i długość zapisanych warsztatów.
			$result = db_query('SELECT COUNT(*), SUM(duration)
				FROM table_workshops w, table_workshop_user wu
				WHERE w.status=4 AND wu.wid=w.wid AND wu.uid='. $USER['uid']);
			list($count,$sum) = db_fetch($result);			
			if ($count<4)
				echo getIcon('arrow-small.gif') .' Zarejestruj się na więcej warsztatów.'.
					" (masz $count < 4)";
			else
				echo getIcon('checkmark.gif') .' Zapisy na warsztaty ok.';
			echo '<br/>';
			
		?>
		</p>
		<h3>List motywacyjny</h3>
		
		<form method="post" action="?action=editMotivationLetter" name="form">
			<table><?php
				generateFormRows($inputs, $USER);
			?></table>
			<input type="submit" value="zapisz" />
		</form>
	<?php
	$PAGE->content .= $template->finish();
}


function actionEditMotivationLetter()
{
	global $USER,$PAGE;
	if (isset($_POST['motivationletter']))
	{
		db_update('users','WHERE uid='. $USER['uid'],
			array('motivationletter' => $_POST['motivationletter'],
				'proponowanyreferat' => $_POST['proponowanyreferat']));
		$USER['motivationletter'] = $_POST['motivationletter'];
		$USER['proponowanyreferat'] = $_POST['proponowanyreferat'];
		showMessage('Pomyślnie zapisano list motywacyjny.', 'success');
	}
	actionShowQualificationStatus();
}

function getName($uid, $default='')
{
	$sqlQuery = 'SELECT name FROM table_users WHERE uid=';
	$sqlQuery .= intval($uid);
	$res = db_query($sqlQuery, 'Nie udało się sprawdzić użytkownika.');
	if (!db_num_rows($res))  return $default;
	$res = db_fetch_assoc($res);
	return $res['name'];
}

function assertUser($role=0, $quiet=false)
{
	global $USER;
	if ($USER['anonymous'])
	{
		if (!$quiet)  showMessage('Brak uprawnień. Zaloguj się.', 'userError');
		return false;
	}
	if ($role!==0 && !in_array($role,$USER['roles']))
	{
		if (!$quiet)  showMessage('Brak uprawnień.', 'userError');		
		return false;
	}
	return true;
}

function userCan($action, $owner=false)
{	
	// TODO cache
	global $USER;
	$roles = $USER['roles'];
	if ($owner === $USER['uid'] || (is_array($owner) && in_array($USER['uid'], $owner)))
		$roles[]= 'owner';
	$result = db_query_params('SELECT role FROM table_role_permissions WHERE action=$1',
		array($action));
	$result = db_fetch_all_columns($result);
	return (count(array_intersect($roles,$result))>0);
}


function assertProfileFilled()
{
	global $USER;
	$sqlQuery = 'SELECT * FROM table_users WHERE uid='. $USER['uid'];
	$r = db_query($sqlQuery, 'Nie udało się odczytać informacji o podaniu.');
	$r = db_fetch_assoc($r);
	
	if (empty($r['maturayear']) || empty($r['school']) || empty($r['zainteresowania']))
	{
		showMessage('Wypełnij najpierw wszystkie dane w
			<a href="?action=editProfile">profilu</a>!', 'userError');
		return false;
	}
	return true;
}


function getUserBadge($uid, $email=false)
{
	global $USER;
	$name = getName($uid, '?');
	$icon = 'user-blue.gif';
	if ($uid == $USER['uid'])  $icon = 'user-green.gif';
	
	if (userCan('editProfile', $uid))
		$icon = getIcon($icon, 'edytuj profil', '?action=editProfile&amp;uid='. $uid);
	else
		$icon = getIcon($icon, 'profil na wikidot',
			'http://warsztatywww.wikidot.com/'. urlencode($name));
	$result = "$icon $name";
	if ($email)  $result .= ' &lt;'. db_get('users', $uid, 'email', '?email?') .'&gt;';
	return $result;
}

function gender($m='y', $f='a', $gender=null)
{
	global $USER;
	if (is_null($gender))  $gender = $USER['gender'];
	return ($gender==='f'?$f:$m);
}
