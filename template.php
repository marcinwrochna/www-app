<?php
/*
	template.php
	Included in common.php, uploader/ubr.php
	Defines:		
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
		class Page extends SimpleTemplate - used as global $PAGE
				
		
		getTipJS($tip) - returns a " onmouseout=... onmouseover=... " string.
		getIcon($name, $tip=false, $href=false) - returns an icon from images/icons/, with optionally a tip and <a href>.
		getButton($title, $href, $icon=false) - returns a big fat button.
		buildMenuBox($title, $items) - builds a menu box, checks permissions
*/

class SimpleTemplate
{
	public $variables;
	private $cleaned = false;
	
	function __construct($templateVariables = array())
	{		
		$this->variables = $templateVariables;
		ob_start();
	}
	
	public function __isset($name)
	{
		return array_key_exists($name, $this->variables);
	}
	
	public function __get($name)
	{
		if (!array_key_exists($name, $this->variables))
			$this->variables[$name] = '';
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
	
	// TODO see why TinyMCE includes html-escaped comment tags with MS Word stuff.
	$offset = 0;
	while (($pos = strpos($html, '&lt;!--', $offset)) !== false)
	{
		$end = strpos($html, '--&gt;', $pos+2);		
		if ($end === false)
			showMessage('Nie znaleziono zamykającego elementu w wklejeniu z Worda.', 'exception');
		$end += strlen('--&gt;');
		$html = substr_replace($html, '', $pos, $end-$pos);
		$offset = $pos;
	}
	
	return $html;
}

// Argument $tip is interpreted as HTML
function getTipJS($tip)
{
	if (strlen(trim($tip))==0)  return ' ';
	$tip = htmlspecialchars(addcslashes($tip, "\\\"\n\r"), ENT_QUOTES);	// Tested.
	return " onmouseout='tipoff()' onmouseover='tipon(this,\"$tip\")' ";
}

function getIcon($name, $tip=false, $href=false)
{
	$class = strtr($name, '.', '_');
	$js = getTipJS($tip);
	if ($tip===false)  $tip = substr($name,0,-4);
	$icon = "<span class='$class icon' $js></span>";
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

// Typical usage: formatAssoc('Name: %name%, ...', $DB->fetch_assoc())
function formatAssoc($string, $assoc)
{
	$names = array();
	$values = array();	
	foreach ($assoc as $name => $value)
	{
		$names[]= "%$name%";
		$values[]= $value;
	}
	return str_replace($names, $values, $string);
}

// Typical usage: '<tr class="'. alternate('even', 'odd') .'">';
function alternate()
{
	static $memory = array();
	$args = func_get_args();
	$array = &$memory[serialize($args)];
	if (!isset($array))
		$array = $args;
	while(!(list($key,$val) = each($array)))
		reset($array);
	return $val;
}
