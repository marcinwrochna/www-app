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
		
		parseUserHTML() - should be called for all user-created text
			replaces [tex] tags with appropriate images,
			it should purify the text to prevent XSS attacks.
		showMessage() - shorthand for displaying short messages on top of page
		
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
		buildFormRow()
		
		getTipJS($tip) - returns a " onmouseout=... onmouseover=... " string.
		getIcon($name, $tip=false, $href=false) - returns an icon from images/icons/, with optionally a tip and <a href>.
		getButton($title, $href, $icon=false) - returns a big fat button.
		buildMenuBox($title, $items) - builds a menu box, checks permissions
*/
require_once('html.php');

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

function generateFormRows($inputs, $previous=array())
{
	foreach ($inputs as $row)
	{
		if (isset($row['name']))  $arguments = $row;
		else  $arguments = array('type'=>$row[2], 'name'=>$row[1], 'description'=>$row[0]);
		if (isset($arguments['readonly']) && $arguments['readonly'] && isset($arguments['default']))
			; // w starych formularzach czasami pola informujące ustawione mają zawartość w 'default' zamiast w previous
		else if (isset($previous[$arguments['name']]) && !is_null($previous[$arguments['name']]))
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
	if (!isset($text))        $text = '';
	if (!isset($hidden))      $hidden = false;
	
	if ($hidden)  return '';
		
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
		if ($type == 'textarea' || $type == 'richtextarea')
			$default = '<div class="descriptionBox">'. parseUserHTML($default) .'</div>';
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
			$row .= " />$text"; 
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
			$href = $item['action'];			
			if (!strpos($item['action'], '://'))
				$href = '?action='. $href;	
			echo ' <a href="'. $href .'">'. $item['title'] .'</a></li>';
			
		}	
	?>
		</ul>
	</div>
	<?php
	$result = $template->finish();
	if (!$count)  return '';
	else  return $result;
}
