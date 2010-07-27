<?php
	/* update.php
	*/
require_once('common.php');
	
$version = intval(getOption('version'));
function setVersion($v)
{
	db_update('options', 'WHERE name=\'version\'', array('value' => $v), 'Nie udało się zmienić wersji.');
	logUser('auto update', $v);
	showMessage('Aplikacja właśnie została z\'update\'owana do wersji '. $v);
}

function insertPermission($role, $action)
{
	db_query("INSERT INTO table_role_permissions VALUES('$role','$action')");
}

function recountDomainOrder()
{
	$r = db_query('SELECT wid FROM table_workshops');
	$wids = db_fetch_all_columns($r);
	foreach ($wids as $wid)
	{
		$r = db_query('SELECT domain FROM table_workshop_domain WHERE wid='. $wid);
		$domains = db_fetch_all_columns($r);
		$order = domainOrder($domains);
		db_update('workshops', 'WHERE wid='.$wid, array('domain_order'=>$order));
	}
}

switch ($version)
{
	case(0):
		db_update('options', 'WHERE name=\'version\'', array('type' => 'readonly'), 'Nie udało się zmienić wersji.');
		db_query('ALTER TABLE table_workshops ADD COLUMN status int');
		db_query('ALTER TABLE table_workshops DROP COLUMN accepted');
		setVersion(1);
	case(1):
		db_query('ALTER TABLE table_workshops DROP COLUMN materials');
		setVersion(2);
	case(2):
		db_query('ALTER TABLE table_workshops ADD COLUMN type int');
		db_query('ALTER TABLE table_workshops ADD COLUMN duration int');
		setVersion(3);
	case(3):
		db_query('ALTER TABLE table_workshops ADD COLUMN link varchar(255)');
		setVersion(4);
	case(4):
		db_query('CREATE TABLE table_workshop_user (
			wid int,
			uid int,
			lecturer int,
			participant int,
			PRIMARY KEY (wid,uid)
		)
		');
		db_query('INSERT INTO table_workshop_user SELECT wid, proposer_uid,1,0 FROM table_workshops');
		db_query('INSERT INTO table_workshop_user VALUES (23,35,1,0)');
		db_query('INSERT INTO table_workshop_user VALUES (42,39,1,0)');
		setVersion(5);
	case(5):
		db_query('CREATE TABLE table_user_roles (
			uid int,
			role varchar(50)
		)');
		db_query('CREATE TABLE table_role_permissions (
			role varchar(50),
			action varchar(50)
		)');
		db_query('INSERT INTO table_user_roles SELECT uid, \'admin\' FROM table_users WHERE roles&1>0');
		db_query('INSERT INTO table_user_roles SELECT uid, \'tutor\' FROM table_users WHERE roles&2>0');
		db_query('INSERT INTO table_user_roles SELECT uid, \'kadra\' FROM table_users WHERE roles&4>0');
		db_query('INSERT INTO table_user_roles SELECT uid, \'uczestnik\' FROM table_users');
		
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'editProfile\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'owner\',\'editProfile\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'listPublicWorkshops\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'kadra\',\'listPublicWorkshops\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'uczestnik\',\'listPublicWorkshops\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'kadra\',\'listOwnWorkshops\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'listOwnWorkshops\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'showWorkshop\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'kadra\',\'showWorkshop\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'uczestnik\',\'showWorkshop\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'showWorkshopDetails\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'owner\',\'showWorkshopDetails\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'createWorkshop\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'editWorkshop\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'owner\',\'editWorkshop\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'changeWorkshopStatus\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'adminUsers\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'listAllWorkshops\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'editOptions\')');
		db_query('INSERT INTO table_role_permissions VALUES(\'admin\',\'showLog\')');
		
		db_query('INSERT INTO table_options VALUES(\'newUserRoles\',\'role nowych użytkowników\',
			\'uczestnik\',\'text\')');
	
		//db_query('INSERT INTO table_role_permissions VALUES(\'uczestnik\',\'signUpForWorkshop\')');
		insertPermission('uczestnik', 'signUpForWorkshop');
		insertPermission('admin','showWorkshopParticipants');
		insertPermission('owner','showWorkshopParticipants');
		setVersion(6);
	case(6):
		db_query('ALTER TABLE table_users ADD COLUMN gender varchar(20)');		
		db_query('CREATE TABLE table_tasks(
			wid int,
			tid int,
			description text,
			inline int
		)');
		insertPermission('admin','editTasks');
		insertPermission('akadra','editTasks');		
		setVersion(7);		
	case(7):
		insertPermission('kadra','createWorkshop');
		setVersion(8);
	case(8):
		db_query('ALTER TABLE table_workshops ADD COLUMN domain_order int');
		recountDomainOrder();
		setVersion(9);
	case(9):
		db_query('CREATE TABLE table_uploads(
			filename varchar(255),
			realname varchar(255),
			size int,
			mimetype varchar(255),
			uploader int,
			utime int
		)');
		setVersion(10);
	case(10):
		recountDomainOrder();
		insertPermission('registered', 'showWorkshop');
		insertPermission('registered', 'listPublicWorkshops');
		insertPermission('registered', 'signUpForWorkshop');
		setVersion(11);
	case(11):
		db_query('ALTER TABLE table_users ADD COLUMN motivationletter text');
		insertPermission('uczestnik', 'showQualificationStatus');
		db_insert('options',array(
			'name' => 'motivationLetterWords',
			'description' => 'min słów listu motywacyjnego',
			'type' => 'int',
			'value' => '250'
		));
		setVersion(12);
	case(12):
		//db_query('DELETE FROM table_user_roles AS r WHERE r.role=\'uczestnik\' AND EXISTS(
		//	SELECT * FROM table_user_roles AS q WHERE r.uid=q.uid AND q.role=\'akadra\')');
		db_query('ALTER TABLE table_users ADD COLUMN proponowanyreferat text');		
		setVersion(13);
	case(13):
		db_query('CREATE TABLE table_task_solutions
			(
				wid int,
				tid int,
				uid int,
				submitted int,
				solution text,
				grade int,
				feedback text,
				comment text
			)'
		);		
		db_query('ALTER TABLE table_workshops ADD COLUMN tasks_comment text');
		setVersion(14);
	case(14):
		insertPermission('uczestnik', 'sendTaskSolution');
		db_query('ALTER TABLE table_workshop_user ADD COLUMN admincomment text');		
		db_query('ALTER TABLE table_workshop_user ADD COLUMN points int');		
		setVersion(15);
	case(15):
		db_query('ALTER TABLE table_task_solutions DROP COLUMN comment');
		db_query('ALTER TABLE table_task_solutions DROP COLUMN grade');
		db_query('ALTER TABLE table_task_solutions ADD COLUMN status int');
		db_query('ALTER TABLE table_task_solutions ADD COLUMN grade varchar(255)');
		db_query('UPDATE table_task_solutions SET status=1');
		setVersion(16);	
	case(16):	
		db_query('ALTER TABLE table_task_solutions ADD COLUMN notified int');
		setVersion(17);
	case(17):
		db_query('ALTER TABLE table_users ADD COLUMN isselfcatered int');
		setVersion(18);
	case(18):
		insertPermission('jadący', 'editAdditionalInfo');	
		db_query('ALTER TABLE table_users ADD COLUMN pesel varchar(30)');
		db_query('ALTER TABLE table_users ADD COLUMN address varchar(255)');
		db_query('ALTER TABLE table_users ADD COLUMN staybegin int');
		db_query('ALTER TABLE table_users ADD COLUMN stayend int');
		db_query('ALTER TABLE table_users ADD COLUMN tshirtsize varchar(30)');
		db_query('ALTER TABLE table_users ADD COLUMN comments varchar(255)');		
		setVersion(19);
	case(19):
		db_query('ALTER TABLE table_users ADD COLUMN telephone varchar(255)');		
		setVersion(20);
}
