<?php
/*
	content.php 
	Included in index.php, panel.php
	Defined:
		buildMenu() - return <ul>s and <h3> in the menu (left)
		buildNews($r) - return the news' html from a fetched $r
		outputNews() - fill pageVars and http headers with front page news
		outputArticle(&file) - fill pageVars and http headers with fetched article
		outputBinary(&$file) - fill http headers and output the file
		fixMonth($s) - correct polish month names in string
*/
function buildMenu()
{
	$sqlQuery = 'SELECT fid, title FROM '. TABLE_FILES .' WHERE parent=-1 AND type=\'html\' ORDER BY fid ASC';
	$result = db_query($sqlQuery, 'Błąd przy wczytywaniu menu.');
	$menu = '<ul>';
	$menu .= '<li><a class="item" href="index.php">Aktualności</a></li>';
	while ($r = db_fetch_assoc($result))
	{
		$menu .= '<li><a class="item" href="index.php?f='. $r['fid'] .'">'.
			$r['title'] .'</a></li>';
	}
	$menu .= '</ul>';
	
	return $menu;
}

function buildNews($r)
{
	$published = isset($r['published']) ? $r['published'] : time();
	return '<div class="newsHead"><h2 class="left"><b>'. fixMonth(strftime("%e %B %Y",$r['eventtime'])) .'</b>'. $r['title'] .'</h2>'.
		'<span class="right">dodano '. fixMonth(strftime('%e %B', $published)) .
		'</span><hr class="antifloat" /></div>'.
		'<div class="news articleBody">'. $r['content']. '<hr class="antifloat" /></div>'
	;
}

function outputNews()
{
	 
	global $pageVars;
	$sqlQuery = 'SELECT * FROM '. TABLE_NEWS;
	if (isset($_GET['n']))  $sqlQuery .= ' WHERE nid='. intval($_GET['n']);
	else $sqlQuery .= ' ORDER BY CASE WHEN eventtime>0 THEN eventtime ELSE published END DESC, published DESC LIMIT 10';
	$result = db_query($sqlQuery, 'Błąd przy wczytywaniu aktualności.');
	$first = true;
	while ($r = db_fetch_assoc($result))
	{
		if (!$first) $pageVars['pageContent'] .= '<hr/>';
		$pageVars['pageContent'] .= buildNews($r);
		$first = false;
	}
	$pageVars['pageTitle'] = TITLE_PREFIX. 'Aktualności';
	$pageVars['fid'] = -1;
	header('Cache-Control: private, s-maxage=0, max-age=0, must-revalidate');
}

function outputArticle(&$file)
{
	global $pageVars;
	$updated = $file['updated'];
	$result = db_query('SELECT max(published) AS max FROM '. TABLE_NEWS, 'Błąd przy sprawdzaniu wersji artykułu.');
	$result = db_fetch_assoc($result);
	$updated = time(); // max($updated, $result['max']);
	// Send cache headers (If browser says it has an older version of the file, check if it changed since.)
	/*if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $updated)
	{
		header("HTTP/1.1 304 Not Modified", 1, 304);
		exit;
	}*/
	// (Browser must ask if content changed - we always decide. That's additional conections 
	// just to get a 304 response, but that's exactly the headers that en.wikipedia.org sends.)
	header('Cache-Control: private, s-maxage=0, max-age=0, must-revalidate');
	header('Last-Modified: '. gmdate("D, d M Y H:i:s", $updated) .' GMT');
	
	// Get article's html body from file.
	$path = CONTENT . $file['filename'];
	$file['body'] = file_get_contents($path);
	if ($file['body'] === false)  throw new KnownException("Nie udało się otworzyć pliku ($path).");
	require_once('article.php');
	// Fill $pageVars[]
	$pageVars['pageContent'] .= buildArticle($file);
	$pageVars['pageTitle'] = TITLE_PREFIX. htmlspecialchars($file['title']);
	$pageVars['fid'] = $file['fid'];
}

function outputBinary(&$file)
{
	global $pageVars;
	$path = CONTENT . $file['filename'];
	// Manage cache. If browser has a version of the file, check if it's the same.
	$fileMTime = filemtime($path);
	$eTag = md5_file($path);
	$browserMTime = 
		isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
			@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) :
			-1
	;
	$browserETag =
		isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
			trim($_SERVER['HTTP_IF_NONE_MATCH']) :
			''
	;

	if ( ($browserMTime == $fileMTime) || ( $browserETag == $eTag)
	)
	{
		header("HTTP/1.1 304 Not Modified", 1, 304);
		exit;
	}
	header('Last-Modified: '. gmdate("D, d M Y H:i:s", $fileMTime) .' GMT');
	header('ETag: '. $eTag);
	
	// Output the file.
	header("Content-Transfer-Encoding: binary");
	header('Content-Type: ' .  mime_content_type($path));
	header('Content-Length: ' . filesize($path));
	$parts = explode('/',$path);
	header('Content-Disposition: attachment; filename="'.array_pop($parts).'"');
	readfile($path);
	exit;
}

if(!function_exists('mime_content_type')) 
{
	function mime_content_type($path)
	{
		if (function_exists('finfo_open'))
		{
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
			return $mimetype;
		}
		else return 'application/octet-stream';
	}
}

function fixMonth($s)
{
	$mianownik = array
	(
		'styczeń','luty','marzec','kwiecień','maj','czerwiec',
		'lipiec','sierpień','wrzesień','październik','listopad','grudzień'
	);
	$dopelniacz = array
	(
		'stycznia','lutego','marca','kwietnia','maja','czerwca',
		'lipca','sierpnia','września','października','listopada','grudnia'
	);
	return str_replace($mianownik, $dopelniacz, $s);
}
?>
