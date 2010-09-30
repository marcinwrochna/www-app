<?php
	/* update.php
	*/
	
function setVersion($v)
{
	global $PAGE, $DB;
	$DB->options['version']->update(array('value' => $v));
	logUser('auto update', $v);
	$PAGE->addMessage('Aplikacja właśnie została z\'update\'owana do wersji '. $v);
}

function insertPermission($role, $action)
{
	db_query("INSERT INTO table_role_permissions VALUES('$role','$action')");
}

global $DB;
$version = intval(getOption('version'));
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
		//recountDomainOrder();
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
		//recountDomainOrder();
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
	case(20):
		db_query('UPDATE table_workshop_user SET participant=4 WHERE participant=1
			AND EXISTS(SELECT * FROM table_user_roles ur WHERE uid=ur.uid AND role=\'kadra\')');
		setVersion(21);
	case(21):
		db_query('UPDATE table_workshop_user SET participant=1 WHERE participant=4
			AND EXISTS(SELECT * FROM table_user_roles ur WHERE uid=ur.uid AND role=\'kadra\')');
		// Urgh, uid should have been: (manually fixed)
		//	UPDATE w1_workshop_user SET participant=4 WHERE participant=1
		//	AND EXISTS(SELECT * FROM w1_user_roles ur WHERE w1_workshop_user.uid=ur.uid AND role='kadra')
		setVersion(22);	
	case(22):
		db_query('ALTER TABLE table_users ADD COLUMN parenttelephone varchar(255)');
		db_query('ALTER TABLE table_users ADD COLUMN gatherplace varchar(255)');
		setVersion(23);
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
		//insertPermission('admin', 'impersonate');
		insertPermission('admin', 'viewTutoringApplications');
		insertPermission('tutor', 'viewTutoringApplications');
		//insertPermission('registered', 'editTutoringApplication');
		setVersion(30);
}
