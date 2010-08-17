<?php

function writeMTime($f)
{
	echo $f .'?'. filemtime($f);
}

class Page extends SimpleTemplate
{
	function __construct()
	{
		parent::__construct();
		$this->head = '';
		$this->title = '';
		$this->menu = ''; 
		$this->content = ''; 
		$this->topContent = '';
		$this->js = '';
		$this->jsOnLoad = '';
	}
	
	function finish()
	{		
		header('Content-Language: pl');
		header('Content-Type: text/html; charset=UTF-8'); 		
		// Too frequent version changes make things incompatible. Must-revalidate.
		// May be changed to something less expensive later.
		header('Cache-Control: private, s-maxage=0, max-age=0, must-revalidate');
		header('Last-Modified: '. gmdate("D, d M Y H:i:s", time()) .' GMT');
		
		if (DEBUG>=2) $this->content .= dumpSuperGlobals();
		
		$this->latexPath = 'http://'. $_SERVER['HTTP_HOST'] . '/cgi-bin/mimetex.cgi';
		$this->uploadGetter = 'http://'. $_SERVER['HTTP_HOST'] . '/uploader/getfile.php';
		
		if (!isset($this->headerTitle))
			$this->headerTitle = '<h2>'. $this->title .'</h2>';
			
		if (!isset($_GET['print']))
			include('html.php'); 			
		else
			$this->printableHTML();
		return parent::finish();
	}
	
	function addMessage($text, $type='unknown')
	{
		$icons = array(
			'info' => 'information.png',
			'warning' => 'error.png',
			'success' => 'accept.png',
			'instruction' => 'pencil.png',
			'userError' => 'exclamation.png',
			'exception' => 'cancel.png',
		);
		if (isset($icons[$type]))
			$text = "<img src='images/fatcow/32/${icons[$type]}' alt='$type'/>$text";
		
		$this->topContent .= "<div class='contentBox message'>$text</div>";		
	}
	
	// $items is an array of menu items: each as an associative array or
	// 	a vector with 'title','action','icon'[,'perm'] values respectively.
	function addMenuBox($title, $items, $custom='')
	{
		$count = 0;
		$menuItems = '';
		foreach($items as $item)
		{
			if (!is_assoc($item))
			{
				if (count($item) == 4)
					$item = array_combine(array('title','action','icon','perm'), $item);
				else
					$item = array_combine(array('title','action','icon'), $item);					
			}
			if (!isset($item['perm']))
				$item['perm'] = userCan($item['action']);
			if (!$item['perm'])
				continue;
			$count++;
			$item['icon'] = getIcon($item['icon']);
			$menuItems .= formatAssoc('<li>%icon% <a href="%action%">%title%</a></li>', $item);
		}			
		if (!$count)  return '';
		
		$params = array('title'=>$title,'items'=>$menuItems,'custom'=>$custom);
		$template = new SimpleTemplate($params);
		?>
			<div class="menuBox">
				%custom%
				<h3>%title%</h3>
				<ul>%items%</ul>
			</div>
		<?php
		$this->menu .= $template->finish();
	}
	
	function printableHTML()
	{
		?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl" dir="ltr">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<link rel="stylesheet" type="text/css" href="<?php writeMTime('css.css'); ?>" />
				<title>%title% - WWW</title>
			</head>
			<body class="printable">
				<div>
					%topContent%
					%headerTitle%
					%content%
				</div>
			</body>
			</html>
		<?php
	}
}

// DEPRECATED, use $PAGE->addMessage instead
function showMessage($text, $type='unknown')
{
	global $PAGE;
	$PAGE->addMessage($text, $type);	
}

