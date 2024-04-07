<?php
namespace App\Database;
use PDO;
use Exception;

class Db extends PDO
{
	private static $instance = null;
	private $isTransactionActive = false;
	private static $domain;
	private static $config;

	private function __construct($dsn, $username = null, $password = null, $options = null)
	{
		parent::__construct($dsn, $username, $password, $options);
	}

	public static function getInstance($dsn='', $username = null, $password = null, $options = null)
	{
		if (self::$instance === null) {
			self::$instance = new self($dsn, $username, $password, $options);
		}
		return self::$instance;
	}

	public function transaction_begin()
	{
		if (!$this->isTransactionActive) {
			parent::beginTransaction();
			$this->isTransactionActive = true;
		}
	}

	public function transaction_commit()
	{
		if ($this->isTransactionActive) {
			$commitSuccess = parent::commit();
			$this->isTransactionActive = false;
			return $commitSuccess;
		}
		return false;
	}
	
	public function transaction_abort()
	{
		if ($this->isTransactionActive) {
			parent::rollBack();
			$this->isTransactionActive = false;
		}
	}

	public function db_addslashes($string)
	{
		if ($string === '' || $string === null) {
			return '';
		}
		return substr(parent::quote($string), 1, -1);
	}

	public function get_transaction()
	{
		return $this->isTransactionActive;
	}

	public function unmarshal($value, $type)
	{
		$type = strtolower($type);
		/* phpgw always returns empty strings (i.e '') for null values */
		if (($value === null) || ($type != 'string' && (strlen(trim($value)) === 0))) {
			return null;
		} else if ($type == 'int') {
			return intval($value);
		} else if ($type == 'decimal') {
			return floatval($value);
		} else if ($type == 'json') {
			$_value = json_decode($value, true);
			if (!is_array($_value)) {
				$this->stripslashes($_value);
				$_value = trim($_value, '"');
			}
			return $_value;
		} else if ($type == 'string') {
			return	$this->stripslashes($value);
		}

		//Sanity check
		if (!$this->valid_field_type($type)) {
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
		try {
			$statement_object = $this->prepare($sql);
			foreach ($valueset as $fields) {
				foreach ($fields as $field => $entry) {
					$statement_object->bindParam($field, $entry['value'], $entry['type']);
				}
				$ret = $statement_object->execute();
			}
		} catch (\PDOException $e) {
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
		try {
			$statement_object = $this->prepare($sql);
			foreach ($valueset as $fields) {
				foreach ($fields as $field => $entry) {
					$statement_object->bindParam($field, $entry['value'], $entry['type']);
				}
				$ret = $statement_object->execute();
			}
		} catch (\PDOException $e) {
			trigger_error('Error: ' . $e->getMessage() . "<br>SQL: $sql\n in File: $file\n on Line: $line\n", E_USER_ERROR);
		}
		return $ret;
	}

	public function metadata($table)
	{
		$db_type = self::$config['db_type'];

		switch ($db_type) {
			case 'pgsql':
			case 'postgres':
				$stmt = $this->prepare("SELECT column_name, data_type, character_maximum_length
				FROM   information_schema.columns
				WHERE  table_schema = 'public'
				AND    table_name   = :table");
				$stmt->execute(['table' => $table]);

				$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
				break;
			case 'mysql':
				$stmt = $this->prepare("SHOW COLUMNS FROM $table");
				$stmt->execute();
				$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
				break;
			case 'mssql':
			case 'mssqlnative':
				$stmt = $this->prepare("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
				FROM INFORMATION_SCHEMA.COLUMNS
				WHERE TABLE_NAME = :table");
				$stmt->execute(['table' => $table]);
				$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
				break;
			case 'oci8':
				$stmt = $this->prepare("SELECT COLUMN_NAME, DATA_TYPE, DATA_LENGTH
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

	final public function next_id($table = '', $key = '')
	{
		$where = '';
		$condition = array();
		$params = array();
		if (is_array($key)) {
			foreach ($key as $column => $value) {
				if ($value) {
					$condition[] = $column . " = :" . $column;
					$params[$column] = $value;
				}
			}

			if ($condition) {
				$where = 'WHERE ' . implode(' AND ', $condition);
			}
		}

		$stmt = $this->prepare("SELECT max(id) as maximum FROM $table $where");
		$stmt->execute($params);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$next_id = (int)$row['maximum'] + 1;
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

		$db_type = self::$config['db_type'];

		switch ($db_type) {
			case 'pgsql':
			case 'postgres':
				$stmt = $this->prepare("SELECT table_name as name, CAST(table_type = 'VIEW' AS INTEGER) as view
				FROM information_schema.tables
				WHERE table_schema = current_schema()");
				$stmt->execute();

				while ($entry = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if ($include_views) {
						$return[] =  $entry['name'];
					} else {
						if (!$entry['view']) {
							$return[] =  $entry['name'];
						}
					}
				}
				break;
			case 'mysql':

				$sql = "SHOW FULL TABLES";

				if (!$include_views) {
					$sql .= " WHERE Table_Type != 'VIEW'";
				}
				$stmt = $this->prepare($sql);
				$stmt->execute();
				//insert the result into the return array
				while ($entry = $stmt->fetch(PDO::FETCH_NUM)) {
					$return[] = $entry[0];
				}
	
				break;
			case 'mssql':
			case 'mssqlnative':
				$stmt = $this->prepare("SELECT name FROM sysobjects WHERE xtype='U'");
				$stmt->execute();
				while ($entry = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$return[] = $entry['name'];
				}
				break;
			case 'oci8':
				$stmt = $this->prepare("SELECT TABLE_NAME as name, TABLE_TYPE as table_type FROM cat");
				$stmt->execute();
				while ($entry = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if ($include_views) {
						$return[] = $entry['name'];
					} else {
						if ($entry['table_type'] == 'TABLE') {
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

		switch ($halt_on_error) {
			case 'yes':
				$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				break;
			case 'report':
				$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
				break;
			default:
				$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
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

		switch ($db_type) {
			case 'pgsql':
			case 'postgres':
				$stmt = $this->prepare("SELECT
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
				if ($primary) {
					$primary = 'true';
				} else {
					$primary = 'false';
				}

				$stmt->execute(['table' => $table, 'primary' => $primary]);
				$meta = $stmt->fetchAll(PDO::FETCH_ASSOC);
				break;
			default:
				throw new Exception("Database type not supported");
		}

		foreach ($meta as &$entry) {
			preg_match('/\((.*?)\)/', $entry['definition'], $matches);
			$columns = explode(',', $matches[1]);
			$entry['columns'] = array_map('trim', $columns);
		}

		return $meta;
	}
}