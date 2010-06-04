<?php
/*
	install.php creates the database table layout
	It can be run again, it won't clean, reset, purge nor delete anything.
	It's outdated and serves only as a reference
	 - some tables are only written in comments.
*/

require_once('common.php');

$template = new SimpleTemplate();

$result = @db_query('
	CREATE TABLE table_users
	(
		uid SERIAL UNIQUE NOT NULL,
		name varchar(255),
		login varchar(255),
		password varchar(255),
		email varchar(255),
		
		confirm int,
		registered int,
		logged int,
		
		roles int,
		
		maturayear int,
		school varchar(255),
		zainteresowania text,
		skadwieszowww text,
		gender varchar(20),
		
		podanieotutora text,
		tutoruid int,
		
		motivationletter text,
		proponowanyreferat text
	)
',false);
if ($result)  echo 'Succesfully created database table '. TABLE_PREFIX .'users.<br/>';

else  echo 'Error creating database table '. TABLE_PREFIX .'users. <pre>'.db_last_error().'</pre><br/>';

if ($result)
{
	db_insert('users', array(
		'uid' => USER_ROOT,
		'login' => 'root',
		'password' => 'rootpassword',
		'name' => 'root',
		'email' => 'noreply@warsztatywww.nstrefa.pl',
		'confirm' => 0,
		'registered' => time(),
		'logged' => time(),
		'roles' => ROLE_ADMIN
	), 'Error creating USER_ROOT.');
}

$result = @db_query('
	CREATE TABLE table_log
	(
		ip inet,
		uid int,
		time int,
		type varchar(255),
		what int
	)
',false);
if ($result)  echo 'Succesfully created database table '. TABLE_PREFIX .'log.<br/>';
else  echo 'Error creating database table '. TABLE_PREFIX .'log. <pre>'.db_last_error().'</pre><br/>';



$result = @db_query('
	CREATE TABLE table_options
	(
		name varchar(255) PRIMARY KEY,
		description varchar(255),
		value text,
		type varchar(255)
	)
',false);
if ($result)
{
	echo 'Succesfully created database table '. TABLE_PREFIX .'options.<br/>';
	$sqlQuery = "INSERT INTO table_options VALUES ('homepage', 'tekst na stronie głównej', '', 'richtextarea')";
	$sqlQuery = "INSERT INTO table_options VALUES ('domains', 'dziedziny', 'matematyka,informatyka teoretyczna,informatyka praktyczna,fizyka,astronomia', 'text')";
	$result = db_query($sqlQuery, 'Error creating initial settings.');
}
else  echo 'Error creating database table '. TABLE_PREFIX .'options. <pre>'.db_last_error().'</pre><br/>';


$result = @db_query('
	CREATE TABLE table_workshops
	(
		wid SERIAL PRIMARY KEY,
		proposer_uid int,
		title varchar(255),
		description text,
		status int,
		type int,
		duration int,
		link varchar(255),
		domain_order int,
		tasks_comment text
	)
',false);
if ($result)  echo 'Succesfully created database table '. TABLE_PREFIX .'workshops.<br/>';
else  echo 'Error creating database table '. TABLE_PREFIX .'workshops. <pre>'.db_last_error().'</pre><br/>';


$result = @db_query('
	CREATE TABLE table_workshop_domain
	(
		wid SERIAL,
		domain varchar(255),
		level int
	)
',false);
if ($result)  echo 'Succesfully created database table '. TABLE_PREFIX .'workshop_domain.<br/>';
else  echo 'Error creating database table '. TABLE_PREFIX .'workshop_domain. <pre>'.db_last_error().'</pre><br/>';


$result = @db_query('
	CREATE TABLE table_workshop_user
	(
		wid int,
		uid int,
		lecturer int,
		participant int,
		admincomment text,
		points int,
		PRIMARY KEY (wid,uid)
	)
',false);
if ($result)  echo 'Succesfully created database table '. TABLE_PREFIX .'workshop_user.<br/>';
else  echo 'Error creating database table '. TABLE_PREFIX .'workshop_user. <pre>'.db_last_error().'</pre><br/>';

$result = @db_query('
	CREATE TABLE table_user_roles
	(
		uid int,
		role varchar(50)
	)
',false);
if ($result)  echo 'Succesfully created database table '. TABLE_PREFIX .'user_roles.<br/>';
else  echo 'Error creating database table '. TABLE_PREFIX .'user_roles. <pre>'.db_last_error().'</pre><br/>';

$result = @db_query('
	CREATE TABLE table_role_permissions
	(
		role varchar(50),
		action varchar(50)
	)
',false);
if ($result)  echo 'Succesfully created database table '. TABLE_PREFIX .'role_permissions.<br/>';
else  echo 'Error creating database table '. TABLE_PREFIX .'role_permissions. <pre>'.db_last_error().'</pre><br/>';

$PAGE->content .= $template->finish();

$result = @db_query('
	CREATE TABLE table_tasks
	(
		wid int,
		tid int,
		description text,
		inline int
	)
',false);
if ($result)  echo 'Succesfully created database table '. TABLE_PREFIX .'tasks.<br/>';
else  echo 'Error creating database table '. TABLE_PREFIX .'tasks. <pre>'.db_last_error().'</pre><br/>';

$PAGE->content .= $template->finish();

/*

CREATE TABLE table_uploads(
			filename varchar(255),
			realname varchar(255),
			size int,
			mimetype varchar(255),
			uploader int,
			utime int
		)
		
CREATE TABLE table_task_solutions
(
	wid int,
	tid int,
	uid int,
	submitted int,
	status int,
	grade varchar(255),
	solution text,	
	feedback text
)

*/

logUser('admin update', 2);

outputPage();
?>
