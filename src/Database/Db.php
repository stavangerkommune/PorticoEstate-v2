<?php

namespace App\Database;

use PDO;
use Exception;
use PDOException;

class Db
{
	private static $instance = null;
	private $db;
	private $isTransactionActive = false;
	private static $domain;
	private static $config;
	private $affected_rows = 0;
	private $fetch_single = false;
	private $pdo_fetchmode = PDO::FETCH_ASSOC;
	private $delayPointer = false;
	private $statement_object = null;
	public $Halt_On_Error = 'yes';
	public $Exception_On_Error = false;
	public $fetchmode = 'ASSOC';
	public $resultSet = null;
	public $Record = null;
	public $debug = false;



	private function __construct($dsn, $username = null, $password = null, $options = null)
	{
		if ($dsn === '')
		{
			//return a dummy PDO-object
			$this->db = new PDO('sqlite::memory:');
		}
		else
		{
			$this->db = new PDO($dsn, $username, $password, $options);
		}
	}

	/**
	 * Forwards method invocations to db object
	 */
	public function __call($method, $arguments)
	{
		return call_user_func_array(array($this->db, $method), $arguments);
	}

	public static function getInstance($dsn = '', $username = null, $password = null, $options = null)
	{

		if (self::$instance === null)
		{
			self::$instance = new self($dsn, $username, $password, $options);
		}
		return self::$instance;
	}

	public function transaction_begin()
	{
		if (!$this->isTransactionActive)
		{
			if (self::$config['db_type'] == 'mysql')
			{
				$this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
				$this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
			}
			$this->db->beginTransaction();
			$this->isTransactionActive = true;
		}
	}

	public function transaction_commit()
	{
		if ($this->isTransactionActive)
		{
			$commitSuccess = $this->db->commit();
			if (self::$config['db_type'] == 'mysql')
			{
				$this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
			}

			$this->isTransactionActive = false;
			return $commitSuccess;
		}
		return false;
	}

	public function transaction_abort()
	{
		$ret = false;

		if ($this->isTransactionActive)
		{
			$this->isTransactionActive = false;

			try
			{
				$ret = $this->db->rollBack();
			}
			catch (PDOException $e)
			{
				if ($e)
				{
					trigger_error('Error: ' . $e->getMessage(), E_USER_ERROR);
				}
			}
		}
		return $ret;
	}

	public function db_addslashes($string)
	{
		if ($string === '' || $string === null)
		{
			return '';
		}
		return substr($this->db->quote($string), 1, -1);
	}

	public function get_transaction()
	{
		return $this->isTransactionActive;
	}

	public function unmarshal($value, $type)
	{
		$type = strtolower($type);
		/* phpgw always returns empty strings (i.e '') for null values */
		if (($value === null) || ($type != 'string' && (strlen(trim($value)) === 0)))
		{
			return null;
		}
		else if ($type == 'int')
		{
			return intval($value);
		}
		else if ($type == 'decimal')
		{
			return floatval($value);
		}
		else if ($type == 'json')
		{
			$_value = json_decode($value, true);
			if (!is_array($_value))
			{
				$this->stripslashes($_value);
				$_value = trim($_value, '"');
			}
			return $_value;
		}
		else if ($type == 'string')
		{
			return	$this->stripslashes($value);
		}

		//Sanity check
		if (!$this->valid_field_type($type))
		{
			throw new Exception(sprintf('Invalid type "%s"', $type));
		}

		return $value;
	}

	function valid_field_type($type)
	{
		$valid_types = array('int', 'decimal', 'string', 'json');
		return in_array($type, $valid_types);
	}

	function stripslashes(&$value)
	{
		return	htmlspecialchars_decode(
			stripslashes(
				str_replace(
					array('&amp;', '&#40;', '&#41;', '&#61;', '&#8722;&#8722;', '&#59;'),
					array('&', '(', ')', '=', '--', ';'),
					(string)$value
				)
			),
			ENT_QUOTES
		);
	}

	/**
	 * Execute prepared SQL statement for insert and update
	 *
	 * @param string $sql
	 * @param array $valueset  values,id and datatypes for the insert
	 * Use type = PDO::PARAM_STR for strings and type = PDO::PARAM_INT for integers
	 * @return boolean TRUE on success or FALSE on failure
	 */

	public function insert($sql, $valueset, $line = '', $file = '')
	{
		try
		{
			$statement_object = $this->db->prepare($sql);
			foreach ($valueset as $fields)
			{
				foreach ($fields as $field => $entry)
				{
					$statement_object->bindParam($field, $entry['value'], $entry['type']);
				}
				$ret = $statement_object->execute();
			}
		}
		catch (\PDOException $e)
		{
			trigger_error('Error: ' . $e->getMessage() . "<br>SQL: $sql\n in File: $file\n on Line: $line\n", E_USER_ERROR);
		}
		return $ret;
	}

	/**
	 * Execute prepared SQL statement for delete
	 *
	 * @param string $sql
	 * @param array $valueset  values,id and datatypes for the insert
	 * Use type = PDO::PARAM_STR for strings and type = PDO::PARAM_INT for integers
	 * @return boolean TRUE on success or FALSE on failure
	 */
	public function delete($sql, $valueset, $line = '', $file = '')
	{
		try
		{
			$statement_object = $this->db->prepare($sql);
			foreach ($valueset as $fields)
			{
				foreach ($fields as $field => $entry)
				{
					$statement_object->bindParam($field, $entry['value'], $entry['type']);
				}
				$ret = $statement_object->execute();
			}
		}
		catch (\PDOException $e)
		{
			trigger_error('Error: ' . $e->getMessage() . "<br>SQL: $sql\n in File: $file\n on Line: $line\n", E_USER_ERROR);
		}
		return $ret;
	}

	public function metadata($table)
	{
		$db_type = self::$config['db_type'];

		switch ($db_type)
		{
			case 'pgsql':
			case 'postgres':
				$stmt = $this->db->prepare("SELECT column_name, data_type, character_maximum_length
				FROM   information_schema.columns
				WHERE  table_schema = 'public'
				AND    table_name   = :table");
				$stmt->execute(['table' => $table]);

				$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
				break;
			case 'mysql':
				$stmt = $this->db->prepare("SHOW COLUMNS FROM $table");
				$stmt->execute();
				$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
				break;
			case 'mssql':
			case 'mssqlnative':
				$stmt = $this->db->prepare("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_NAME = :table");
				$stmt->execute(['table' => $table]);
				$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
				break;
			case 'oci8':
				$stmt = $this->db->prepare("SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH
				FROM ALL_TAB_COLUMNS
				WHERE TABLE_NAME = :table");
				$stmt->execute(['table' => $table]);
				$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
				break;
			default:
				throw new Exception("Database type not supported");
		}

		return $meta;
	}

	public static function datetime_format()
	{
		return 'Y-m-d H:i:s';
	}

	public static function date_format()
	{
		return 'Y-m-d H:i:s';
	}
	/**
	 * Convert a unix timestamp to a rdms specific timestamp
	 *
	 * @param int unix timestamp
	 * @return string rdms specific timestamp
	 */
	public function to_timestamp($epoch)
	{
		return date($this->datetime_format(), $epoch);
	}

	/**
	 * Convert a rdms specific timestamp to a unix timestamp
	 *
	 * @param string rdms specific timestamp
	 * @return int unix timestamp
	 */
	public function from_timestamp($timestamp)
	{
		return strtotime($timestamp);
	}

	public function get_domain()
	{
		return self::$domain;
	}

	public function set_domain($domain)
	{
		self::$domain = $domain;
	}

	public function set_config($config)
	{
		self::$config = $config;
	}

	public function get_config()
	{
		return self::$config;
	}

	/**
	 * Finds the next ID for a record at a table
	 *
	 * @param string $table tablename in question
	 * @param array $key conditions
	 * @return int the next id
	 */

	final public function next_id($table = '', $key = '', $min = 0, $max = 0)
	{

		$where = '';
		$condition = array();
		$params = array();
		if (is_array($key))
		{
			foreach ($key as $column => $value)
			{
				if ($value)
				{
					$condition[] = $column . " = :" . $column;
					$params[$column] = $value;
				}
			}

			if ($condition)
			{
				$where = 'WHERE ' . implode(' AND ', $condition);
			}
		}

		$stmt = $this->db->prepare("SELECT max(id) as maximum FROM $table $where");
		$stmt->execute($params);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$next_id = (int)$row['maximum'] + 1;

		$update = false;
		if ($next_id == 1)
		{
			$columns = implode(',', array_keys($params));
			$values = ':' . implode(', :', array_keys($params));
			$stmt = $this->db->prepare("INSERT INTO $table (id, $columns) VALUES (:next_id, $values)");
			$stmt->execute(array_merge(['next_id' => $next_id], $params));
	//		_debug_array(array_merge(['next_id' => $next_id], $params));
		}
		else
		{
			$update = true;
		}

		//if the next id is less than the minimum id, set it to the minimum id
		if ($next_id < $min)
		{
			$next_id = $min;
		}
		if ($update)
		{
			// update the table {$table} SET id = {$next id} +1
			$stmt = $this->db->prepare("UPDATE $table SET id = id +1 $where");
			$stmt->execute($params);

		}
		return $next_id;
	}

	/**
	 * Get a list of table names in the current database
	 * @param bool $include_views include views in the listing if any (optional)
	 *
	 * @return array list of the tables
	 */
	public function table_names($include_views = null)
	{
		$return = array();

		if (!isset(self::$config['db_type']))
		{
			return $return;
		}

		$db_type = self::$config['db_type'];

		switch ($db_type)
		{
			case 'pgsql':
			case 'postgres':
				$stmt = $this->db->prepare("SELECT table_name as name, CAST(table_type = 'VIEW' AS INTEGER) as view
				FROM information_schema.tables
				WHERE table_schema = current_schema()");
				$stmt->execute();

				while ($entry = $stmt->fetch(PDO::FETCH_ASSOC))
				{
					if ($include_views)
					{
						$return[] =  $entry['name'];
					}
					else
					{
						if (!$entry['view'])
						{
							$return[] =  $entry['name'];
						}
					}
				}
				break;
			case 'mysql':

				$sql = "SHOW FULL TABLES";

				if (!$include_views)
				{
					$sql .= " WHERE Table_Type != 'VIEW'";
				}
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				//insert the result into the return array
				while ($entry = $stmt->fetch(PDO::FETCH_NUM))
				{
					$return[] = $entry[0];
				}

				break;
			case 'mssql':
			case 'mssqlnative':
				$stmt = $this->db->prepare("SELECT name FROM sysobjects WHERE xtype='U'");
				$stmt->execute();
				while ($entry = $stmt->fetch(PDO::FETCH_ASSOC))
				{
					$return[] = $entry['name'];
				}
				break;
			case 'oci8':
				$stmt = $this->db->prepare("SELECT TABLE_NAME as name, TABLE_TYPE as table_type FROM cat");
				$stmt->execute();
				while ($entry = $stmt->fetch(PDO::FETCH_ASSOC))
				{
					if ($include_views)
					{
						$return[] = $entry['name'];
					}
					else
					{
						if ($entry['table_type'] == 'TABLE')
						{
							$return[] = $entry['name'];
						}
					}
				}

				break;
			default:
				throw new Exception("Database type not supported");
		}

		return $return;
	}

	public function set_halt_on_error($halt_on_error = '')
	{

		switch ($halt_on_error)
		{
			case 'yes':
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				break;
			case 'report':
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
				break;
			default:
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
		}
	}

	/**
	 * Implement the create database method
	 */
	public function create_database()
	{
	}

	/**
	 * Returns an associate array of foreign keys, or false if not supported.
	 *
	 * @param string $table name of table to describe
	 * @param boolean $primary optional, default False.
	 * @return array Index data
	 */

	public function metaindexes($table, $primary = false)
	{
		$db_type = self::$config['db_type'];

		switch ($db_type)
		{
			case 'pgsql':
			case 'postgres':
				$stmt = $this->db->prepare("SELECT
					c.relname as name,
					i.indisunique as unique,
					i.indisprimary as primary,
					pg_get_indexdef(i.indexrelid) as definition
				FROM
					pg_catalog.pg_class c,
					pg_catalog.pg_class c2,
					pg_catalog.pg_index i
				WHERE
					c.relname = :table
					and c.oid = i.indrelid
					and i.indexrelid = c2.oid
					and i.indisprimary = :primary");

				$primary = filter_var($primary, FILTER_VALIDATE_BOOLEAN);
				if ($primary)
				{
					$primary = 'true';
				}
				else
				{
					$primary = 'false';
				}

				$stmt->execute(['table' => $table, 'primary' => $primary]);
				$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
				break;
			default:
				throw new Exception("Database type not supported");
		}

		foreach ($meta as &$entry)
		{
			preg_match('/\((.*?)\)/', $entry['definition'], $matches);
			$columns = explode(',', $matches[1]);
			$entry['columns'] = array_map('trim', $columns);
		}

		return $meta;
	}

	/**
	 * Execute a query
	 *
	 * @param string $sql the query to be executed
	 * @param mixed $line the line method was called from - use __LINE__
	 * @param string $file the file method was called from - use __FILE__
	 * @param bool $exec true for exec, false for query
	 * @param bool $fetch_single true for using fetch, false for fetchAll
	 * @return integer current query id if sucesful and null if fails
	 */
	public function query($sql, $line = '', $file = '', $exec = false, $_fetch_single = false)
	{

		/**
		 * Note: JSONB operator '?' is troublesom: convert to '~@'
		 * CREATE OPERATOR ~@ (LEFTARG = jsonb, RIGHTARG = text, PROCEDURE = jsonb_exists);
		 * CREATE OPERATOR ~@| (LEFTARG = jsonb, RIGHTARG = text[], PROCEDURE = jsonb_exists_any);
		 * CREATE OPERATOR ~@& (LEFTARG = jsonb, RIGHTARG = text[], PROCEDURE = jsonb_exists_all);
		 */

		if (in_array(self::$config['db_type'], array('mssql', 'mssqlnative')))
		{
			if (preg_match('/(^SELECT)/i', $sql) && !preg_match('/TOP 100 PERCENT/i', $sql))
			{
				$sql = str_replace(array('SELECT', 'SELECT TOP 100 PERCENT DISTINCT'), array('SELECT TOP 100 PERCENT', 'SELECT DISTINCT TOP 100 PERCENT'), $sql);
			}
		}

		self::_get_fetchmode();
		self::set_fetch_single($_fetch_single);
		$exec = false;

		$fetch_single = $this->fetch_single;

		$fetch = true;
		if (preg_match('/(^INSERT INTO|^DELETE FROM|^CREATE|^DROP|^ALTER|^UPDATE|^SET)/i', $sql)) // need it for MySQL and Oracle
		{
			$fetch = false;
		}
		if (preg_match('/(^SET)/i', $sql)) // need it for MSSQL
		{
			$exec = true;
		}

		try
		{

			if ($exec)
			{
				$this->affected_rows = $this->db->exec($sql);
				return true;
			}
			else
			{
				if ($statement_object = $this->db->query($sql))
				{
					$this->affected_rows = $statement_object->rowCount();
				}
				if ($fetch)
				{
					if ($fetch_single)
					{
						$this->resultSet = $statement_object->fetch($this->pdo_fetchmode);
						$this->statement_object = $statement_object;
						unset($statement_object);
					}
					else
					{
						$this->resultSet = $statement_object->fetchAll($this->pdo_fetchmode);
					}
				}
			}
		}
		catch (PDOException $e)
		{
			if ($e && !$this->Exception_On_Error && $this->Halt_On_Error == 'yes')
			{
				$this->transaction_abort();

				if ($file)
				{
					$msg = "SQL: {$sql}<br/><br/> in File: $file<br/><br/> on Line: $line<br/><br/>";
					$msg .= 'Error: ' . ($e->getMessage());
					trigger_error($msg, E_USER_ERROR);
				}
				else
				{
					trigger_error("$sql\n" . $e->getMessage(), E_USER_ERROR);
				}
				exit;
			}
			else if ($this->Exception_On_Error && $this->Halt_On_Error == 'yes')
			{
				$this->transaction_abort();
				throw $e;
			}
			else if ($this->Exception_On_Error && $this->Halt_On_Error != 'yes')
			{
				throw $e;
			}
		}
		$this->delayPointer = true;
		return true;
	}


	/**
	 * Execute a query with limited result set
	 *
	 * @param string $sql the query to be executed
	 * @param integer $offset row to start from
	 * @param integer $line the line method was called from - use __LINE__
	 * @param string $file the file method was called from - use __FILE__
	 * @param integer $num_rows number of rows to return (optional), if unset will use $GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs']
	 * @return integer current query id if sucesful and null if fails
	 */

	function limit_query($sql, $offset, $line = '', $file = '', $num_rows = 0)
	{
		//			self::sanitize($sql);//killing performance

		$this->_get_fetchmode();

		$sql = $this->get_offset($sql, $offset, $num_rows);

		if ($this->debug)
		{
			printf("Debug: limit_query = %s<br />offset=%d, num_rows=%d<br />\n", $sql, $offset, $num_rows);
		}

		try
		{
			$statement_object = $this->db->query($sql);
			$this->resultSet = $statement_object->fetchAll($this->pdo_fetchmode);
		}
		catch (PDOException $e)
		{
			if ($e && !$this->Exception_On_Error && $this->Halt_On_Error == 'yes')
			{
				$this->transaction_abort();

				if ($file)
				{
					trigger_error('Error: ' . $e->getMessage() . "<br>SQL: $sql\n in File: $file\n on Line: $line\n", E_USER_ERROR);
				}
				else
				{
					trigger_error("$sql\n" . $e->getMessage(), E_USER_ERROR);
				}
				exit;
			}
			else if ($this->Exception_On_Error && $this->Halt_On_Error == 'yes')
			{
				$this->transaction_abort();
				throw $e;
			}
			else if ($this->Exception_On_Error && $this->Halt_On_Error != 'yes')
			{
				throw $e;
			}
		}

		$this->delayPointer = true;
		return true;
	}

	/**
	 * Move to the next row in the results set
	 *
	 * @return bool was another row found?
	 */
	public function next_record()
	{
		if ($this->fetch_single)
		{
			if ($this->delayPointer)
			{
				$this->delayPointer = false;
			}
			else
			{
				$this->resultSet = $this->statement_object->fetch($this->pdo_fetchmode);
			}
			$this->Record = $this->resultSet;
			return !!$this->Record;
		}
		if ($this->resultSet && current($this->resultSet))
		{
			if ($this->delayPointer)
			{
				$this->delayPointer = false;
				$this->Record = current($this->resultSet);
				return true;
			}

			$row = next($this->resultSet);
			$this->Record = &$row;
			return !!$row;
		}
		return false;
	}


	function get_offset($sql = '', $offset = 0, $num_rows = 0)
	{
		$offset		=  $offset < 0 ? 0 : (int)$offset;
		$num_rows	= (int)$num_rows;

		if ($num_rows <= 0)
		{
			$maxmatches = isset($GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs']) ? (int)$GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs'] : 15;
			$num_rows = $maxmatches;
		}

		switch (self::$config['db_type'])
		{
			case 'mssqlnative':
			case 'mssql':
				$sql = str_replace('SELECT ', 'SELECT TOP ', $sql);
				$sql = str_replace('SELECT TOP DISTINCT', 'SELECT DISTINCT TOP ', $sql);
				$sql = str_replace('TOP ', 'TOP ' . ($offset + $num_rows) . ' ', $sql);
				break;
			case 'oci8':
			case 'oracle':
				//http://www.oracle.com/technology/oramag/oracle/06-sep/o56asktom.html
				//http://dibiphp.com
				if ($offset > 0)
				{
					$sql = 'SELECT * FROM (SELECT t.*, ROWNUM AS "__rownum" FROM (' . $sql . ') t ' . ($num_rows >= 0 ? 'WHERE ROWNUM <= '
						. ($offset + $num_rows) : '') . ') WHERE "__rownum" > ' .  $offset;
				}
				elseif ($num_rows >= 0)
				{
					$sql = "SELECT * FROM ({$sql}) WHERE ROWNUM <= {$num_rows}";
				}

				break;
			default:
				$sql .= " LIMIT {$num_rows}";
				$sql .=  $offset ? " OFFSET {$offset}" : '';
		}
		return $sql;
	}

	/**
	 * Return the value of a filed
	 *
	 * @param string $String name of field
	 * @param boolean $strip_slashes string escape chars from field(optional), default false
	 * @return string the field value
	 */
	public function f($name, $strip_slashes = False)
	{
		if ($this->resultSet)
		{
			if (isset($this->Record[$name]))
			{
				if ($strip_slashes)
				{
					return $this->unmarshal($this->Record[$name], 'string');
				}
				else
				{
					return $this->Record[$name];
				}
			}
			return '';
		}
	}


	/**
	 * Get the number of rows affected by last update
	 *
	 * @return integer number of rows
	 */
	public function affected_rows()
	{
		return $this->affected_rows;
	}
	protected function _get_fetchmode()
	{
		if ($this->fetchmode == 'ASSOC')
		{
			$this->pdo_fetchmode = PDO::FETCH_ASSOC;
		}
		else
		{
			$this->pdo_fetchmode = PDO::FETCH_BOTH;
		}
	}

	/**
	 * set_fetch_single:fetch single record from pdo-object
	 *
	 * @param bool    $value true/false
	 */
	public function set_fetch_single($value = false)
	{
		$this->fetch_single = $value;
	}


	/**
	 * Prepare the VALUES component of an INSERT sql statement by guessing data types
	 *
	 * It is not a good idea to rely on the data types determined by this method if
	 * you are inserting numeric data into varchar/text fields, such as street numbers
	 *
	 * @param array $value_set array of values to insert into the database
	 * @return string the prepared sql, empty string for invalid input
	 */
	public function validate_insert($values)
	{
		if (!is_array($values) || !count($values))
		{
			return '';
		}

		$insert_value = array();
		foreach ($values as $value)
		{
			if ($value && $this->isJson($value))
			{
				if (self::$config['db_type'] == 'mysql')
				{
					$insert_value[] = "'" . str_ireplace(array('\\'), array('\\\\'), $value) . "'";
				}
				else
				{
					$insert_value[]	= "'{$value}'"; //already escaped by json_encode()
				}
			}
			else if ($value || (is_numeric($value) && $value == 0))
			{
				if (is_numeric($value))
				{
					$insert_value[]	= "'{$value}'";
				}
				else
				{
					$insert_value[]	= "'" . $this->db_addslashes(stripslashes($value)) . "'"; //in case slashes are already added.
				}
			}
			else
			{
				$insert_value[]	= 'NULL';
			}
		}
		return implode(",", $insert_value);
	}

	final public function isJson($string)
	{
		if (!preg_match('/^{/', $string))
		{
			return false;
		}
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}

	/**
	 * Prepare the SET component of an UPDATE sql statement
	 *
	 * @param array $value_set associative array of values to update the database with
	 * @return string the prepared sql, empty string for invalid input
	 */
	public function validate_update($value_set)
	{
		if (!is_array($value_set) || !count($value_set))
		{
			return '';
		}

		$value_entry = array();
		foreach ($value_set as $field => $value)
		{
			if ($value && $this->isJson($value))
			{
				$value_entry[] = "{$field}='{$value}'"; //already escaped by json_encode()
			}
			else if ($value || (is_numeric($value) && $value == 0))
			{
				if (is_numeric($value))
				{
					if ((strlen($value) > 1 && strpos($value, '0') === 0))
					{
						$value_entry[] = "{$field}='{$value}'";
					}
					else
					{
						$value_entry[] = "{$field}={$value}";
					}
				}
				else
				{
					$value_entry[] = "{$field}='" . $this->db_addslashes(stripslashes($value)) . "'"; //in case slashes are already added.
				}
			}
			else
			{
				$value_entry[] = "{$field}=NULL";
			}
		}
		return implode(',', $value_entry);
	}


	/**
	 * Get the current id in the next_id table for a particular application/class
	 *
	 * @param string $table table name to get the id from
	 * @param array $key Application name to get the id for
	 * @param integer $min Minimum of id range
	 * @param integer $max Maximum of id range
	 * @return integer|boolean Last used id or false
	 */
	public function last_id($table = '', $key = '', $min = 0, $max = 0)
	{
		if (!$key)
		{
			return -1;
		}

		$where = '';
		$condition = array();
		$params = array();
		if (is_array($key))
		{
			foreach ($key as $column => $value)
			{
				if ($value)
				{
					$condition[] = $column . " = :" . $column;
					$params[$column] = $value;
				}
			}

			if ($condition)
			{
				$where = 'WHERE ' . implode(' AND ', $condition);
			}
		}

		$stmt = $this->db->prepare("SELECT id FROM $table $where");
		$stmt->execute($params);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$id = (int)$row['id'];

		//TODO: Implement this
		/*
		if (empty($id))
		{
			$id = 1;
			if ($min)
			{
				$id = $min;
			}

			$this->query("INSERT INTO phpgw_nextid (appname,id) VALUES ('{$appname}', {$id})", __LINE__, __FILE__);
			return $id;
		}
		else if ($id < $min)
		{
			$id = $min;
			$this->query("UPDATE phpgw_nextid SET id = {$id} WHERE appname='{$appname}'", __LINE__, __FILE__);
			return $id;
		}
		else if ($max && ($id > $max))
		{
			return 0;
		}
	*/
		return (int) $id;
	}
}
