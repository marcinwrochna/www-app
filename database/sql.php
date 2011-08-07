<?php
/*
 *	database/sql.php
 *	Some common parts for drivers of SQL-compliant databases.
 *	No actual standard has been chosen, so don't assume anything, test.
 * I only tested on postgres.
 */

abstract class DB_SQL extends DB
{
	public function query($query, $params=null)
	{
		$query = str_replace('table_', TABLE_PREFIX, $query);
		if (func_num_args()>2)
		{
			$params = func_get_args();
			array_shift($params);
		}
		if (is_null($params))
			$result = $this->query_noparams($query);
		else if (is_array($params))
			$result = $this->query_params($query, $params);
		else
			$result = $this->query_params($query, array($params));

		if ($result === false)  throw new DbException($errorString);
		$class = 'DBResult_'. $this->driver;
		$this->lastResult = new $class($this, $result);
		return $this->lastResult;
	}
	abstract protected function query_params($query, $params);
	abstract protected function query_noparams($query);
}

abstract class DBTable_SQL extends DBTable
{
	public function offsetSet($pkey, $values)
	{
		if (is_null($pkey))
		{
			$query = 'INSERT INTO "'. $this->name .'" ';
			$query .= '('. implode(array_keys($values), ',') .') ';
			$query .= 'VALUES ($' .implode(range(1,count($values)),',$') .')';
			return $this->DB->query($query, array_values($values));
		}
		else
			return $this->offsetGet($pkey)->update($values);
	}
}


abstract class DBRow_SQL extends DBRow
{
	protected $where = ' ';

	public function __construct($DB, $table, $pkey)
	{
		parent::__construct($DB, $table, $pkey);

		$conditions = array();
		foreach ($this->table->pkeyColumns as $i=>$column)
			$conditions[]= '"'. $column['name'] .'"=$'. ($i+1);
		$this->where = 'WHERE '. implode(' AND ', $conditions);

		if (!is_array($this->pkey))  $this->pkey = array($this->pkey);
		if (count($this->table->pkeyColumns) > count($this->pkey))
			throw new KnownException('Returning relation slices isn\'t supported yet.'); //TODO slices
		if (count($this->table->pkeyColumns) < count($this->pkey))
			throw new KnownException('Too much columns in key. Primary key is actually: '.
				json_encode($this->table->pkeyColumns));
		// TODO pkey as associative array
	}

	public function assoc($select='*')
	{
		$query  = 'SELECT '. $select .' ';
		$query .= 'FROM "'. $this->table->name .'" ';
		$query .= $this->where;

		$this->DB->query($query, $this->pkey);
		return $this->DB->fetch_assoc();
	}

	public function update($values)
	{
			$query  = 'UPDATE "'. $this->table->name .'" ';

			$updates = array();
			$i = count($this->table->pkeyColumns)+1;
			foreach ($values as $k=>$v)
				$updates[]= '"'. $k .'"=$'. ($i++);
			$query .= 'SET '. implode($updates,',') .' ';

			$query .= $this->where;
			return $this->DB->query($query, array_merge($this->pkey, array_values($values)));
	}

	public function count()
	{
		$query  = 'SELECT COUNT(*) FROM "'. $this->table->name .'" ';
		$query .= $this->where;
		return intval($this->DB->query($query, $this->pkey)->fetch());
	}

	public function delete()
	{
		$query  = 'DELETE FROM "'. $this->table->name .'" ';
		$query .= $this->where;
		return $this->DB->query($query, $this->pkey);
	}
}

abstract class DBResult_SQL extends DBResult
{
	public function fetch($n = null)
	{
		$result = $this->fetch_assoc($n);
		if (count($result)==1)
			$result = array_pop($result);
		return $result;
	}

	abstract public function affected_rows();
}
