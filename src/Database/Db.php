<?php
namespace App\Database;
use PDO;
use Exception;

class Db extends PDO
{
	private $isTransactionActive = false;

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
			parent::commit();
			$this->isTransactionActive = false;
		}
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
		return parent::quote($string);
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

}