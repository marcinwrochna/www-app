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
		
	public static function create($driver, $connectParams)
	{
		require_once(strtolower($driver) .'.php');
		$className = "DB_$driver";
		$DB = new $className($connectParams);		
		return $DB;
	}
	
	protected function __construct($connectParams)
	{
		if (!$this->connect($connectParams))
			throw new DbException('Połączenie z bazą danych nie powiodło się.');
	}
	abstract public function connect($connectParams);
	
	abstract public function last_error();
	
	// Enable " $DB->tablename : DBTable " syntax.
	public function &__get($name)
	{
		return $this->tables[TABLE_PREFIX . $name];
	}	
	
	public function num_rows()  { return $this->lastResult->count(); }
	public function affected_rows()  { return $this->lastResult->affected_rows(); }
	public function fetch() 	{ return $this->lastResult->fetch(); }
	public function fetch_assoc($n = null) { return $this->lastResult->fetch_assoc($n); }
	public function fetch_all() { return $this->lastResult->fetch_all(); }
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
		return new DBRow_PostgreSQL($this->DB, $this, $pkey);
	}	
	
	public function offsetExists($pkey)  { return $this->offsetGet($pkey)->count() > 0; }
	public function offsetUnset($pkey)   { return $this->offsetGet($pkey)->delete(); }

	abstract public function lastValue($column = null);
}



abstract class DBRow
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
	
	abstract public function assoc($columns='*');
	abstract public function update($values);
	abstract public function count();
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
	abstract public function fetch_all();
}
