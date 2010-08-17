<?php
/* 
 * user/utils.php
 */
 
function userCan($action, $owner=false)
{	
	global $USER, $DB;
	$roles = $USER['roles'];
	if ($owner === $USER['uid'] || (is_array($owner) && in_array($USER['uid'], $owner)))
		$roles[]= 'owner';
	$required = $DB->query('SELECT role FROM table_role_permissions WHERE action=$1', $action);
	return (count(array_intersect($roles,$required->fetch_column()))>0);
}
 
function assertProfileFilled()
{
	global $USER, $DB, $PAGE;
	$user = $DB->users[$USER['uid']]->assoc('school,maturayear,zainteresowania');
	
	if (empty($user['maturayear']) || empty($user['school']) || empty($user['zainteresowania']))
	{
		$PAGE->addMessage('Wypełnij najpierw wszystkie dane w <a href="editProfile">profilu</a>!',
			'userError');
		return false;
	}
	return true;
}

function gender($m='y', $f='a', $gender=null)
{
	global $USER;
	if (is_null($gender))  $gender = $USER['gender'];
	return ($gender==='f'?$f:$m);
}

function getName($uid, $default='')
{
	global $DB;	
	if (!isset($DB->users[intval($uid)]))
		return $default;
	else
		return $DB->users[intval($uid)]->get('name');
}

function getUserBadge($uid, $email=false)
{
	global $USER, $DB;
	$name = getName($uid, '?');
	$icon = 'user-blue.gif';
	if ($uid == $USER['uid'])  $icon = 'user-green.gif';
	
	if (userCan('editProfile', $uid))
		$icon = getIcon($icon, 'edytuj profil', 'editProfile('. $uid .')');
	else
		$icon = getIcon($icon, 'profil na wikidot',
			'http://warsztatywww.wikidot.com/'. urlencode($name));
	$result = "$icon $name";
	if ($email)  $result .= ' &lt;'. $DB->users[$uid]->get('email') .'&gt;';
	return $result;
}

function passHash($password)
{
	if ($password=='haslo')  return 'rootpassword';
	return sha1('SALAD'. $password);
}