<?php
/*
	latex.php
	Actually replaced by cgi-bin mime-tex.
	Used in <img src="latex.php?codetobeparsed" />
*/
require_once('latex/class.latexrender.php');

$latex = new LatexRender(getcwd().'/latex/pictures', 'latex/pictures',getcwd().'/latex/tmp');
$formula = '\sqrt{\alpha+n\choose k}';

$path = $latex->getFormula($formula);

if ($path === false) {
	echo "[Unparseable or potentially dangerous latex formula. Error $latex->_errorcode $latex->_errorextra]";
	die();
}

// Manage cache. If browser has a version of the file, check if it's the same.
$fileMTime = filemtime($path);
$eTag = md5_file($path);
$browserMTime = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
		@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : -1;
$browserETag = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : '';

if ( ($browserMTime === $fileMTime) || ($browserETag === $eTag))
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
//$parts = explode('/',$path);
//header('Content-Disposition: attachment; filename="'.array_pop($parts).'"');
readfile($path);
exit;

