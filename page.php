<?php

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
			
		include('html.php'); 			
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
}

// DEPRECATED, use $PAGE->addMessage instead
function showMessage($text, $type='unknown')
{
	global $PAGE;
	$PAGE->addMessage($text, $type);	
}

