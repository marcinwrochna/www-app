<?php
/*
 *	database/postgresql.php
 */

require_once('sql.php');

class DB_PostgreSQL extends DB_SQL
{
	protected function __construct($connectionString)
	{
		parent::__construct($connectionString);
		$this->driver = 'PostgreSQL';
		$tables = $this->query('
			SELECT relname
			FROM pg_class
			WHERE
				relname LIKE $1 AND
				relowner NOT IN (SELECT usesysid FROM pg_user WHERE usename=\'postgres\') AND
				relkind = \'r\'
			',
			TABLE_PREFIX.'%'
		);
		//$this->tables['pg_class'] = new DBTable_Postgresql($this, 'pg_class', array(array('name'=>'relname',)));
		foreach ($tables as $table)
		{
			$result = $this->query('
				SELECT
					pg_attribute.attname AS name,
					format_type(pg_attribute.atttypid, pg_attribute.atttypmod) AS type
				FROM pg_index, pg_class, pg_attribute
				WHERE
			  		pg_class.oid = $1::regclass AND
	  				indrelid = pg_class.oid AND
	  				pg_attribute.attrelid = pg_class.oid AND
	  				pg_attribute.attnum = any(pg_index.indkey) AND
	  				indisprimary
	  			ORDER BY pg_attribute.attnum
	  			', $table['relname']
	  		);
			$this->tables[$table['relname']] =
				new DBTable_Postgresql($this, $table['relname'], $this->fetch_all());
		}
	}
	function connect($connectionString)  { return pg_connect($connectionString); }

	public function last_error() { return pg_last_error(); }

	protected function query_params($query, $params)  { return pg_query_params($query, $params); }
	protected function query_noparams($query)  { return pg_query($query); }
}


class DBTable_PostgreSQL extends DBTable_SQL
{
	// Warning: may cause race when sequence frequently updated.
	// TODO - replace with nextValue
	public function lastValue($column = null)
	{
		if (is_null($column))  $column = $this->pkeyColumns[0]['name'];
		$r = $this->DB->query('SELECT currval(\''. $this->name .'_'. $column .'_seq\')');
		return $r->fetch();
	}
}

class DBRow_PostgreSQL extends DBRow_SQL
{
}


class DBResult_Postgresql extends DBResult_SQL
{
	protected $count = 0;
	protected $i = 0;
	protected $value = null;

	public function __construct($DB, $resultResource)
	{
		parent::__construct($DB, $resultResource);
		$this->count = $this->count();
	}

	public function rewind()
	{
		// TODO pg_result_seek($this->resultResource, 0); and fetch_assoc()
		// instead of jumping through rows
		$this->i = -1;
		$this->next();
	}
	public function valid()   { return ($this->i < $this->count); }
	public function key()     { return $this->i; }
	public function current() { return $this->value; }
	public function next()
	{
		// PostgreSQL stores result resources in a way which
		// doesn't make jumping through rows inefficient
		// (so we're not assuming we can just fetch_assoc() to get the next row by default).
		++$this->i;
		if ($this->i < $this->count)
			$this->value = $this->fetch_assoc($this->i);
		else
			$this->value = null;
	}

	public function count()  { return pg_num_rows($this->resultResource); }
	public function affected_rows() { return pg_affected_rows($this->resultResource); }

	public function fetch_assoc($n = null)
	{
		if (is_null($n))  return pg_fetch_assoc($this->resultResource);
		else  return pg_fetch_assoc($this->resultResource, $n);
	}

	public function fetch_vector($n = null)
	{
		if (is_null($n))  return pg_fetch_row($this->resultResource);
		else  return pg_fetch_row($this->resultResource, $n);
	}

	public function fetch_all()
	{
		$result = pg_fetch_all($this->resultResource);
		// pg_fetch_all returns false for empty results. We should check for errors here.
		if (!$result)  return array();
		return $result;
	}

	public function fetch_column($column = null)
	{
		if (is_null($column))
			return pg_fetch_all_columns($this->resultResource);
		else
			return parent::fetch_column($column);
	}
}


