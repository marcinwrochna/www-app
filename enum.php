<?php
/*
 * enum.php
 * Requires PHP 5.2?: eval, ArrayAccess, IteratorAggregate, ArrayObject
 */
require_once('utils.php');
// TODO translate, po-parse parseTables and templates.

class Enum implements ArrayAccess, IteratorAggregate
{
	private static $enumTypes = array();
	public static function defineEnum($enumName, array $items, $default)
	{
		self::$enumTypes[$enumName]= new Enum($items, $default);
		$function = 'function enum'. ucfirst($enumName) .'($i=null)'.
		'{ return Enum::get(\''. $enumName .'\', $i); }';
		//eval($function);
	}

	public static function get($enumName, $keyOrId = null)
	{
		if (is_null($keyOrId))
			return self::$enumTypes[$enumName];
		if (is_numeric($keyOrId))
			foreach (self::$enumTypes[$enumName] as $enumItem)
				if ($enumItem->id == $keyOrId)
					return $enumItem;
		return self::$enumTypes[$enumName][$keyOrId];
	}

	private $items;
	private $default;
	private function __construct(array $items, $default)
	{
		$this->items = array();
		foreach ($items as $key => $item)
			$this->items[$key]= new EnumItem($item);
		$default['key'] = 'default';
		$default['id']  = -1;
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
		foreach ($this->items as $item)
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
	public function inArray($enumIds)
	{
		if (!is_array($enumIds))
			$enumIds = func_get_args();
		foreach ($enumIds as $enumId)
			if ($enumId == $this->key)
				return true;
		return false;
	}
}

Enum::defineEnum('participantStatus',
	parseTable('
		KEY          => #ID; tDESCRIPTION;                  bCAN_RESIGN; ICON;            tEXPLANATION;
		none         => 0;   unassociated;                  false;       ;                You aren\'t signed up for this workshop block.;
		candidate    => 1;   candidate;                     true;        tick-yellow.png; You signed up for this workshop block (remember about qualification).;
		rejected     => 2;   didn\'t meet the requirements; false;       cross.png;       You didn\'t meet the requirements.;
		accepted     => 3;   accepted;                      false;       tick.png;        You have been accepted for this workshop block.;
		autoaccepted => 4;   signed up (staff);             true;        tick.png;        You signed up for this workshop block (qualified as a staff member).;
		lecturer     => 5;   lecturer;                      false;       user-green.png;  You are a lecturer to this workshop block.;
	'),
	array('description'=>'???', 'canResign'=>false)
);
function enumParticipantStatus($i=null) { return Enum::get('participantStatus', $i); }

Enum::defineEnum('blockStatus',
	parseTable('
		KEY        => #ID; tDECISION;          tSTATUS;
		new        => 0;   new;                to be considered;
		undetailed => 1;   details requested;  details requested;
		rejected   => 2;   poor;               initially considered;
		accepted   => 4;   accepted;           initially considered;
	'),
	array('decision'=>_('unknown'), 'status'=>_('unknown'))
);
function enumBlockStatus($i=null) { return Enum::get('blockStatus', $i); }

Enum::defineEnum('blockType',
	parseTable('
		KEY          => #ID; tSHORT;    tDESCRIPTION;
		lightLecture => 0;   light;     light lecture;
		workshop     => 1;   workshop;  workshop block;
	'),
	array('description'=>'???')
);
function enumBlockType($i=null) { return Enum::get('blockType', $i); }

Enum::defineEnum('subject',
	parseTable('
		KEY         => ICON; tDESCRIPTION;                #ORDER_WEIGHT;
		mathematics => m;    mathematics;                 -1;
		cs_theory   => it;   computer science (theory);    2;
		cs_practice => ip;   computer science (practice);  4;
		physics     => f;    physics;                      8;
		astronomy   => a;    astronomy;                   16;
	'),
	array('description'=>'???')
);
function enumSubject($i=null) { return Enum::get('subject', $i); }

Enum::defineEnum('solutionStatus',
	parseTable('
		KEY      => #ID; tDESCRIPTION;            ICON;
		none     => 0;   none;                    solution-none.gif;
		new      => 1;   to be checked;           solution-new.gif;
 		returned => 2;   returned for correction; solution-returned.gif;
 		graded   => 3;   graded;                  solution-graded.gif;
 	'),
 	array('description' => '???')
);
function enumSolutionStatus($i=null) { return Enum::get('solutionStatus', $i); }
