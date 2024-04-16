<?php

namespace App\Database;

use App\Database\Db;
use PDO;
use Exception;
use PDOException;

/**
 * Class Db2  - A non-singleton version of the Db class
 * @package App\Database
 */
class Db2 extends Db
{
	private $db;
	function __construct($dsn, $username = null, $password = null, $options = null)
	{
		try
		{
			$this->db = new PDO($dsn, $username, $password, $options);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch (PDOException $e)
		{
			throw new Exception($e->getMessage());
		}
	}
}
