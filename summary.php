<?php

function actionShowCorrelation()
{
	global $DB, $PAGE;
	$DB->query('SELECT wid,title  FROM table_workshops
	            WHERE edition=$1 AND type=$2 AND status=$3  ORDER BY title',
		getOption('currentEdition'), enumBlockType('workshop')->id, enumBlockStatus('accepted')->id
	);
	$workshops = $DB->fetch_all();

	$PAGE->title = _('Correlation matrix');
	echo  _('For each pair of workshops the number of qualified users '.
		'accepted for both is shown (including lecturers and staff).<br/>'.
		'You can single out a principal minor by clicking on rows.');
	echo  '<table style="text-align:center;"><tr class="odd"><td></td><td></td>';
	$class = 'third';
	foreach ($workshops as $w1)
	{
		echo '<td class="'. $class .'"><b><a'. getTipJS($w1['title']).'>'.
			$w1['wid'] .'</a></b></td>';
		$class = ($class=='even')?'odd':(($class=='odd')?'third':'even');
	}
	$class = 'third';
	echo '</tr>';
	foreach ($workshops as $w)
	{
		$DB->query('
			SELECT w.wid,w.title,
				(SELECT COUNT(*) FROM w1_users u WHERE
					EXISTS (SELECT * FROM w1_edition_users eu  WHERE u.uid=eu.uid AND edition=$1 AND qualified>0) AND
					EXISTS (SELECT * FROM w1_workshop_users wu WHERE u.uid=wu.uid AND wu.wid=w.wid AND participant>=$5) AND
					EXISTS (SELECT * FROM w1_workshop_users wu WHERE u.uid=wu.uid AND wu.wid=$2 AND participant>=$5)) AS cnt
			FROM table_workshops w
			WHERE edition=$1 AND w.type=$3 AND w.status=$4
			ORDER BY w.title',
			getOption('currentEdition'), $w['wid'], enumBlockType('workshop')->id, enumBlockStatus('accepted')->id, enumParticipantStatus('accepted')->id
		);

		echo '<tr class="'. $class .'" id="w'. $w['wid']. '">';
		echo '<td><b>'. $w['wid'] .'</b></td><td>'. $w['title'] .'</td>';
		$tdclass = 'third';
		while ($row = $DB->fetch_assoc()) {
			echo '<td class="'. $tdclass .'" id="w'.$row['wid'].'_w'.$w['wid'].'">';
			if ($row['wid'] == $w['wid'])
				echo '<span class="diagonal">';
			echo intval($row['cnt']);
			if ($row['wid'] == $w['wid'])
				echo '</span>';
			echo  '</td>';
			$tdclass = ($tdclass=='even')?'odd':(($tdclass=='odd')?'third':'even');
		}
		echo '</tr>';
		$class = ($class=='even')?'odd':(($class=='odd')?'third':'even');
	}
	echo  '</table>';

	$wids = array();
	foreach ($workshops as $w)  $wids[]= $w['wid'];
	$PAGE->js .= '
		var wids = ['. implode($wids, ',') .'];
		var selected = {};
		function redrawSelected()
		{

			for (var i=0; i<wids.length; i++) {
				$("#w"+wids[i]).removeClass("selected");
				for (var j=0; j<wids.length; j++) {
					$("#w"+wids[i]+"_w"+wids[j]).removeClass("selected");
				}
			}
			for (var i in selected) {
				$("#"+i).addClass("selected");
				for (var j=0; j<wids.length; j++) {
					$("#"+i+"_w"+wids[j]).addClass("selected");
					//$("#w"+wids[j]+"_"+i).addClass("selected");
				}
			}
		}
	';
	$PAGE->jsOnLoad .= '
		for (var i=0; i<wids.length; i++)
			$("#w"+wids[i]).click(function(){
				if (this.id in selected) {
					delete selected[this.id]; redrawSelected();
				}
				else {
					selected[this.id] = true; redrawSelected();
				}
			});
	';
}


function actionListPersonalData()
{
	global $USER, $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$PAGE->title = _('List of personal data');
	if (!isset($_GET['print']))
	{
		echo '<a class="right" href="?print">'. _('printable version') .'</a><br/>';
		$users = $DB->query('
			SELECT u.name, u.email FROM table_users u
			WHERE '. sqlUserIsQualified() .' AND u.pesel IS NULL AND u.telephone IS NULL
			ORDER BY u.ordername
		');
		echo _('Persons who did not fill their data: ');
		$emails = array();
		foreach ($users as $user)
			$emails[]= $user['name'] .' &lt;'. $user['email']. '&gt;';
		echo implode(', ',$emails) .'.';
	}

	$headers = array(
		'name' => _('full name'),
		'telephone' => _('phone number'),
		'parenttelephone' => _('parent\'s phone'),
		'pesel'           => _('PESEL'),
		'address'         => _('address')
	);
	$rows = $DB->query('
		SELECT u.'. implode(', u.', array_keys($headers)) .'
		FROM table_users u
		WHERE '. sqlUserIsQualified() .'
		ORDER BY u.pesel IS NULL AND u.telephone IS NULL DESC, u.ordername
	');
	buildTableHTML($rows, $headers);
}

function actionListArrivalData($comments = true)
{
	global $USER, $DB, $PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$PAGE->title = _('List of arrival data');
	if (!isset($_GET['print']))
	{
		echo '<a class="right" href="?print">' ._('printable version'). '</a>';
		if ($comments)
			echo '<a href="listArrivalData(0)">'. _('without comments') .'</a>';
		else
			echo '<a href="listArrivalData(1)">'. _('with comments') .'</a>';
	}

	$headers = array(
		'name'          => _('full name'),
		'telephone'     => _('phone number'),
		'staybegintime' => _('arrival'),
		'stayendtime'   => _('departure'),
		'gatherplace'   => _('gathering'));
		//parenttelephone?
	if ($comments)
	{
		$headers['comments']= _('comments');
		$headers['lastmodification']= _('last mod.');
	}

	$users = $DB->query('
		SELECT '. implode(',', array_keys($headers)) .'
		FROM table_users u, table_edition_users eu
		WHERE u.uid=eu.uid AND eu.edition=$1 AND eu.qualified>0
		ORDER BY u.gatherplace DESC, eu.staybegintime, u.ordername
	', getOption('currentEdition'));

	$rows = array();
	foreach ($users as $user)
	{
		if (!is_null($user['staybegintime']))
			$user['staybegintime'] = strftime('%a %e. %H:%M', $user['staybegintime']);
		if (!is_null($user['stayendtime']))
			$user['stayendtime']   = strftime('%a %e. %H:%M', $user['stayendtime']  );
		if (!is_null($user['lastmodification']))
		{
			$t = $user['lastmodification'];
			$user['lastmodification'] = strftime('%Y-%m-%d', $t);
			// Highlight if older than 300 days
			if ($t < time() -  300 * 24 * 60 * 60)
				$user['lastmodification'] = '<span style="color:#a00">'. $user['lastmodification'] . '</span>';
		}
		if ($user['gatherplace'] == 'none')
			$user['gatherplace'] = ' - ';
		else
			$user['gatherplace'] = ucfirst($user['gatherplace']);

		$rows[] = $user;
	}
	buildTableHTML($rows, $headers);
}

function actionListDailyCounts()
{
	global $DB,$PAGE;
	if (!userCan('adminUsers'))  throw new PolicyException();
	$PAGE->title = _('List of meal data');
	$edition = getOption('currentEdition');
	$starttime = strtotime('2011/08/08 00:00');

	if (!isset($_GET['print']))
		echo '<a class="right" href="?print">'. _('printable version') .'</a>';

	$DB->query('SELECT COUNT(*) FROM w1_edition_users
		        WHERE edition=$1 AND qualified>0
					AND (staybegintime IS NULL OR stayendtime IS NULL
					     OR staybegintime < $2 OR stayendtime < $2)',
				$edition, $starttime);
	echo _('Number of qualified users who didn\'t specify their staying time: '). $DB->fetch();
	echo ' ('. _('see') .' <a href="listPersonalData">'. _('list of personal data') .'</a>)<br/>';


	$hours = array(3,9,14,19);
	$headers = array(_('day'));
	foreach ($hours as $h)
		$headers[]= "$h:00";
	$rows = array();
	for ($i=0; $i<=10; $i++) // Workshops have 11 days [0..10].
	{
		$time = $starttime + $i*24*3600;
		$row = array(strftime("%a %e.", $time+9*3600));
		$query = 'SELECT COUNT(*) FROM w1_edition_users
			      WHERE edition=$1 AND qualified > 0
			            AND staybegintime<=$2 AND stayendtime>=$2
			            AND isselfcatered=0
			            ';
		foreach ($hours as $h)
			$row[]= $DB->query($query, $edition, $time+$h*3600)->fetch();
		$rows[]= $row;
	}
	buildTableHTML($rows, $headers);

	$tshirtsizes = array('XS','S','M','L','XL','XXL');
	echo '<h4>' ._('T-shirt sizes') .'</h4>';
	$rows = array();
	$query = 'SELECT COUNT(*) FROM w1_users u WHERE '. sqlUserIsQualified() .' AND u.tshirtsize=$1';
	foreach ($tshirtsizes as $t)
		$rows[]= array($t, $DB->query($query, $t)->fetch());
	buildTableHTML($rows);
}

function sqlUserIsQualified($edition = null)
{
	if (is_null($edition))
		$edition = getOption('currentEdition');
	return ' EXISTS (SELECT * FROM table_edition_users eu WHERE eu.uid=u.uid AND eu.qualified=1
		AND eu.edition='. intval($edition) .') ';
}

