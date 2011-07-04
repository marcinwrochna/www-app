<?php
include_once('common.php');
require_once('utils.php');
require_once('template.php');
require_once('enum.php');
require_once('user/utils.php');

/* For each workshop with tasks, for each participant,
 * if their last solution was at least an hour ago,
 * send an email to lecturers.
 */

$time = time() - 60*60;
$result = $DB->query('SELECT wid, uid FROM table_task_solutions '.
	'WHERE notified<1 GROUP BY wid,uid HAVING MAX(submitted)<'. $time);
$count = 0;
while (($row = $result->fetch_assoc()) !== false)
{
	$count++;
	$wid = $row['wid'];
	$uid = $row['uid'];
	
	$address = array();
	$r = $DB->query('SELECT u.name, u.email FROM table_users u, table_workshop_user wu
		WHERE u.uid=wu.uid AND participant=$1 AND wid=$2',
		enumParticipantStatus('lecturer')->id, $wid);
	$r = $DB->fetch_all($r);
	foreach ($r as $lecturer)
		$address[]= array($lecturer['name'], $lecturer['email']);
	
	$workshop = $DB->workshops($wid)->get('title');
	$user = $DB->users($uid)->get('name, login, email, gender');
	$title = 'Rozwiązania zadań ('. $user['name'] .')';
	$email = $user['name']. ' ('. $user['email'] .') wysłał'. gender('','a',$user['gender']);
	$email .= " rozwiązania zadań kwalifikacyjnych do warsztatów\n$workshop\n";
	$email .= "Zobacz i oceń na\n";
	$email .= "http://warsztatywww.nstrefa.pl/showTaskSolutions($wid;$uid)\n\n";
	$email .= "--------\n(poniżej rozwiązania, ale bez obrazków, linków i HTMLu w ogóle)\n\n";
	$r = $DB->query('SELECT tid, submitted, solution FROM table_task_solutions WHERE '.
	 "wid=$wid AND uid=$uid AND notified<1");
	$r = $DB->fetch_all($r);
	foreach ($r as $task)
	{
		$email .= "Zadanie ". $task['tid'] .". (". strftime('%a %T', $task['submitted']) .")\n";
		$email .= strip_tags($task['solution']);
		$email .= "\n\n";
	}
	$DB->query('UPDATE table_task_solutions SET notified = 1 WHERE wid=$1 AND uid=$2 AND notified<1', $wid,$uid);
	
	sendMail($title, $email, $address);
	$address = json_encode($address);
	echo "Sent email to $address<br/>\n";
}
echo "$count emails in total.<br/>\n";
	
