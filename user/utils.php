<?php
/*
 * user/utils.php
 */

function userIs($role)
{
	global $USER;
	return in_array($role, $USER['roles']);
}

function userCan($action, $owner=false)
{
	global $USER, $DB;
	$roles = $USER['roles'];
	if ($owner === $USER['uid'] || (is_array($owner) && in_array($USER['uid'], $owner)))
		if (in_array('registered', $USER['roles']))
			$roles[]= 'owner';
	$required = $DB->query('SELECT role FROM table_role_permissions WHERE action=$1', $action);
	return (count(array_intersect($roles,$required->fetch_column())) > 0);
}

function assertProfileFilled($quiet = false)
{
	global $USER, $DB, $PAGE;
	$user = $DB->users[$USER['uid']]->assoc('school,maturayear,zainteresowania');

	if (empty($user['maturayear']) || empty($user['school']) || empty($user['zainteresowania']))
	{
		if (!$quiet)
			$PAGE->addMessage(_('Fill in <a href="editProfile">your profile</a> first!'), 'userError');
		return false;
	}
	return true;
}

function gender($m='y', $f='a', $gender='user')
{
	global $USER;
	if ($gender === 'user')  $gender = $USER['gender'];
	return ($gender==='f'?$f:$m);
}

function genderize($s, $gender='user')
{
	$s = str_replace('%ś', gender('eś','aś',$gender), $s);
	$s = str_replace('%', gender('y','a',$gender), $s);
	return $s;
}

function getName($uid, $default='')
{
	global $DB;
	if (!isset($DB->users[intval($uid)]))
		return $default;
	else
		return $DB->users[intval($uid)]->get('name');
}

function getUserBadge($uid, $email=false, $default='?')
{
	global $USER, $DB;
	if (!isset($DB->users[intval($uid)]))
		return $default;
	$name = $DB->users[intval($uid)]->get('name');
	$icon = 'user-blue.gif';
	if ($uid == $USER['uid'])  $icon = 'user-green.gif';

	if (userCan('editProfile', $uid))
		$icon = getIcon($icon, _('edit profile'), 'editProfile('. $uid .')');
	else
		$icon = getIcon($icon, _('profile on wikidot'), // TODO profile on wikidot exists?
			'http://warsztatywww.wikidot.com/'. urlencode($name));
	$result = "$icon $name";
	if ($email)  $result .= ' &lt;'. $DB->users[$uid]->get('email') .'&gt;';
	return $result;
}

function passHash($password)
{
	// The root password is hashed this way so it can be set in config.php,
	// installation scripts only have to write 'rootpassword' instead of a sha1().
	if ($password == ROOT_PASSWORD)
		return 'rootpassword';
	return sha1('SALAD'. $password);
}
