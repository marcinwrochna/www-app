<?php
// TODO move plan.php to summary.php

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
	foreach($workshops as $w1)
	{
		echo '<td class="'. $class .'"><b><a'. getTipJS($w1['title']).'>'.
			$w1['wid'] .'</a></b></td>';
		$class = ($class=='even')?'odd':(($class=='odd')?'third':'even');
	}
	$class = 'third';
	echo '</tr>';
	foreach($workshops as $w)
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
