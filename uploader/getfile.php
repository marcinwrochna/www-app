<?php
require_once dirname(__FILE__).'/ubr.php';
$file = $_GET['f'];

	$path = dirname(__FILE__).'/files/'. $file;
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
	
	if (($browserMTime === $fileMTime) || ( $browserETag === $eTag))
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
	//header('Content-Disposition: attachment; filename="'.array_pop($parts).'"');
	readfile($path);
	exit;
