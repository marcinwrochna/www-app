<?php
/*
 *	database/db.php
 */

include_once('test.php');

abstract class DB
{
	public $lastResult = null;
	public $driver;
	protected $tables = array();

	public static function create($driver, $connectionString)
	{
		require_once(strtolower($driver) .'.php');
		$className = "DB_$driver";
		$DB = new $className($connectionString);
		return $DB;
	}

	protected function __construct($connectionString)
	{
		if (!$this->connect($connectionString))
			throw new KnownException(_('Could not connect to database.'). '<br/>
			<pre>'. $this->last_error() .'</pre>');
	}
	abstract public function connect($connectionString);

	abstract public function last_error();

	// Enable " $DB->tablename : DBTable " syntax.
	public function &__get($name)
	{
		$table = TABLE_PREFIX . $name;
		if (isset($this->tables[$table]) && is_object($this->tables[$table]))
			return $this->tables[$table];
		else
			throw new KnownException(sprintf(_('Undefined database table <i>%s</i> (prefix is <i>%s</i>).'), $name, TABLE_PREFIX));
	}

	// Enable " $DB->tablename($pkey_col1, $pkey_col2) : DBRow " syntax
	// TODO replace array parameter with func_get_args().
	public function __call($name, $args)
	{
			return $this->{$name}->offsetGet($args);
	}

	public function num_rows()  { return $this->lastResult->count(); }
	public function affected_rows()  { return $this->lastResult->affected_rows(); }
	public function fetch() 	{ return $this->lastResult->fetch(); }
	public function fetch_assoc($n = null) { return $this->lastResult->fetch_assoc($n); }
	public function fetch_all() { return $this->lastResult->fetch_all(); }
	public function fetch_column($c = null) { return $this->lastResult->fetch_column($c); }
	public function fetch_int() 	{ return $this->lastResult->fetch_int(); }
	public function fetch_vector() 	{ return $this->lastResult->fetch_vector(); }
}

abstract class DBTable implements ArrayAccess
{
	protected $DB;
	public $name;
	public $pkeyColumns;

	public function __construct($DB, $name, $pkeyColumns)
	{
		$this->DB = $DB;
		$this->name = $name;
		$this->pkeyColumns = $pkeyColumns;
	}

	public function offsetGet($pkey)
	{
		if (count($this->pkeyColumns) == 0)
			throw new KnownException('Relation \''. $this->name .'\' has no primary key.');
		$class = 'DBRow_'. $this->DB->driver;
		return new $class($this->DB, $this, $pkey);
	}

	public function offsetExists($pkey)  { return $this->offsetGet($pkey)->count() > 0; }
	public function offsetUnset($pkey)   { return $this->offsetGet($pkey)->delete(); }

	abstract public function lastValue($column = null);
}



abstract class DBRow implements Countable
{
	protected $DB;
	protected $table;
	protected $pkey;

	public function __construct($DB, $table, $pkey)
	{
		$this->DB = $DB;
		$this->table = $table;
		$this->pkey = $pkey;
	}

	public function get($column)
	{
		$tmp = $this->assoc($column);
		return $tmp[$column];
	}

	abstract public function assoc($columns='*');
	abstract public function update($values);
	abstract public function delete();
}



abstract class DBResult implements Iterator, Countable
{
	public $resultResource = null;
	protected $DB = null;
	public function __construct($DB, $resultResource)
	{
		$this->DB = $DB;
		$this->resultResource = $resultResource;
	}

	abstract public function fetch($n = null);
	abstract public function fetch_assoc($n = null);
	abstract public function fetch_vector($n = null);
	abstract public function fetch_all();
	public function fetch_column($column = null)
	{
		$all = $this->fetch_all();
		$result = array();
		if (empty($all))
			return $result;
		if (is_null($column))
		{
			$column = array_keys($all[0]);
			$column = $column[0];
		}
		foreach ($all as $row)
			$result[]= $row[$column];
		return $result;
	}

	public function fetch_int()
	{
		$r = $this->fetch();
		if (ctype_digit($r))
			return intval($r);
		else
			return false;
	}
}
