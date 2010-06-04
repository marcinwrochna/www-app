<?php
/*
	template.php
	Included in common.php
	Defines:
		initPage() - initializes global $PAGE SimeplTemplate
			$PAGE->head - stuff to be placed in <head>, usually <script>s
			$PAGE->title - page title, without global site prefix
			$PAGE->menu 
			$PAGE->content
			$PAGE->topContent - just above content, for messages
			$PAGE->js
			$PAGE->jsOnLoad
		outputPage() - output headers and all html
			
		showMessage() - shorthand for displaying short messages
		
		class SimpleTemplate - widely used simple output buffer redirecting and parsing class
			Usage:
				$template = new SimpleTemplate(array('varname' => 'value'));
				$template->varname = 'value';
				?>
					<html>
						Place %varname%s with percent signs to replace with values.
						You can add php functions <?php echo f(); ?> and
						<?php if (true) { ?>
							control structures
						<?php } ?>
						normally.
					</html>
				<?php
				echo $template->finish();
				
		generateFormRows($inputs, $previous=array())
*/


function initPage()
{
	global $PAGE;
	$PAGE = new SimpleTemplate();
	$PAGE->head = '';
	$PAGE->title = '';
	$PAGE->menu = '';
	$PAGE->content = '';
	$PAGE->topContent = '';
	$PAGE->js = '';
	$PAGE->jsOnLoad = '';
}

class SimpleTemplate
{
	public $variables;
	private $cleaned = false;
	
	function __construct($templateVariables = array())
	{		
		$this->variables = $templateVariables;
		ob_start();
	}
	
	public function __get($name)
	{
		if (!array_key_exists($name, $this->variables))
		{
			$this->variables[$name] = '';
		}
		return $this->variables[$name];
	}
	
	public function __set($name, $value)
	{
		$this->variables[$name] = $value;
	}
	
	function finish()
	{
		if ($this->cleaned)  return 'Błąd parsera.';
		
		$names = array();
		$values = array();
		foreach ($this->variables as $name => $value)
			if (!is_array($value))
		{
			$names[]= "%$name%";
			$values[]= $value;
		}
		$this->cleaned = true;
		return str_replace($names, $values, ob_get_clean());
	}

	function __destruct()
	{
		if (!$this->cleaned && ob_get_level())  ob_end_clean();
	}
}

function parseUserHTML($html) {
	// TODO high: prevent XSS attacks with HTMLPurifier.
	
	// Parse [tex]code[/tex] into <img src="pathtorenderer.cgi?code"/>.
	preg_match_all("#\[tex\](.*?)\[/tex\]#si",$html,$tex_matches);
	for ($i=0; $i < count($tex_matches[0]); $i++) {
		$pos = strpos($html, $tex_matches[0][$i]); // TODO low: this seems stupid?
		$len = strlen($tex_matches[0][$i]);
		$latex_formula = $tex_matches[1][$i];
		$url = 'http://'. $_SERVER['HTTP_HOST'] . '/cgi-bin/mimetex.cgi?';
		//urlencode($latex_formula)
		$url .= htmlspecialchars($latex_formula, ENT_QUOTES);
		$img = "<img src='$url' alt='formuła latexa' align='absmiddle'/>";
		$html = substr_replace($html, $img,$pos,$len);
	}
	return $html;
}

function showMessage($text, $type='unknown')
{
	//type in ('success', 'userError', 'instruction', 'exception', 'info')
	global $PAGE;
	
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
		
	$PAGE->topContent .= "<div class='contentBox message'>$text</div>";
}

function outputPage()
{	
	global $PAGE;
	header('Content-Language: pl');
	header('Content-Type: text/html; charset=UTF-8'); 
	
	// Too frequent version changes make things incompatible. Must-revalidate.
	header('Cache-Control: private, s-maxage=0, max-age=0, must-revalidate');
	header('Last-Modified: '. gmdate("D, d M Y H:i:s", time()) .' GMT');

	if (DEBUG>=2) $PAGE->content .= dumpSuperGlobals();
	$PAGE->latexPath = 'http://'. $_SERVER['HTTP_HOST'] . '/cgi-bin/mimetex.cgi';
	$PAGE->uploadGetter = 'http://'. $_SERVER['HTTP_HOST'] . '/uploader/getfile.php';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<!--<meta name="keywords" content="" />
	<meta name="description" content="" />
	<meta name="author" content="" />-->
	
	<!--<link rel="icon" type="image/png" href="%[/images/favicon.png]%" />-->
	<!--<link rel="alternate" type="application/atom+xml" href="%[atom.php,lang=-]%" title="Atom feed" />-->
	<link rel="stylesheet" type="text/css" href="css.css?20100525" />
	<link rel="stylesheet" type="text/css" href="images/icons/icons_png.css?20100525b" />
	<title>WWW - %title%</title>

	%head%
	<script type="text/javascript" src="common.js?20100517"></script>
	<script type="text/javascript" src="tinymce/tiny_mce_gzip.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
	<script type="text/javascript">
		tinyMCE_GZ.init({
		plugins : 'nonbreaking,latex,paste,ubrupload',
		themes : 'advanced',
		languages : 'en,pl',
		disk_cache : true,
		debug : false,
		suffix : '_src'
	});
	</script>
	<script type="text/javascript"><!--	
		%js% 
		
		tinyMCE.init({
			language: "pl",
			mode : "specific_textareas",
			editor_selector : "mceEditor",
			plugins: "nonbreaking,latex,ubrupload",
			nonbreaking_force_tab : "true",
			entity_encoding : "raw",
			theme: "advanced",
			theme_advanced_toolbar_location: "top",
			theme_advanced_buttons1 :
				 "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,separator,undo,redo,|,link,unlink,image,ubrupload,hr", 
			theme_advanced_buttons2 : "formatselect,fontsizeselect,|,latex,charmap,sub,sup,outdent,indent,|,removeformat,code,|,forecolor,backcolor,", 
			theme_advanced_buttons3 : "",
			theme_advanced_blockformats : "p,h3,h4,h5,h6,pre,div", //blockquote,address,samp,dd,dt
			indentation: "20px",
			theme_advanced_path : true,
			theme_advanced_path_location : "bottom",
			latex_renderUrl : "%latexPath%",
			ubrupload_getter : "%uploadGetter%",
			button_tile_map : true
		 });
				
		$(document).ready(function(){   
			%jsOnLoad%			
		});
	--></script>
	<!--[if lt IE 7]>
		<script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE7.js" type="text/javascript"></script>
		<style type="text/css">
			body { behavior: url("iecsshover3.htc"); }
		</style>
	<![endif]-->

</head>
<body>
	<div id="tooltip" onmouseover="tiptipon()" onmouseout="tipoff()"></div>
	<div id="globalContainer">
		<div id="headerBox"><h1><a href="index.php">
			<img src="images/logo.gif" alt="Wakacyjne Warsztaty Wielodyscyplinarne" />
		</a></h1></div>

		<div id="middleContainer">
			<div id="contentBoxMargin"><div id="contentBox">
				%topContent%
				%content%
			</div></div>

			<div id="menuPanel">
				<h2 style="display:none;">menu</h2>
				%menu%
			</div>
		</div>
		
		<div id="footerBox" >
			<span class="left">
				<a href="?action=about">credits</a>
			</span>
			<span class="right">
				&copy; <?php echo strftime("%Y"); ?> Wakacyjne Warsztaty Wielodyscyplinarne
			</span>
		</div>
	</div>
	
	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try
		{
			var pageTracker = _gat._getTracker("UA-12926426-2");
			pageTracker._trackPageview();
		} catch(err) {}
	</script>
	<!-- php time: <?php echo time()-$_SERVER['REQUEST_TIME']; ?>s -->
</body>
</html>
	<?php
	echo $PAGE->finish();
}


function generateFormRows($inputs, $previous=array())
{
	foreach ($inputs as $row)
	{
		if (isset($row['name']))  $arguments = $row;
		else  $arguments = array('type'=>$row[2], 'name'=>$row[1], 'description'=>$row[0]);
		if (isset($previous[$arguments['name']]) && !isset($arguments['default']))
			$arguments['default'] = $previous[$arguments['name']];
		buildFormRow($arguments);
	}
}

define('VALUE_OTHER', -66642);
function buildFormRow($type, $name=NULL, $description=NULL, $default=NULL, $options=array(), $ignorePOST=false)
{
	// Handle named arguments.
	if (is_array($type))
		foreach ($type as $key => $val)
			$$key = $val;
			
	if (!isset($properties))  $properties = '';
	if (!isset($readonly))    $readonly = false;
		
	$rtype = $type;
	if (isset($_POST[$name]) && !$ignorePOST)  $default = $_POST[$name];
	
	$row = "<tr id='row_$name'>";
	if ($readonly)
	{
		if (is_array($default)) 
		{
			$tmp = array();
			if (!empty($options))
				foreach($default as $d)  $tmp[]= $options[$d];
			$default = implode(', ', $tmp);
		}
		if ($type == 'timestamp')  $default = strftime('%Y-%m-%d %T', $default);
		$row .= "<td><label>$description</label></td>";
		$row .= "<td><span $properties>$default</span></td>";
	}
	else switch ($type)
	{
		case 'textarea':
		case 'richtextarea':
			$row .=  "<td colspan='2'>";
			$row .= "<label for='$name'>$description</label>";
			$height = ($type=='textarea') ? 2 : 20;
			$class  = ($type=='textarea') ? '' : 'class="mceEditor"';
			$row .= "<br/><textarea rows='$height' name='$name' $class $properties>";
			$row .= htmlspecialchars($default);
			$row .= "</textarea>";
			$row .= "</td>";
			break;
		case 'readonly':
			$row .= "<td><label>$description</label></td>";
			$row .= "<td><span $properties>$default</span></td>";
			break;
		case 'select':
			if (isset($other))
			{
				$options[VALUE_OTHER] = 'inny...';
				$otherIndex = count($options)-1;
				$properties .= 'onChange="setDisplay(\'row_'. $name .'_other\','.
					'this.selectedIndex=='. $otherIndex .')"';
			}
		
			$row .= "<td><label for='$name'>$description</label></td>";
			$row .= "<td><select name='$name' $properties>";
			$foundSelected = false;
			foreach ($options as $val=>$option)
			{
				if (!$foundSelected && $val == VALUE_OTHER) 
				{
					$selected = 'selected="selected"';					
				}
				else if ($val == $default) 
				{
					$selected = 'selected="selected"';
					$foundSelected = true;
				}
				else $selected = '';
				$row .= "<option value='$val' $selected>$option</option>";
			}
			
			if (!$foundSelected && $default && !isset($other))
				$row .= "<option value='$default' selected='selected'>inne ($default)</option>";			
				
			$row .= "</select></td>";
			break;
		case 'checkboxgroup':
			$i = 1;
			$name = $name .'[]';
			$row .= "<td><label for='$name'>$description</label></td><td>";
			foreach ($options as $val=>$option)
			{
				$row .= "<input type='checkbox' class='checkbox' name='$name' value='$val' ";
				if (is_array($default) && in_array($val, $default))  $row .= 'checked="checked" ';
				$row .= "/>$option";				
				if (!($i%3))  $row .= "<br/>";
				$i++;
			}
			$row .= "</td>";
			break;
		case 'custom':
			$row .= "<td><label for='$name'>$description</label></td><td>$custom</td>";
			break;
		case 'int': 			
		case 'timestamp':
			$type = 'text';
		default:
			$row .= "<td><label for='$name'>$description</label></td>";
			$row .= "<td><input type='$type' name='$name' $properties ";
			if ($type == 'checkbox')
			{
				if (!empty($default) && $default!='0')
					$row .= ' checked="checked"';
				$row .= ' value="1"';
			}
			else if (($rtype=='int') || $default)
				$row .= ' value="'. htmlspecialchars($default) .'"';
			if ($rtype == 'int')  $row .= ' size="4"';
			else if ($rtype == 'text' || $rtype == 'timestamp')  $row .= ' class="text"';
			$row .= " />"; 
			if ($rtype == 'timestamp')  $row .= '<small><i>YYYY-MM-DD HH:MM</i> lub <i>YYYY-MM-DD</i></small>';
			$row .= "</td>";
	}
	echo $row . "</tr>";
	if (isset($other))
	{
		buildFormRow(array('name'=>"{$name}_other", 'description'=>" &nbsp; &nbsp; &nbsp; $other",
					'type'=>'text', 'default'=>$default));
		global $PAGE;
		if ($foundSelected)
			$PAGE->jsOnLoad .= 'document.getElementById("row_'. $name .'_other").style.display = "none";';					
	}
}

function getTipJS($tip)
{
	if (strlen(trim($tip))==0)  return ' ';
	$tip = addcslashes($tip, "\n\r");
	return " onmouseout='tipoff()' onmouseover='tipon(this,\"$tip\")' ";
}

function getIcon($name, $tip=false, $href=false)
{
	$class = strtr($name, '.', '_');
	$js = $tip?"onmouseout='tipoff()' onmouseover='tipon(this,\"$tip\")'":'';
	if ($tip===false)  $tip = substr($name,0,-4);
	$icon = "<span class='$class icon' alt='$tip' $js></span>";
	//$icon = "<img class='icon' src='images/icons/$name' alt='$title' title='$title'/>";
	if ($href)  $icon = "<a class='iconLink' href='$href'>$icon</a>";
	return $icon;
}

function getButton($title, $href, $icon=false)
{
	$href = htmlspecialchars($href, ENT_QUOTES);
	$button = $title;
	if ($icon)  $button = getIcon($icon, '') . $button;
	return "<a class='button' href='$href'>$button</a>";
}

function buildMenuBox($title, $items) 
{
	$template = new SimpleTemplate();
	?>
	<div class="menuBox">
		<h3><?php echo $title; ?></h3>
		<ul>
	<?php
		$count = 0;
		foreach($items as $item)
			if ((isset($item['perm']) && $item['perm']) || userCan($item['action']))
		{
			$count++;
			echo '<li>';
			if (isset($item['icon']))  echo getIcon($item['icon'], $item['title']);
			echo ' <a href="?action='. $item['action'] .'">'. $item['title'] .'</a></li>';
			
		}	
	?>
		</ul>
	</div>
	<?php
	$result = $template->finish();
	if (!$count)  return '';
	else  return $result;
}
