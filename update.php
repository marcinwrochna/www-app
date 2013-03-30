<?php
	/* update.php
	*/

function setVersion($v)
{
	global $PAGE, $DB;
	$DB->options['version']->update(array('value' => $v));
	logUser('auto update', $v);
	$PAGE->addMessage(sprintf(_('Application database has just been updated to version %d.'), $v));
}

function insertPermission($role, $action)
{
	global $DB;
	$DB->role_permissions[]= array('role' => $role, 'action' => $action);
}

global $DB;
$version = intval(getOption('version'));
switch ($version)
{
	case(23):
		//db_query('ALTER TABLE table_options ADD PRIMARY KEY (name)');
		//db_query('ALTER TABLE table_role_permissions ADD PRIMARY KEY (role,action)');
		/*db_query('ALTER TABLE table_task_solutions ADD PRIMARY KEY (wid,tid,uid,submitted)');
		db_query('ALTER TABLE table_tasks ADD PRIMARY KEY (wid,tid)');
		db_query('ALTER TABLE table_user_roles ADD PRIMARY KEY (uid,role)');
		db_query('ALTER TABLE table_users ADD PRIMARY KEY (uid)');
		db_query('ALTER TABLE table_workshop_domain ADD PRIMARY KEY (wid,domain)');
		db_query('ALTER TABLE table_workshops ADD PRIMARY KEY (wid)');*/
		//db_query('ALTER TABLE table_workshop_user ADD PRIMARY KEY (wid,uid)');
		//db_query('ALTER TABLE table_test ADD PRIMARY KEY (id)');
		setVersion(24);
	case (24):
		insertPermission('admin', 'showCorrelation');
		setVersion(25);
	case(25):
		//$DB->query('ALTER TABLE table_users ADD UNIQUE(login)');
		//$DB->query('ALTER TABLE table_users ADD UNIQUE(email)');
	case(26):
		$DB->query('UPDATE table_workshop_user SET participant=$1 WHERE lecturer>0',
			enumParticipantStatus('lecturer')->id);
		foreach (enumSubject() as $subjectId => $subjectItem)
			$DB->query('UPDATE table_workshop_domain SET domain=$2 WHERE domain=$1',
				$subjectItem->description, $subjectId);
		setVersion(27);
	case(27):
		insertPermission('admin', 'autoQualifyForWorkshop');
		insertPermission('kadra', 'autoQualifyForWorkshop');
		setVersion(28);
	case(28):
		$DB->options[]= array(
			'name' => 'gmailOAuthEmail',
			'description' => 'konto gmail używane do mailingu',
			'value' => 'mwrochna@gmail.com',
			'type' => 'text'
		);
		$DB->options[]= array(
			'name' => 'gmailOAuthAccessToken',
			'description' => 'accessToken konta gmail '.
				'<small><a href="fetchGmailOAuthAccessToken">[reautoryzuj]</a></small>',
			'value' => null,
			'type' =>'readonly'
		);
		setVersion(29);
	case(29):
		insertPermission('admin', 'viewTutoringApplications');
		insertPermission('tutor', 'viewTutoringApplications');
		setVersion(30);
	case(30):
		//insertPermission('admin', 'impersonate');
		//insertPermission('registered', 'editTutoringApplication');
		setVersion(31);
	case(31):
		$DB->query('ALTER TABLE table_workshops ADD COLUMN edition int');
		$DB->query('UPDATE table_workshops SET edition=6');
		$DB->query('CREATE TABLE table_editions (edition int, name varchar(255))');
		$DB->query('ALTER TABLE table_editions ADD PRIMARY KEY (edition)');
		$DB->query('INSERT INTO table_editions VALUES (6, \'WWW6\')');
		$DB->options[]= array(
			'name' => 'currentEdition',
			'description' => 'obecna edycja warsztatów',
			'value' => '7',
			'type' => 'int'
		);
		setVersion(32);
	case(32):
		/*$DB->query('INSERT INTO table_editions VALUES (7, \'WWW7\')');
		$DB->query('CREATE TABLE table_edition_user (edition int, uid int, qualified int, lecturer int)');
		$DB->query('ALTER TABLE table_edition_user ADD PRIMARY KEY (edition, uid)');
		$DB->query('INSERT INTO table_edition_user (SELECT 6, uid, 0, 0 FROM table_users)');*/
		$DB->query('UPDATE table_edition_user SET qualified=
			(SELECT count(*) FROM table_user_roles ur WHERE ur.uid=uid AND ur.role=\'jadący\')');
		$DB->query('UPDATE table_edition_user SET lecturer=
			(SELECT count(*) FROM table_user_roles ur WHERE ur.uid=uid AND ur.role=\'akadra\')');
			/*(SELECT count(*) FROM table_workshops w, table_workshop_user wu WHERE w.wid=wu.wid AND wu.uid=eu.uid
				AND wu.participant=5 AND w.status>2)');*/
		setVersion(33);
	case(33):
		$DB->query('DELETE FROM table_user_roles WHERE role=\'jadący\' OR role=\'akadra\' OR role=\'kadra\'');
		setVersion(34);
	case(34):
		insertPermission('registered', 'applyAsLecturer');
		$DB->query('ALTER TABLE table_workshops ADD COLUMN edition int');
		$DB->query('UPDATE table_workshops SET edition=6');
		$DB->query('DELETE FROM table_role_permissions WHERE action=\'signUpForWorkshop\' AND role=\'registered\'');
		insertPermission('kadra', 'signUpForWorkshop');
		setVersion(35);
	case(35):
		$DB->query('DELETE FROM table_user_roles WHERE role=\'uczestnik\'');
		setVersion(36);
	case(36):
		insertPermission('registered', 'applyAsParticipant');
		setVersion(37);
	case(37):
		$DB->query('UPDATE table_edition_user SET qualified=1 WHERE uid IN
			(SELECT wu.uid FROM table_workshop_user wu, table_workshops w
				WHERE w.wid=wu.wid AND w.edition=$1 AND wu.participant=$2
				AND w.status>=$3 ORDER BY w.wid
			)',
			getOption('currentEdition'), enumParticipantStatus('lecturer')->id, enumBlockStatus('accepted')->id);
		setVersion(38);
	case(38):
		$DB->query('DELETE FROM table_user_roles WHERE role=\'akadra\'');
		$DB->query('INSERT INTO table_user_roles (SELECT uid, \'akadra\' AS role FROM table_edition_user
			WHERE qualified=1 AND lecturer=1 AND edition=$1)', getOption('currentEdition'));
		setVersion(39);
	case(39):
		$DB->query('ALTER TABLE table_users ADD COLUMN lastmodification int');
		$DB->query('UPDATE table_users SET lastmodification =
			(SELECT MAX(l.time) FROM w1_log l WHERE l.type=\'user edit2\' AND l.uid=w1_users.uid)');
		setVersion(40);
	case(40):
		// Change type of table_users.comments to text.
		$DB->query('ALTER TABLE table_users ADD column tmp text');
		$DB->query('UPDATE table_users SET tmp = comments');
		$DB->query('ALTER TABLE table_users DROP column comments');
		$DB->query('ALTER TABLE table_users ADD column comments text');
		$DB->query('UPDATE table_users SET comments = tmp');
		$DB->query('ALTER TABLE table_users DROP column tmp');
		setVersion(41);
	case(41):
		$DB->query('ALTER TABLE table_users DROP COLUMN roles');
		$DB->query('DELETE FROM table_user_roles WHERE role=\'\'');
		insertPermission('uczestnik', 'editMotivationLetter');
		setVersion(42);
	case(42):
		$DB->query('UPDATE table_users SET motivationletter = motivationletter || \'<p><h4>Proponowany referat</h4>\' || proponowanyreferat || \'</p>\'');
		$DB->query('ALTER TABLE table_users DROP COLUMN proponowanyreferat');
		setVersion(43);
	case(43):
		$DB->query('ALTER TABLE table_users ADD COLUMN ordername varchar(255)');
		$DB->query('UPDATE table_users SET ordername=regexp_replace(name,\'(.*)\ ([^\ ]+)\',\'\\\\2\ \\\\1\ \')');
		$DB->query('UPDATE table_users SET ordername=ordername || uid');
		setVersion(44);
	case(44):
		$DB->query('ALTER TABLE table_workshops ADD COLUMN subjects_order integer');
		$DB->query('UPDATE table_workshops SET subjects_order=domain_order');
		$DB->query('ALTER TABLE table_workshops DROP COLUMN domain_order');
		setVersion(45);
	case(45):
		//$DB->query('ALTER TABLE table_workshops DROP COLUMN proposer_uid');
		//$DB->query('ALTER TABLE table_workshop_user DROP COLUMN lecturer');
		$DB->query('ALTER TABLE table_workshop_domain DROP COLUMN level');
		$DB->query('ALTER TABLE table_tasks DROP COLUMN inline');

		$DB->query('ALTER TABLE table_users ADD COLUMN graduationyear integer');
		$DB->query('UPDATE table_users SET graduationyear=maturayear');
		$DB->query('ALTER TABLE table_users DROP COLUMN maturayear');
		$DB->query('ALTER TABLE table_users ADD COLUMN interests text');
		$DB->query('UPDATE table_users SET interests=zainteresowania');
		$DB->query('ALTER TABLE table_users DROP COLUMN zainteresowania');
		$DB->query('ALTER TABLE table_users ADD COLUMN howdoyouknowus text');
		$DB->query('UPDATE table_users SET howdoyouknowus=skadwieszowww');
		$DB->query('ALTER TABLE table_users DROP COLUMN skadwieszowww');
		$DB->query('ALTER TABLE table_users ADD COLUMN tutorapplication text');
		$DB->query('UPDATE table_users SET tutorapplication=tutorapplication');
		$DB->query('ALTER TABLE table_users DROP COLUMN tutorapplication');
		setVersion(46);
	case(46):
		// Oh, postgres supports renames, how nice :P
		$DB->query('ALTER TABLE table_workshop_domain RENAME TO table_workshop_subjects');
			$DB->query('ALTER INDEX table_workshop_domain_pkey RENAME TO table_workshop_subjects_pkey');
			$DB->query('ALTER TABLE w1_workshop_domain_wid_seq RENAME TO table_workshop_subjects_wid_seq');
			// Postgres >8.1?
			//$DB->query('ALTER SEQUENCE w1_workshop_domain_wid_seq RENAME TO table_workshop_subjects_wid_seq');
		$DB->query('ALTER TABLE table_workshop_subjects RENAME COLUMN domain TO subject');
		$DB->query('ALTER TABLE table_edition_user RENAME TO table_edition_users');
			$DB->query('ALTER INDEX table_edition_user_pkey RENAME TO table_edition_users_pkey');
		$DB->query('ALTER TABLE table_workshop_user RENAME TO table_workshop_users');
			$DB->query('ALTER INDEX table_workshop_user_pkey RENAME TO table_workshop_users_pkey');
		$DB->query('ALTER TABLE table_edition_users ADD COLUMN staybegintime int');
		$DB->query('UPDATE table_edition_users SET staybegintime = (SELECT staybegin FROM table_users u WHERE table_edition_users.uid=u.uid)');
		$DB->query('ALTER TABLE table_edition_users ADD COLUMN stayendtime int');
		$DB->query('UPDATE table_edition_users SET stayendtime = (SELECT stayend FROM table_users u WHERE table_edition_users.uid=u.uid)');
		$DB->query('UPDATE table_edition_users SET staybegintime = staybegintime*60*60+$1 WHERE edition=7', strtotime('2011/08/08 00:00'));
		$DB->query('UPDATE table_edition_users SET stayendtime = stayendtime*60*60+$1 WHERE edition=7', strtotime('2011/08/08 00:00'));
		$DB->query('UPDATE table_edition_users SET staybegintime = staybegintime*60*60+$1 WHERE edition=6', strtotime('2010/08/19 00:00'));
		$DB->query('UPDATE table_edition_users SET stayendtime = stayendtime*60*60+$1 WHERE edition=6', strtotime('2010/08/19 00:00'));
		$DB->query('ALTER TABLE table_edition_users ADD COLUMN isselfcatered int');
		$DB->query('UPDATE table_edition_users SET isselfcatered = (SELECT isselfcatered FROM table_users u WHERE table_edition_users.uid=u.uid)');
		$DB->query('ALTER TABLE table_users DROP COLUMN isselfcatered');
		$DB->query('ALTER TABLE table_edition_users ADD COLUMN lastmodification int');
		$DB->query('UPDATE table_edition_users SET lastmodification = (SELECT lastmodification FROM table_users u WHERE table_edition_users.uid=u.uid)');
		$DB->query('ALTER TABLE table_users DROP COLUMN lastmodification');
		$DB->query('ALTER TABLE table_users DROP COLUMN staybegin');
		$DB->query('ALTER TABLE table_users DROP COLUMN stayend');
		setVersion(47);
	case(47):
		$DB->query('DELETE FROM w1_options WHERE name=\'newUserRoles\'');
		$DB->query('DELETE FROM w1_options WHERE name=\'domains\'');
		$DB->query('UPDATE w1_options SET description=\'current workshop edition\' WHERE name=\'currentEdition\'');
		$DB->query('UPDATE w1_options SET description=\'database version\' WHERE name=\'version\'');
		$DB->query('UPDATE w1_options SET description=\'min of motivation letter words\' WHERE name=\'motivationLetterWords\'');
		$DB->query('UPDATE w1_options SET description=\'main page top content\' WHERE name=\'homepage\'');
		$DB->query('UPDATE w1_options SET description=\'accessToken to gmail account <small><a href="fetchGmailOAuthAccessToken">[reauthorize]</a></small>\' WHERE name=\'gmailOAuthAccessToken\'');
		$DB->query('UPDATE w1_options SET description=\'gmail account used to send e-mails\' WHERE name=\'gmailOAuthEmail\'');
		setVersion(48);
	case(48):
		$DB->query('UPDATE w1_role_permissions SET role=\'lecturer\' WHERE role=\'kadra\'');
		$DB->query('UPDATE w1_role_permissions SET role=\'qualified lecturer\' WHERE role=\'akadra\'');
		$DB->query('UPDATE w1_role_permissions SET role=\'qualified\' WHERE role=\'jadący\'');
		$DB->query('UPDATE w1_role_permissions SET role=\'candidate\' WHERE role=\'uczestnik\'');
		setVersion(49);
	case(49):
		$DB->query('ALTER TABLE w1_editions ADD COLUMN begintime int');
		$DB->query('ALTER TABLE w1_editions ADD COLUMN endtime int');
		$DB->query('ALTER TABLE w1_editions ADD COLUMN importanthours varchar(50)');
		$DB->query('ALTER TABLE w1_editions ADD COLUMN proposaldeadline int');
		$DB->query('UPDATE w1_editions SET begintime=$1 WHERE edition=6', strtotime('2010/08/19 18:00'));
		$DB->query('UPDATE w1_editions SET begintime=$1 WHERE edition=7', strtotime('2011/08/08 18:00'));
		$DB->query('UPDATE w1_editions SET endtime=$1 WHERE edition=6', strtotime('2010/08/29 10:00'));
		$DB->query('UPDATE w1_editions SET endtime=$1 WHERE edition=7', strtotime('2011/08/18 10:00'));
		$DB->query('UPDATE w1_editions SET proposaldeadline=$1 WHERE edition=7', strtotime('2011/05/15 10:00'));
		$DB->query('UPDATE w1_editions SET importanthours=$1', '3 9 14 19');
		setVersion(50);
	case(50):
		insertPermission('admin', 'seeWorkshopStatus');
		insertPermission('qualified lecturer', 'seeWorkshopStatus');
		setVersion(51);
	case(51):
		$DB->query('ALTER TABLE w1_editions ADD COLUMN signupdeadline int');
		$DB->query('ALTER TABLE w1_editions ADD COLUMN solvedeadline int');
		$DB->query('ALTER TABLE w1_editions ADD COLUMN qualifydeadline int');
		$DB->query('ALTER TABLE w1_editions ADD COLUMN acceptdeadline int');
		$DB->query('ALTER TABLE w1_editions ADD COLUMN wikidotdeadline int');
		$DB->query('ALTER TABLE w1_editions ADD COLUMN taskdeadline int');
		$DB->query('ALTER TABLE w1_editions ADD COLUMN checkdeadline int');
		$DB->query('UPDATE w1_editions SET signupdeadline=$1 WHERE edition=9', strtotime('2013/06/23 10:00'));
		$DB->query('UPDATE w1_editions SET solvedeadline=$1 WHERE edition=9', strtotime('2013/07/07 10:00'));
		$DB->query('UPDATE w1_editions SET qualifydeadline=$1 WHERE edition=9', strtotime('2013/07/14 10:00'));
		$DB->query('UPDATE w1_editions SET acceptdeadline=$1 WHERE edition=9', strtotime('2013/05/09 10:00'));
		$DB->query('UPDATE w1_editions SET wikidotdeadline=$1 WHERE edition=9', strtotime('2013/05/15 10:00'));
		$DB->query('UPDATE w1_editions SET taskdeadline=$1 WHERE edition=9', strtotime('2013/05/24 10:00'));
		$DB->query('UPDATE w1_editions SET checkdeadline=$1 WHERE edition=9', strtotime('2013/07/10 10:00'));
		setVersion(52);
}
