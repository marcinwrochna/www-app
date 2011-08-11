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
	public $cssClass = null;
	public $columnWidth = null;
	public $custom = '';
	public $submitValue = 'Save';
	public $valid = false;

	public function __construct($rows = array(), $action='')
	{
		$this->submitValue = _('Save');
		$this->action = $action;
		foreach ($rows as $id => $row)
			if (is_int($id))
				$this->addRow($row); // DEPRECATED, use parseTable to define rows.
			else
				$this->rows[$id]= $row;

	}

	// DEPRECATED, use parseTable to define rows.
	public function addRow($row) //or addRow($type, $name, $description, $readonly).
	{
		if (!is_array($row))
			$row = func_get_args();
		$row = arrayToAssoc($row, array('type','name','description','readonly'));
		$this->rows[$row['name']]= $row;
	}

	public function getHTML()
	{
		$params = array(
			'action'      => $this->action,
			'class'       => $this->cssClass ? 'class="'. $this->cssClass .'"' : '',
			'columnWidth' => $this->columnWidth,
			'custom'      => $this->custom,
			'submitValue' => $this->submitValue,
			'formid'      => $this->getFormId(),
			'rows'        => generateFormRows($this->rows, $this->values),
		);
		$template = new SimpleTemplate($params);
		?>
			<form method="post" action="%action%" name="form" %class%>
				<input type="hidden" name="formid" value="%formid%" />
				<?php if (is_null($this->columnWidth)) : ?>
					<table>
				<?php else: ?>
					<table style="table-layout:fixed">
						<tr class="columnWidth"><td width="%columnWidth%"></td></tr>
				<?php endif; ?>
						%rows%
					</table>
				%custom%
				<?php if (!is_null($this->submitValue)) : ?>
					<input type="submit" value="%submitValue%" />
				<?php endif; ?>
			</form>
		<?php
		return $template->finish();
	}

	public function submitted()
	{
		// TODO Could add CSRF protection: just set $_SESSION['requestId'] and check it here.
		return isset($_POST['formid']) && ($_POST['formid'] == $this->getFormId());
	}

	public function getColumns()
	{
		$columns = array();
		foreach ($this->rows as $row)
			if (!in_array($row['type'], array('checkboxgroup', 'custom')) && empty($row['notdb']))
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
				$value = isset($_POST[$row['name'] .'_other']) ? $_POST[$row['name'] .'_other'] : null;

			switch($row['type'])
			{
				case 'int':
				case 'timestamp':
					$value = intval($value);
					break;
				case 'checkbox':
					$value = empty($value) ? 0 : 1; // SQL would have problems with true/false.
					break;
			}

			$values[$row['name']] = $value;
		}
		return $values;
	}

	public function getFormId()
	{
		return sha1($this->action . $this->getColumns());
	}

	public function fetchAndValidateValues()
	{
		global $PAGE;
		$values = $this->fetchValues();
		$this->valid = true;
		foreach ($this->rows as &$row)
			if (!empty($row['validation']) && empty($row['readonly']))
		{
			$row['errors'] = array();
			$validators = explode(',', $row['validation']);
			foreach ($validators as $v)
			{
				$validator = substr($v, 0, strpos($v.'(', '('));
				$params = substr($v, strpos($v, '(') + 1, -1);
				$params = explode(' ', $params);
				$r = $this->validate($values[$row['name']], $validator, $params, $values);
				if ($r !== true)
				{
					$row['errors'][] = $r;
					$this->valid = false;
				}
			}
		}
		return $values;
	}

	public function assert($assertion, $errorDescription)
	{
		if (!$assertion)
		{
			global $PAGE;
			$PAGE->addMessage($errorDescription, 'userError');
			$this->valid = false;
		}
		return $assertion;
	}

	public function validate(&$value, $validator, $params, $values)
	{
		global $PAGE;
		switch ($validator)
		{
			case 'charset':
				$value = (string) $value;
				for ($i = 0; $i < mb_strlen($value, 'UTF-8'); $i++)
				{
					$char = mb_substr($value, $i, 1, 'UTF-8');
					$good = false;
					foreach ($params as $p)
						$good = $good || $this->validateCharset($char, $p);
					if (!$good)
						return sprintf(_('Invalid character: "%s", position %d.'),
							$i + 1,
							htmlspecialchars($char)
						);
				}
				return true;
			case 'length':
				$value = trim($value);
				if (count($params) == 1)
				{
					if (strlen($value) != $params[0])
						return sprintf(_('The value should have exactly %d characters.'), $params[0]);
				}
				else
				{
					if (strlen($value) < $params[0])
						return _('The value is too short'). '(< '. $params[0] ._('characters').').';
					if (strlen($value) > $params[1])
						return _('The value is too long').  '(> '. $params[0] ._('characters') .').';
				}
				return true;
			case 'email':
				if (!validEmail($value))
					return _('Invalid e-mail address.');
				return true;
			case 'equal':
				if ($value != $values[$params[0]])
					return _('The value doesn\'t match it\'s repetition.');
				unset($values[$params[0]]);
				return true;
			case 'int':
				if (intval($value) != $value)
					return _('The value is not an integer.');
				$value = intval($value);
				return true;
			default:
				return 'Unknown validator: '. $validator .'.';
		}
	}

	public function validateCharset($char, $charset)
	{
		switch ($charset)
		{
			case 'alpha':
				return (bool) preg_match('/(*UTF8)\pL/u', $char);
			case 'alnum':
				return $this->validateCharset($char, 'alpha') || $this->validateCharset($char, 'digit');
			case 'name':
				return $this->validateCharset($char, 'alpha') || $this->validateCharset($char, ' \'-.’');
			case 'white':
				return ctype_space($char);
			case '':
				return $char === ' ';
			default:
				if (is_callable("ctype_$charset"))
				return call_user_func_array("ctype_$charset", array($char));
				return strpos($charset, $char) !== false;
		}
	}
}

function generateFormRows($inputs, $previous=array())
{
	$result = '';
	foreach ($inputs as $row)
	{
		if (isset($row['name']))  $arguments = $row;
		else  $arguments = array('type'=>$row[0], 'name'=>$row[1], 'description'=>$row[2]);
		if (isset($arguments['readonly']) && $arguments['readonly'] && isset($arguments['default']))
			; // old forms sometimes may have fields set in 'default' instead of $previous DEPRECATED.
		else if (isset($previous[$arguments['name']]) && !is_null($previous[$arguments['name']]))
			$arguments['default'] = $previous[$arguments['name']];
		$result .= buildFormRow($arguments);
	}
	return $result;
}

define('VALUE_OTHER', -66642);
function buildFormRow($type, $name=NULL, $description=NULL, $default=NULL, $options=array(), $ignorePOST=false)
{
	global $PAGE;
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
				foreach ($default as $d)  $tmp[]= $options[$d];
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
				$row .= "<option value='$default' selected='selected'>". _('other') ." ($default)</option>";

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
		case 'text':
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
			if ($rtype == 'timestamp')  $row .= '<small><i>YYYY-MM-DD HH:MM</i> '. _('or') .' <i>YYYY-MM-DD</i></small>';
			$row .= "</td>";

			if (!empty($autocomplete))
			{
				foreach ($autocomplete as &$item)
					$item = json_encode($item);
				$autocomplete = implode(',', $autocomplete);
				$PAGE->jsOnLoad .= '$("#'. $name .'").autocomplete({minLength:1,source:['. $autocomplete.']});'."\n";
			}
	}

	if (isset($errors))
	{
		$row .= "<td class='formError'><ul>";
		foreach ($errors as $e)
		{
			$row .= "<li>$e</li>";
		}
		$row .= "</ul></td>";
	}

	$row .= "</tr>";
	if (isset($other))
	{
		$row .= buildFormRow(array('name'=>"{$name}_other", 'description'=>" &nbsp; &nbsp; &nbsp; $other",
					'type'=>'text', 'default'=>$default));
		global $PAGE;
		if ($foundSelected)
			$PAGE->jsOnLoad .= 'document.getElementById("row_'. $name .'_other").style.display = "none";';
	}
	return $row;
}
