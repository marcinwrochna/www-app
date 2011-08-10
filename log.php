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



function logUser($type, $what=-1)
{
	global $DB, $USER;
	$DB->log[] = array(
		'uid' => $USER['uid'],
		'ip' => $_SERVER['REMOTE_ADDR'],
		'time' => time(),
		'type' => $type,
		'what' => intval($what)
	);
}

function actionShowLog()
{
	global $DB, $USER;
	if (!userCan('showLog'))  return;

	$authors = array();
	$res = $DB->query('SELECT uid,name,login FROM table_users');
	foreach ($res as $row)
		$authors[$row['uid']] = $row;

	$res = $DB->query('SELECT ip,uid,time,type,what FROM table_log ORDER BY time DESC LIMIT 50');
	$rows = '';
	foreach ($res as $row)
	{
		if (array_key_exists($row['uid'], $authors))
			$author = $authors[$row['uid']];
		else
			$author = array('name'=>'', 'login'=>'?');
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
	$template = new SimpleTemplate(array('rows' => $rows));
	?>
		<div class="contentBox panel userLog">
		<table>
			<thead><tr><th>{{when}}</th><th>{{who}}</th><th>{{what}}</th><th>{{which}}</th></tr></thead>
			%rows%
		</table>
		</div>
	<?php
	echo $template->finish(true);
}

// DEPRECATED - I didn't use that since I don't know when...
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

	// INSERT
}
