<?php

function actionShowPlan() 
{
	global $PAGE;
	$PAGE->title = 'Plan';
	$template = new SimpleTemplate();
	?>
	<h2><?php echo $PAGE->title; ?></h2>
	<form method="post" action="">
		<table><tr><td width="25%"></td></tr></table>
		<input type="submit" value="zapisz" />
	</form>
	<?php
	$PAGE->content .= $template->finish();
}

function actionShowCorrelation()
{
	global $DB, $PAGE;
	$r = db_query('SELECT wid,title FROM table_workshops WHERE type=1 AND status=4 ORDER BY title');
	$DB->query('SELECT wid,title  FROM table_workshops
	            WHERE edition=$1 AND type=$2 AND status=$3  ORDER BY title',
		getOption('currentEdition'), enumBlockType('workshop')->id, enumBlockStatus('great')->id
	);
	$workshops = $DB->fetch_all();
	
	$PAGE->title = 'Macierz korelacji';
	$PAGE->content .=  'Dla każdej pary warsztatów (z listy publicznych) wyświetlana jest liczba
		\'jadących\' uczestników zakwalifikowanych na oba. Prowadzący i kadrowicze też się liczą.
		Można wyróżnić minor główny klikając na wiersze.';
	$PAGE->content .=  '<table style="text-align:center;"><tr class="odd"><td></td><td></td>';
	$class = 'third';
	foreach($workshops as $w1)
	{
		$PAGE->content .= '<td class="'. $class .'"><b><a'. getTipJS($w1['title']).'>'.
			$w1['wid'] .'</a></b></td>';
		$class = ($class=='even')?'odd':(($class=='odd')?'third':'even');
	}
	$class = 'third';
	$PAGE->content .= '</tr>';
	foreach($workshops as $w)
	{
		$DB->query('
			SELECT w.wid,w.title, 
				(SELECT COUNT(*) FROM w1_users u WHERE 
					EXISTS (SELECT * FROM w1_user_roles r WHERE u.uid=r.uid AND role=\'jadący\') AND
					EXISTS (SELECT * FROM w1_workshop_user wu WHERE u.uid=wu.uid AND wu.wid=w.wid AND (lecturer>0 OR participant>=3)) AND
					EXISTS (SELECT * FROM w1_workshop_user wu WHERE u.uid=wu.uid AND wu.wid=$1 AND (lecturer>0 OR participant>=3))) AS cnt
			FROM table_workshops w
			WHERE edition=$2 AND w.type=$3 AND w.status=$4
			ORDER BY w.title',
			$w['wid'], getOption('currentEdition'), enumBlockType('workshop')->id, enumBlockStatus('great')->id
		);
		
		$PAGE->content .= '<tr class="'. $class .'" id="w'. $w['wid']. '">';
		$PAGE->content .= '<td><b>'. $w['wid'] .'</b></td><td>'. $w['title'] .'</td>';
		$tdclass = 'third';
		while ($row = $DB->fetch_assoc()) {
			$PAGE->content .= '<td class="'. $tdclass .'" id="w'.$row['wid'].'_w'.$w['wid'].'">';
			if ($row['wid'] == $w['wid'])  $PAGE->content .= '<span class="diagonal">';
			$PAGE->content .= intval($row['cnt']);
			if ($row['wid'] == $w['wid'])  $PAGE->content .= '</span>';
			$PAGE->content .=  '</td>';
			$tdclass = ($tdclass=='even')?'odd':(($tdclass=='odd')?'third':'even');
		}
		$PAGE->content .= '</tr>';
		$class = ($class=='even')?'odd':(($class=='odd')?'third':'even');
	}
	$PAGE->content .=  '</table>';
	
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
