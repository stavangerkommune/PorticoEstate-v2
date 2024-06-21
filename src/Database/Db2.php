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

	function __construct($dsn= null, $username = null, $password = null, $options = null)
	{
		$config = Db::getInstance()->get_config();
		if (is_null($dsn))
		{
			$dsn = parent::CreateDsn($config);
			$username = $config['db_user'];
			$password = $config['db_pass'];
			$options = [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_EMULATE_PREPARES => false,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			];
		}
		
		try
		{
			$this->db = new PDO($dsn, $username, $password, $options);
		}
		catch (PDOException $e)
		{
			throw new Exception($e->getMessage());
		}
		$this->set_config($config);
	}

	public function set_config($config)
	{
		$this->config = $config;
	}

	public function get_config()
	{
		return $this->config;
	}

}
