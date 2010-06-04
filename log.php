<?php
/*
	log.php defines logVisitor() and logUser(), which log visitors and authors, 
	trying to remember them. Visitors are identified by the first time seen.
	Included in common.php.
	Data saved:
		ip inet,
		logid int,
		time int,
		type varchar(31), // index/panel
		what int  // file_id/panel_id
		referer varchar(1023),
		agent varchar(1023),
		acclang varchar(1023),
		request varchar(1023),
		uid int,
		details varchar(1023)
*/

function logVisitor($type, $what, $details='')
{
	return;
		
	global $USER;
	if (isset($USER['uid']))  $uid = intval($USER['uid']);
	else $uid = -1;
	$time = time();
	if (!isset($_SESSION['logid']))  $_SESSION['logid'] = $time;

	// Fill the data to remember into $params, to pass it safely through pg_query_params.
	$params = array();
	foreach (array('REMOTE_ADDR','HTTP_REFERER','HTTP_USER_AGENT','HTTP_ACCEPT_LANGUAGE','REQUEST_URI') as $var)
	{
		if (isset($_SERVER[$var])) $params[]= $_SERVER[$var];
		else $params[]= null;
	}
	$params[]= $details;
	
	/*@db_query_params(
		"
			INSERT INTO ". TABLE_LOG ." VALUES
			(
				$1,
				". intval($_SESSION['logid']) .",
				". $time .",
				'". $type ."',
				". $what .",
				$2,$3,$4,$5,". $uid ." ,$6
			)
		", 
		$params,
		''
	);*/
}

function logUser($type, $what=-1)
{
	global $USER;
	db_insert('log', array(
		'uid' => $USER['uid'],
		'ip' => $_SERVER['REMOTE_ADDR'],
		'time' => time(),
		'type' => $type,
		'what' => intval($what)
	), 'Nie udało się dopisać do logu.');
}

function actionShowLog()
{
	global $USER;
	if (!userCan('showLog'))  return;
	
	$res = db_query('SELECT uid,name,login FROM table_users', 'Nie udało się otrzymać informacji o autorach.');
	$authors = array();
	while ($row = db_fetch_assoc($res))  $authors[$row['uid']] = $row;
	
	$res = db_query('SELECT ip,uid,time,type,what FROM table_log ORDER BY time DESC LIMIT 50', 'Nie udało się wczytać logu autorów.');
	$rows = '';
	while ($row = db_fetch_assoc($res))
	{
		if (array_key_exists($row['uid'], $authors))  $author=$authors[$row['uid']];
		else  $author = array('name'=>'', 'login'=>'?');
		$rows .= '<tr>';
		$rows .= '<td>'.strftime('%y-%m-%d %H:%M',$row['time']).'</td>';
		$rows .= '<td>'.$author['name'].' ('.$author['login'].')</td>';
		$rows .= '<td>'.$row['type'].'</td>';
		$rows .= '<td>'.$row['what'].'</td>';
		if (DEBUG)  $rows .= '<td>'. $row['ip'] .'</td>';
		$rows .= '</tr>';
	}
	
	global $PAGE;
	$PAGE->title = 'Log';
	$template = new SimpleTemplate(array('rows'=>$rows));
	?>
		<div class="contentBox panel userLog">
		<h3>Log</h3>
		<table>
			<thead><th>kiedy</th><th>kto</th><th>co</th><th>które</th></thead>
			%rows%
		</table>
		</div>
	<?php
	$PAGE->content .= $template->finish();
}
