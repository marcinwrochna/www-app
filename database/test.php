<?php
/*
 *	database/test.php
 */
/*
$assoc = array('column'=>$value, ...);

INSERT: $DB->options[]= $assoc;
UPDATE by pkey: $DB->options[$pkey]= $assoc; // Columns not in $assoc as left as they were.
EXISTS by pkey: isset($DB->options[$pkey]);
DELETE by pkey: unset($DB->options[$pkey]);
*/
function actionDatabaseTest()
{
	function testCompare($is, $shouldbe, $strong=true)
	{
		global $PAGE;
		if (($strong && ($is !== $shouldbe)) || ($is != $shouldbe))
			$PAGE->addMessage('is '. json_encode($is) .'<br/>shouldbe '. json_encode($shouldbe), 'exception');
		else
			$PAGE->addMessage('ok '. json_encode($is), 'success');
	}

	//global $connectionParams;
	//$DB = DB::create(DB_DRIVER, $connectionParams);
	global $DB;

	// query, affected rows
	$r = $DB->query('DELETE FROM table_test');
	$a = $r->affected_rows();

	// query with one parameter, count
	$r = $DB->query('SELECT * FROM table_test WHERE id=$1', 1);
	testCompare(count($r), 0);

	// INSERT, lastValue, SELECT by pkey, fetch assoc
	$DB->test[]= array('time'=>42);
	$id = $DB->test->lastValue();
	$r = $DB->test[$id]->assoc();
	testCompare($r, array('id'=>"$id",'time'=>'42')); // Actually ints shouldn't be strings.

}

