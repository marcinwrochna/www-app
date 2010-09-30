<?php
/*
 * forms.php
 * Included in template.php
 *
 */
 
class Form
{
	public $action = '';
	public $rows = array();
	public $values = array();
	public $columnWidth = null;
	public $custom = '';
	public $submitValue = 'Wyślij';	
	
	public function __construct($rows = array(), $action='')
	{
		foreach ($rows as $row)
			$this->addRow($row);
		$this->action = $action;
	}
	
	public function addRow($row) //$type, $name, $description
	{
		if (is_array($row))
			$this->rows[]= applyDefaultKeys($row, array('type','name','description','readonly'));
		else 
		{
			$row = func_get_args();
			$this->rows[]= applyDefaultKeys($row, array('type','name','description', 'readonly'));
		}
	}
	
	public function getHTML()
	{
		$params = array(
			'action' => $this->action,
			'columnWidth' => $this->columnWidth,
			'custom' => $this->custom,
			'submitValue' => $this->submitValue,
		);
		$template = new SimpleTemplate($params);
		?>
		<form method="post" action="%action%" name="form" id="theform">
			<?php if (is_null($this->columnWidth)) : ?>		
				<table>
			<?php else: ?>
				<table style="table-layout:fixed">
					<tr class="columnWidth"><td width="%columnWidth%"></td></tr>				
			<?php endif; ?>
				<?php generateFormRows($this->rows, $this->values);	?>			
			</table>
			%custom%
			<?php if (!is_null($this->submitValue)) : ?>
				<input type="submit" name="formsubmitted" value="%submitValue%" />
			<?php endif; ?>
		</form>
		<?php
		return $template->finish();
	}
	
	public static function submitted()
	{
		return isset($_POST['formsubmitted']);
	}
	
	public function getColumns()
	{
		$columns = array();
		foreach ($this->rows as $row)
			if (!in_array($row['type'], array('checkboxgroup', 'custom')))
				$columns[]= '"'. $row['name'] .'"';
		return implode(',', $columns);
	}
	
	public function fetchValues()
	{
		
		$values = array();
		foreach ($this->rows as $row)
		{
			if (!empty($row['readonly'])
					|| !empty($row['hidden'])
					|| ($row['type'] == 'checkboxgroup')
					|| ($row['type'] == 'custom'))
				continue;
				
			$value = isset($_POST[$row['name']]) ? $_POST[$row['name']] : null;
			
			if (isset($row['other']) && ($value == VALUE_OTHER))
				$value = $_POST[$row['name'] .'_other'];
				
			switch($row['type'])
			{
				case 'int':
				case 'timestamp':
					$value = intval($value);
					break;
				case 'checkbox':
					$value = empty($value) ? 0 : 1; // 0/1, cause SQL has problems with true/false.
					break;
			}
			
			if (isset($row['filter']) && ($row['filter'] == 'int'))
				$value = intval($value);
				
			$values[$row['name']] = $value;
		}
		return $values;
	}
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
			$row .= "<br/><textarea rows='$height' name='$name' id='$name' $class $properties>";
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
				$properties .= ' onchange="$(\'#row_'. $name .'_other\').toggle('.
					'this.selectedIndex=='. $otherIndex .')"';
			}
		
			$row .= "<td><label for='$name'>$description</label></td>";
			$row .= "<td><select name='$name' id='$name' $properties>";
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
			$row .= "<td><input type='$type' name='$name' id='$name' $properties ";
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
