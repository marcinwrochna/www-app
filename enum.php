<?php
/*
 * enum.php
 * Requires PHP 5.2?: eval, ArrayAccess, IteratorAggregate, ArrayObject
 */
require_once('utils.php');
 
class Enum implements ArrayAccess, IteratorAggregate
{	
	private static $enumTypes = array();
	public static function define($enumName, array $items, $default)
	{
		self::$enumTypes[$enumName]= new Enum($items, $default);
		$function = 'function enum'. ucfirst($enumName) .'($i=null)'.
		'{ return Enum::get(\''. $enumName .'\', $i); }';
		eval($function);
	}
	
	public static function get($enumName, $id = null)
	{
		if (is_null($id))
			return self::$enumTypes[$enumName];
		if (is_numeric($id))
			foreach(self::$enumTypes[$enumName] as $enumItem)
				if ($enumItem->id == $id)
					return $enumItem;					
		return self::$enumTypes[$enumName][$id];		
	}

	private $items;
	private $default;
	private function __construct(array $items, $default)
	{
		$this->items = array();
		foreach($items as $id=>$item)
			$this->items[$id]= new EnumItem($item);
		$this->default = new EnumItem($default);
	}
	public function offsetGet($offset)
	{
		if (isset($this->items[$offset]))
			return $this->items[$offset];
		else
			return $this->default;
	}
	public function offsetSet($offset, $value)  { throw new KnownException('Undefined operation'); }
	public function offsetExists($offset)  { return isset($this->items[$offset]); }
	public function exists($offset) { return isset($this->items[$offset]); }
	public function offsetUnset($offset)  { throw new KnownException('Undefined operation'); }
	public function getIterator()  { return new ArrayIterator($this->items); }
	
	public function assoc($keyColumn, $valColumn)
	{
		$a = array();
		foreach($this->items as $item)
			$a[$item[$keyColumn]] = $item[$valColumn];
		return $a;
	}
}

class EnumItem extends ArrayObject
{
	public function __construct($items)
	{
		parent::__construct($items, ArrayObject::ARRAY_AS_PROPS);
	}
}

// '% ' zamienione na 'y'/'a'/'ych', '%ś' zamienione na 'eś'/'aś'
Enum::define('participantStatus', 
	applyDefaultHeaders(
		array('id','description','canResign', 'icon', 'explanation'),
		array(
			'none'         => array(0, 'niezapisan%',         false, '',                'Nie jesteś zapisan% na te warsztaty.'),
			'candidate'    => array(1, 'wstępnie zapisan%',   true , 'tick-yellow.png', 'Jesteś zapisan% (wstępnie; pamiętaj o zadaniach kwalifikacyjnych).'),
			'rejected'     => array(2, 'nie spełnia wymagań', false, 'cross.png',       'Nie spełnił%ś wymagań.'),
			'accepted'     => array(3, 'zakwalifikowan%',     false, 'tick.png',        'Zakwalifikował%ś się.'),
			'autoaccepted' => array(4, 'zapisan% (kadra)',    true , 'tick.png',        'Jesteś zapisan% (zakwalifikowany jako kadra).'),
			'lecturer'     => array(5, 'prowadząc%',          false, 'user-green.png',  'Prowadzisz te warsztaty.')
		)		
	),
	array('description'=>'???', 'canResign'=>false)
);

Enum::define('blockStatus', 
	applyDefaultHeaders(
		array('id', 'decision', 'status'),
		array(
			'new'        => array(0, 'nowe',               'nie rozpatrzono'     ),
			'undetailed' => array(1, 'prośba o szczegóły', 'prośba o szczegóły'  ),
			'rejected'   => array(2, 'beznadziejne',       'wstępnie rozpatrzono'),
			'ok'         => array(3, 'ujdzie',             'wstępnie rozpatrzono'),
			'great'      => array(4, 'świetne',            'wstępnie rozpatrzono')
		)
	),
	array('decision'=>'nieznany', 'status'=>'nieznany')
);

Enum::define('blockType',
	applyDefaultHeaders(
		array('id', 'short', 'description'),
		array(
			'lightLecture' => array(0, 'luźny',     'luźny wykład'),
			'workshop'     => array(1, 'warsztaty', 'warsztaty'   )
		)
	),
	array('description'=>'???')
);

Enum::define('subject', 
	applyDefaultHeaders(
		array('icon', 'description', 'orderWeight'),
		array(
			'mathematics' => array('m',  'matematyka',             -1),
			'cs_theory'   => array('it', 'informatyka teoretyczna', 2),
			'cs_practice' => array('ip', 'informatyka praktyczna',  4),		
			'physics'     => array('f',  'fizyka',                  8),
			'astronomy'   => array('a',  'astronomia',             16)
		)
	),
	array('description'=>'???')	
);

Enum::define('solutionStatus', 
	applyDefaultHeaders(
		array('id', 'description', 'icon'),
		array(
			'none'     => array(0, 'brak',                 'solution-none.gif'),
			'new'      => array(1, 'czeka na sprawdzenie', 'solution-new.gif'),
 			'returned' => array(2, 'do poprawy',           'solution-returned.gif'),
 			'graded'   => array(3, 'ocenione',             'solution-graded.gif')
 		)
 	),
 	array('description' => '???')
);

function actionEnumTest()
{
	global $PAGE;	
	$PAGE->content .= enumWorkshopStatus('zong')->decision;
}
