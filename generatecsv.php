<?php


/**
 * We're creting CSV
 */
function actionGenerateCSV()
{
	global $USER, $DB, $PAGE;
    $PAGE->title = _('Generating CSV in progress');
	if (!userCan('adminUsers'))  throw new PolicyException();

    $workshops = $DB->query('
		SELECT w.wid, w.title, 
                (SELECT ws.subject FROM table_workshop_subjects AS ws
                    WHERE ws.wid=w.wid LIMIT 1) AS subject
        FROM table_workshops w WHERE w.status=4 AND w.wid<139 AND NOT w.wid=135 AND NOT w.wid=132 AND w.edition=$1 ORDER BY subject, w.wid', getOption('currentEdition') - 1);
        
    $workshops = $workshops->fetch_all();
    
    $headers = '
        COLUMN          => tDescription; ORDER;
        uid             => uid; ORDER;
        Nazwisko        => Nazwisko; ORDER; desc;
    ';
    
    foreach($workshops as $row)
    {
        $headers = $headers . $row['wid'] . '          => ' . $row['wid'] . '; ' . $row['wid'] . ';
            '; // new line is important
    }
    
    $headers = $headers . '# warsztatow             => # warsztatow; ORDER;
        5 best ocen             => 5 best ocen; ORDER;
        school                => school; ORDER;
        byl wczesniej               => wczesniej; ORDER;
        matura              => matura; ORDER;
        comments                => comments; ORDER;
        ';
    
	$headers = parseTable($headers);
    
    $rows = array();
    
	// Titles of workshops

    $row = array();
    $row[] = '.';
    $row[] = "nazwa";
	foreach ($workshops as $ws)
	{
        $row[] = $ws['title'];
    }
    $rows[] = $row;
    
    // Subjects of workshops
    
    $row = array();
    $row[] = '.';
    $row[] = "przedmiot";
	foreach ($workshops as $ws)
	{
        $row[] = $ws['subject'];
    }
    $rows[] = $row;
    
    // Number od participants which scored particular score
    
    for ($i = 1; $i <= 6; $i++) {
        $row = array();
        $row[] = '.';
        $row[] = $i;
        
        foreach($workshops as $ws)
        {
            $score = $DB->query('
            SELECT COUNT(*) AS scored FROM table_workshop_users wu
            WHERE wu.wid=$1 AND wu.points=$2 GROUP BY wu.wid ORDER BY wu.wid ', $ws['wid'], $i );
            $score = $score->fetch_all();
            if(isset($score[0]['scored']))
                $row[] = $score[0]['scored'];
            else
                $row[] = 0;
        }
        
        $rows[] = $row;
    }
    
    $row = array();
    $row[] = '.';
    $row[] = 'średnia';
    foreach($workshops as $ws)
    {
        $score = $DB->query('
        SELECT COUNT(*) AS num_points, SUM(wu.points) AS sum_points FROM table_workshop_users wu
        WHERE wu.wid=$1 AND wu.points>0 GROUP BY wu.wid ORDER BY wu.wid ', $ws['wid']);
        $score = $score->fetch_all();
        $row[] = round($score[0]['sum_points'] / $score[0]['num_points'], 2);
    }
    $rows[] = $row;
    
    $users = $DB->query('
        SELECT u.uid, u.name, u.school, u.graduationyear
            FROM table_users u ORDER BY u.name');
    $users = $users->fetch_all();
    
    foreach($users as $user)
    {
        $row = array();
        $row[] = $user['uid'];
        $row[] = $user['name'];
        $user_scores = array();
        $user_participant = 0;
        $user_comment = '';
        foreach($workshops as $ws)
        {
            $score = $DB->query('
            SELECT wu.points, wu.participant, wu.admincomment FROM table_workshop_users wu
            WHERE wu.uid=$1 AND wu.points>0 AND wu.wid=$2', $user['uid'], $ws['wid']);
            $score = $score->fetch_all();
            $row[] = $score[0]['points'];
            if($score[0]['points'] > 0)
                $user_scores[] = $score[0]['points'];
            if($score[0]['participant'] >= 3)
                $user_participant += 1;
            if($score[0]['admincomment'])
            {
                $user_comment = $user_comment . $ws['title'] . ': ' . $score[0]['admincomment'] . '
                    |
                    ';
            }
        }
        
        
        //wczesniejsze edycje
        $wczesniejsze = $DB->query('
            SELECT eu.edition, eu.staybegintime, eu.isselfcatered FROM table_edition_users eu
            WHERE eu.uid=$1 ORDER BY eu.edition', $user['uid']);
        $wczesniejsze = $wczesniejsze->fetch_all();
        $wczesniej = '';
        foreach($wczesniejsze as $wczed)
        {
            if($wczed['staybegintime'] or $wczed['isselfcatered'])
            {
                $wczesniej = $wczesniej . 'WWW' . $wczed['edition'] . '
                    ';
            }
        }
        
        //wstawianie do tabelki
        
        $row[] = $user_participant;
        rsort($user_scores);
        $largest5 = array_slice($user_scores, 0, 5);
        $row[] = array_sum($largest5);
        $row[] = $user['school'];
        $row[] = $wczesniej;
        $row[] = $user['graduationyear'];
        $row[] = $user_comment;
        if(count($user_scores) > 0)
            $rows[] = $row;
    }
    
    
    
    buildTableHTML($rows, $headers);
    
    // Trying to generate CSV
    /*
    function outputCSV($data) {
        $output = fopen("php://output", "w");
        foreach ($data as $row) {
            fputcsv($output, $row); // here you can change delimiter/enclosure
        }
        fclose($output);
    }
    
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=file.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    outputCSV(array(
        array("name 1", "age 1", "city 1"),
        array("name 2", "age 2", "city 2"),
        array("name 3", "age 3", "city 3")
    ));*/
}
