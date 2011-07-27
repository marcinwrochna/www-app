<?php
/*
 * user/admin.php
 */
 
function addAdminMenuBox()
{
	global $PAGE, $USER;
	$admin = in_array('admin', $USER['roles']);
	$PAGE->addMenuBox('Administracja', array(
		array('zarządzaj użytkownikami',   'adminUsers',       'group.png' ),
		array('wszystkie warsztaty',       'listAllWorkshops', 'bricks.png'),
		array('korelacje',                 'showCorrelation',  'table.png' ),
		array('ustawienia',                'editOptions',      'wrench.png'),
		array('log',                       'showLog',          'time.png'  ),
		array('lista danych personalnych', 'listPersonalData', null, $admin),
		array('lista dot. dojazdów',       'listArrivalData',  null, $admin),
		array('lista dot. posiłków',       'listDailyCounts',  null, $admin),
		array('podsumowanie punktów',      'showPointsTable',  null, $admin),
	));
}

function actionAdminUsers($filterBy = null)
{
	global $USER, $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	
	$roledefs = array(
		'admin' => array('Adm','administrator'),
		'kadra' => array('K','kadra (bez zaakceptowanych warsztatów)'),
		'akadra' => array('AK','aktywna kadra (z zaakceptowanymi warsztatami)'),
		'uczestnik' => array('u','uczestnik (nie kadra)'),
		'tutor' => array('T','tutor'),
		'jadący' => array('j','jadący')
	);
	
	$where = 'WHERE EXISTS (SELECT * FROM table_user_roles r WHERE r.uid=u.uid)';
	if (isset($filterBy) && $filterBy == 'all')
		$where = '';
	if (isset($filterBy) && in_array($filterBy, array_keys($roledefs)))
		$where = 'WHERE EXISTS (SELECT * FROM table_user_roles r
			WHERE r.uid=u.uid AND r.role=\''. $filterBy .'\')';
	
	
	$PAGE->title = 'Zarządzanie użytkownikami';
	$template = new SimpleTemplate();
	echo '<a href="adminUsers(all)">wszystkie konta</a><br/>';
	
	$users = $DB->query("SELECT u.uid, u.name, u.email, u.motivationletter, u.proponowanyreferat
	                 FROM table_users u $where ORDER BY u.uid");	
	$users = $users->fetch_all();
	$mails = array();
	foreach ($users as $row)
		$mails[]= $row['name'] .' <'.$row['email'] .'>';
	$mails = htmlspecialchars(implode(', ', $mails));
	echo '<a href="mailto:'. $mails .'" '. getTipJS($mails) .'>link "mailto:"</a><br/>';
	
	?>
	<table>
	<tr>
		<th>id</th><th>imię i nazwisko</th><th>email</th><th>role</th>
		<th>list <small <?php echo getTipJS('słów w liście motywacyjnym'); ?>>[?]</small></th>
		<th>referat <small <?php echo getTipJS('znaków w prop. temacie referatu'); ?>>[?]</small></th>
	</tr>
	<?php
		$class = 'even';
		foreach ($users as $row)
		{
			$r = $DB->query('SELECT role FROM table_user_roles WHERE uid=$1 ORDER BY role', $row['uid']);
			$row['roles'] = '';
			foreach ($r->fetch_column() as $role)
				$row['roles'] .= "<a href='adminUsers($role)' ".
					getTipJS($roledefs[$role][1]) .">".	$roledefs[$role][0] ."</a> ";			
			
			$row['ml_words'] = str_word_count(strip_tags($row['motivationletter']));
			$row['pr_chars'] = strlen($row['proponowanyreferat']);
			$row['class'] = $class;
			
			$sub = new SimpleTemplate($row);
			?><tr class='%class%'>
				<td>%uid%</td><td>%name%</td><td>%email%</td><td>%roles%</td>
				<td>%ml_words%</td><td>%pr_chars%</td>
				<td>
					<a href='editProfile(%uid%)'>edytuj</a> 
					<a href='editUserStatus(%uid%)'>status</a> 
					<a href='editAdditionalInfo(%uid%)'>dane</a>
				</td>
			</tr><?php
			echo $sub->finish();
				
			$class = ($class=='even')?'odd':'even';
		}
	?></table>
	<?php
	$PAGE->content .= $template->finish();
}


function actionEditUserStatus($uid)
{
	global $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$uid = intval($uid);

	$user = $DB->users[$uid]->assoc('uid,login,name,email,school,maturayear,gender,'.
	 'zainteresowania,motivationletter,proponowanyreferat');
	$roles = $DB->query('SELECT role FROM table_user_roles WHERE uid=$1', $uid);
	$user['roles'] = $roles->fetch_column();
	$user['badge'] = getUserBadge($uid);
	$user['maturayeartext'] = '('. $user['maturayear'] .')';
	$maturaYearOptions = getMaturaYearOptions();
	if (array_key_exists(strval($user['maturayear']), $maturaYearOptions))
		$user['maturayeartext'] = $maturaYearOptions[$user['maturayear']];	
	$user['jadący'] = (array_search('jadący', $user['roles']) !== false) ? 'checked="checked"' : '';	
	$user['genderya'] = gender('y','a',$user['gender']);	
		
	$user['workshops'] = '<table>';
	$workshops = $DB->query('
		SELECT w.wid, w.title, w.duration, wu.*
		FROM table_workshop_user wu, table_workshops w
		WHERE w.edition=$1 AND w.wid=wu.wid AND uid=$2', getOption('currentEdition'), $uid);
	foreach ($workshops as $row) 
	{	
		if ($row['lecturer'])
			$row['status'] = genderize('prowadząc%', $user['gender']);
		else
			$row['status'] = genderize(enumParticipantStatus($row['participant'])->description, $user['gender']);
		if (!empty($row['admincomment']))
			$row['comment'] = '<a '. getTipJS($row['admincomment']) .'>(komentarz)</a>';
		else 
			$row['comment'] = '';
		$template = new SimpleTemplate($row);
		?><tr>
			<td><b><a href="showWorkshop(%wid%)">%title%</a></b></td>
			<td><a href="showTaskSolutions(%wid%,%uid%)" title="szczegóły">%status%</a></td>
			<td>%points%</td>
			<td>%comment%</td>
		</tr><?php
		$user['workshops'] .= $template->finish();
	}
	$user['workshops'] .= '</table>';
	

	$PAGE->title = $user['name'] . ' - kwalifikacja';
	if (userCan('adminUsers'))
		$PAGE->headerTitle = getUserHeader($uid, $user['name'], 'editUserStatus'); 
	$template = new SimpleTemplate($user);	
	 ?>
		<span class="left">%badge% (%login%) &nbsp; %email%</span>
		<span class="right">%school% - %maturayeartext%</span><br/>
		
		<h3 onclick='$("#zaint_sign").toggle(); $("#zaint").toggle("fast");'>			
			 <span id='zaint_sign'>+</span> Zainteresowania</h3>
		<div class="descriptionBox" id="zaint" style="display:none">%zainteresowania%</div>
		<h3>List motywacyjny</h3>
		<div class="descriptionBox">%motivationletter%</div>
		<h3>Proponowany referat</h3>
		%proponowanyreferat%
		<h3>Warsztaty</h3>
		%workshops%
		<br/>
		
		<form method="post" action="editUserStatusForm(%uid%)">
			<h3 style="display: inline">Jadąc%genderya%</h3>
			<input type="checkbox" name="jadący" value="1" %jadący%>
			<input type="submit" name="submitted" value="zapisz" />
		</form>
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function actionEditUserStatusForm($uid)
{
	global $DB, $PAGE;
	$uid = intval($uid);
	if (!userCan('adminUsers'))  throw new PolicyException();
	
	$oldj = isset($DB->user_roles[array('uid'=>$uid,'role'=>'jadący')]);
	$newj = !empty($_POST['jadący']);
	if (!$oldj && $newj)
	{
		$DB->user_roles[]= array('uid'=>$uid,'role'=>'jadący');
		$DB->edition_user(getOption('currentEdition'), $uid)->update(array('qualified'=>1));
		logUser('set jadący 1', $uid);
		$PAGE->addMessage('Pomyślnie dodano do jadących.', 'success');
	}
	else if ($oldj && !$newj)
	{
		$DB->user_roles[array($uid,'jadący')]->delete();
		$DB->edition_user(getOption('currentEdition'), $uid)->update(array('qualified'=>0));
		logUser('set jadący 0', $uid);		
		$PAGE->addMessage('Pomyślnie usunięto z jadących.', 'success');			
	}
	else
		$PAGE->addMessage('Pozostawiono status.');		
	actionEditUserStatus($uid);	
}


function getUserHeader($uid, $name, $action)
{
	global $DB;
	$prev = $DB->query('SELECT MAX(uid) FROM table_users WHERE uid<$1', $uid)->fetch();
	$next = $DB->query('SELECT MIN(uid) FROM table_users WHERE uid>$1', $uid)->fetch();
	
	$s = "<a class='back' href='adminUsers'>wróć do listy</a>";
	if (is_string($next))
		$s = "<a class='back' href='$action($next)' title='następny'>→</a>". $s;
	if (is_string($prev))
		$s .= "<a class='back' href='$action($prev)' title='poprzedni'>←</a>";
	$s .= "<h2>$name<div class='tabs'>";
	$actions = array(
		'editProfile' => 'profil',
		'editAdditionalInfo' => 'dodatkowe dane',
		'editUserStatus' => 'status kwalifikacji'
	);
	foreach ($actions as $a=>$aname)
		$s .= "<a href='$a($uid)'". (($a == $action)?" class='selected'":"") .">$aname</a>";
	$s .= "</div></h2>";
	return $s;	
}


function actionListPersonalData()
{
	global $USER, $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$PAGE->title = 'Lista danych personalnych';
	if (!isset($_GET['print']))
	{
		$PAGE->content .= '<a class="right" href="?print">wersja do druku</a><br/>';	
		$users = $DB->query('
			SELECT u.name, u.email FROM table_users u
			WHERE EXISTS (SELECT * FROM table_user_roles r WHERE r.uid=u.uid AND r.role=\'jadący\')
				AND u.pesel IS NULL
			ORDER BY regexp_replace(u.name,\'.*\ ([^\ ]+)\',\'\\\\1\')
		');
		$PAGE->content .= 'Osoby, które nie wypełniły dodatkowych danych: ';
		$emails = array();
		foreach ($users as $user)
			$emails[]= $user['name'] .' &lt;'. $user['email']. '&gt;';
		$PAGE->content .= implode(', ',$emails) .'.';
	}
	$users = $DB->query('
		SELECT u.uid, u.name, u.telephone, u.parenttelephone, u.pesel, u.address
		FROM table_users u
		WHERE EXISTS (SELECT * FROM table_user_roles r WHERE r.uid=u.uid AND r.role=\'jadący\')
		ORDER BY regexp_replace(u.name,\'.*\ ([^\ ]+)\',\'\\\\1\')	
	');	
	$PAGE->content .= '<table class="bordered"><thead><tr><th>imię i nazwisko</th><th>komórka</th>'.
		'<th>telefon do rodziców</th><th>PESEL</th><th>adres zameldowania</th></tr></thead>';
	foreach ($users as $user)
		$PAGE->content .= formatAssoc(
			'<tr class="'. alternate('even','odd') .'"><td>%name%</td><td>%telephone%</td>'.
			'<td>%parenttelephone%</td><td>%pesel%</td><td>%address%</td></tr>',
			$user
		);
	$PAGE->content .= '</table>';
}

function actionListArrivalData($comments = true)
{
	//tabelkę: imię, nazwisko, komórka, data przyjazdu, data wyjazdu, gdzie zbiórka
	global $USER, $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$PAGE->title = 'Lista danych dot. dojazdu';
	if (!isset($_GET['print']))
	{
		$PAGE->content .= '<a class="right" href="?print">wersja do druku</a>';	
		if ($comments)
			$PAGE->content .= '<a href="listArrivalData(0)">bez uwag</a>';
		else
			$PAGE->content .= '<a href="listArrivalData(1)">z uwagami</a>';
	}
	$users = $DB->query('
		SELECT u.uid, u.name, u.telephone, u.parenttelephone, u.staybegin, u.stayend, u.gatherplace, u.comments, u.lastmodification
		FROM table_users u
		WHERE EXISTS (SELECT * FROM table_user_roles r WHERE r.uid=u.uid AND r.role=\'jadący\')
		ORDER BY u.gatherplace DESC, u.staybegin, regexp_replace(u.name,\'.*\ ([^\ ]+)\',\'\\\\1\')	
	');	
		
	$PAGE->content .= '<table class="bordered"><thead><tr><th>imię i nazwisko</th><th>komórka</th>'.
		'<th>przyjazd</th><th>wyjazd</th><th>zbiórka</th>'.
		($comments?'<th>uwagi</th><th>ost. zmiana</th>':'') .
		'</tr></thead>';
	foreach ($users as $user)
	{
		$starttime = strtotime('2011/08/08 00:00');
		if (!is_null($user['staybegin']))
			$user['staybegin'] = strftime('%a %d. %H:00', $starttime + 3600*$user['staybegin']);
		if (!is_null($user['stayend']))
			$user['stayend']   = strftime('%a %d. %H:00', $starttime + 3600*$user['stayend']  );
		if (!is_null($user['lastmodification']))
		{
			$t = $user['lastmodification'];			
			$user['lastmodification'] = strftime('%Y-%m-%d', $t);
			// Highlight if older than 300 days
			if ($t < time() -  300 * 24 * 60 * 60)
				$user['lastmodification'] = '<span style="color:#a00">'. $user['lastmodification'] . '</span>';
		}
		if ($user['gatherplace'] == 'none')
			$user['gatherplace'] = ' - ';
		else
			$user['gatherplace'] = ucfirst($user['gatherplace']);
		$PAGE->content .= formatAssoc(
			'<tr class="'. alternate('even','odd') .'"><td>%name%</td><td>%telephone%</td>'.
			'<td>%staybegin%</td><td>%stayend%</td><td>%gatherplace%</td>'.
			($comments?'<td>%comments%</td><td>%lastmodification%</td>':'').
			'</tr>',
			$user
		);
	}
	$PAGE->content .= '</table>';
}

function actionListDailyCounts()
{
	global $DB,$PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$PAGE->title = 'Lista danych dot. posiłków';
	
	if (!isset($_GET['print']))
		$PAGE->content .= '<a class="right" href="?print">wersja do druku</a>';
	
	$DB->query('SELECT COUNT(*) FROM w1_users u
		WHERE EXISTS (SELECT * FROM table_user_roles r WHERE r.uid=u.uid AND r.role=\'jadący\')
			AND u.staybegin IS NULL');				
	$PAGE->content .= 'Jadących, którzy nie określili czasu pobytu: '. $DB->fetch();
	$PAGE->content .= ' (patrz <a href="listPersonalData">lista danych personalnych</a>)<br/>';
	$PAGE->content .= '<i>nocleg</i> := wlicza tych, którzy przyjechali na kolację,<br/>
		&nbsp; &nbsp; ale nie tych, którzy odjeżdżają po kolacji<br/>
		&nbsp; &nbsp; (jeżeli jednak zakładamy, że odjeżdzają tuż przed śniadaniem, <br/>
		&nbsp; &nbsp; a nie tuż po kolacji, to należy patrzeć na kolumnę <i>kolacja</i>)';
	$PAGE->content .= '<table class="bordered" style="width:auto">';
	$PAGE->content .= '<thead><tr><th>dzień</th>';
	$starttime = strtotime('2011/08/08 00:00');
	$mealhours = array(9=>'śniadanie', 14=>'obiad', 19=>'kolacja');	
	foreach ($mealhours as $meal)
		$PAGE->content .= '<th>'. $meal .'</th>';
	$PAGE->content .= '<th>nocleg</th></tr></thead>';
	for ($i=0; $i<=10; $i++) // Warsztaty mają 11 dni [0..10].
	{
		$PAGE->content .= '<tr class="'. alternate('even', 'odd') .'">';
		$PAGE->content .= '<td>'. strftime("%a %e.", $starttime+($i*24+9)*3600) .'</td>';
		foreach ($mealhours as $h=>$meal)
		{
			$DB->query('SELECT COUNT(*) FROM w1_users u
				WHERE EXISTS (SELECT * FROM table_user_roles r WHERE r.uid=u.uid AND r.role=\'jadący\')
					AND u.staybegin<=$1 AND u.stayend>=$1
					AND u.isselfcatered=0',
					$i*24+$h);				
			$PAGE->content .= '<td>'. $DB->fetch() .'</td>';
		}
		$DB->query('SELECT COUNT(*) FROM w1_users u
			WHERE EXISTS (SELECT * FROM table_user_roles r WHERE r.uid=u.uid AND r.role=\'jadący\')
				AND u.staybegin<=$1 AND u.stayend>$1
				AND u.isselfcatered=0',
				$i*24+19);				
		$PAGE->content .= '<td>'. $DB->fetch() .'</td>';				

		$PAGE->content .= '</tr>';
	}
	$PAGE->content .= '</table>';
}
