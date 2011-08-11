<?php
/*
 * user/utils.php
 */

/**
 * Returns whether current user has given role.
 * @param $role a role name, see getUserRoles().
 */
function userIs($role)
{
	global $USER;
	return in_array($role, $USER['roles']);
}

/**
 * Return whether current user has rights to do given action.
 * table_role_permissions contains for each action several entries -
 * each entry is a concatenation (space-separated) of required roles,
 * if any one of these entries is fully fulfilled, permission is granted.
 * @param $action the action to be checked.
 * @param $owners a single uid or an array of uids - if the current user belongs here,
 * 	the role 'owner' is added.
 */
function userCan($action, $owners=false)
{
	global $USER, $DB;
	$roles = $USER['roles'];
	if ($owners === $USER['uid'] || (is_array($owners) && in_array($USER['uid'], $owners)))
		if (in_array('registered', $USER['roles']))
			$roles[]= 'owner';
	$required = $DB->query('SELECT role FROM table_role_permissions WHERE action=$1', $action);
	foreach ($required as $requirements)
		if (!count(array_diff(explode(' ', $requirements['role']), $roles)))
			return true;
	return false;
}

/**
 * Returns whether the user filled his basic profile information, displays a message if not.
 * @param $quiet (optional) Defaults to false. If true, no message is displayed.
 */
function assertProfileFilled($quiet = false)
{
	global $USER, $DB, $PAGE;
	$user = $DB->users[$USER['uid']]->assoc('school,graduationyear,interests');

	if (empty($user['graduationyear']) || empty($user['school']) || empty($user['interests']))
	{
		if (!$quiet)
			$PAGE->addMessage(_('Fill in <a href="editProfile">your profile</a> first!'), 'userError');
		return false;
	}
	return true;
}

function gender($m='y', $f='a', $gender='user') // TODO it's only used in genderize().
{
	global $USER;
	if ($gender === 'user')
		$gender = $USER['gender'];
	return (($gender === 'f') ? $f : $m);
}

/**
 * Inflect words in a translated string to account for a user's grammatical gender.
 * English doesn't need it, so the string is left unchanged.
 * Currently only Polish is supported - if a string returned by gettext() contains
 * the special character %, a part will be replaced with the appropriate suffix.
 * @param $s string returned by gettext() (_(), n_() or ngettext()).
 * @param $gender (optional) Defaults to $USER['gender']. Should be 'f' or 'm',
 * 	other strings are treated as 'm'.
 */
function genderize($s, $gender='user')
{
	$s = str_replace('%ś', gender('eś','aś',$gender), $s);
	$s = str_replace('%', gender('y','a',$gender), $s);
	return $s;
}

/**
 * Returns a user's name with an icon linking to his profile.
 * @param $uid an uid.
 * @param $email (optional) Defaults to false. If true, his email is also displayed.
 * @param $default (optional) Defaults to '?'. The name to use for invalid uid (e.g. in old logs).
 */
function getUserBadge($uid, $email=false, $default='?')
{
	global $USER, $DB;
	if (!isset($DB->users[intval($uid)]))
		return $default;
	$name = $DB->users[intval($uid)]->get('name');
	$icon = 'user-blue.gif';
	if ($uid == $USER['uid'])
		$icon = 'user-green.gif';

	if (userCan('editProfile', $uid))
		$icon = getIcon($icon, _('edit profile'), 'editProfile('. $uid .')');
	else
		$icon = getIcon($icon, _('profile on wikidot'), // TODO profile on wikidot exists?
			'http://warsztatywww.wikidot.com/'. urlencode($name));
	$result = "$icon $name";
	if ($email)  $result .= ' &lt;'. $DB->users[$uid]->get('email') .'&gt;';
	return $result;
}

/**
 * Returns an array of roles given user has.
 * Possible roles are 'admin','tutor' (from table_user_roles),
 * 	'lecturer', 'candidate', 'qualified' (from table_edition_users),
 * 	'registered', 'public', 'owner' (these are not set here, but in userCan() or getUser()).
 * @param $uid an uid.
 */
function getUserRoles($uid)
{
	global $DB, $USER;
	$DB->query('SELECT role FROM table_user_roles WHERE uid=$1', $uid);
	$roles = $DB->fetch_column();
	$r = $DB->query('SELECT lecturer, qualified FROM table_edition_users WHERE edition=$1 AND uid=$2',
		getOption('currentEdition'), $uid);
	if ($r->count())
	{
		$status = $DB->fetch_assoc();
		$roles[]= ($status['lecturer']) ? 'lecturer' : 'candidate';
		if ($status['qualified'])
			$roles[]= 'qualified';
	}
	return $roles;
}

/**
 * Returns a hash used for storing passwords. Store the value 'rootpassword' instead of a real hash
 * whenever you want someone's password to be the one defined in config.php's const ROOT_PASSWORD.
 */
function passHash($password)
{
	// The root password is hashed this way so it can be set in config.php,
	// installation scripts only have to write 'rootpassword' instead of a sha1().
	if ($password === ROOT_PASSWORD)
		return 'rootpassword';
	return sha1('SALAD'. $password);
}
