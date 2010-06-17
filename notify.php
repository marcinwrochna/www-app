<?php

define('STANDALONE', true);
include_once('common.php');

/* For each workshop with tasks, for each participant,
 * if their last solution was at least an hour ago,
 * send an email to lecturers.
 */

$time = time() - 60*60;

$result = db_query('SELECT wid, uid FROM table_task_solutions '.
	'WHERE notified<1 GROUP BY wid,uid HAVING MAX(submitted)<'. $time);
$count = 0;
while (($row = db_fetch_assoc($result)) !== false)
{
	$count++;
	$wid = $row['wid'];
	$uid = $row['uid'];
	
	$address = array();
	$r = db_query('SELECT u.name, u.email FROM table_users u, table_workshop_user wu
		WHERE u.uid=wu.uid AND lecturer>0 AND wid='. $wid);
	$r = db_fetch_all($r);
	foreach ($r as $lecturer)
		$address[]= array($lecturer['name'], $lecturer['email']);
	
	$workshop = db_get('workshops', $wid, 'title');
	$user = db_get('users', $uid, 'name, login, email, gender');
	$title = 'Rozwiązania zadań ('. $user['name'] .')';
	$email = $user['name']. ' ('. $user['email'] .') wysłał'. gender('','a',$user['gender']);
	$email .= " rozwiązania zadań kwalifikacyjnych do warsztatów\n$workshop\n";
	$email .= "Zobacz i oceń na\n";
	$email .= "http://warsztatywww.nstrefa.pl/?action=showTaskSolutions&wid=$wid&uid=$uid\n\n";
	$email .= "--------\n(poniżej rozwiązania, ale bez obrazków, linków i HTMLu w ogóle)\n\n";
	$r = db_query('SELECT tid, submitted, solution FROM table_task_solutions WHERE '.
	 "wid=$wid AND uid=$uid AND notified<1");
	$r = db_fetch_all($r);
	foreach ($r as $task)
	{
		$email .= "Zadanie ". $task['tid'] .". (". strftime('%a %T', $task['submitted']) .")\n";
		$email .= strip_tags($task['solution']);
		$email .= "\n\n";
	}	
	db_update('task_solutions', "WHERE wid=$wid AND uid=$uid AND notified<1", array('notified'=>1));
	
	sendMail($title, $email, $address);
	sendMail($title .' [debug]', $email, 'mwrochna@gmail.com'); // Debuguję.
	$address = json_encode($address);
	echo "Sent email to $address<br/>\n";
}
echo "$count emails in total.<br/>\n";
	
