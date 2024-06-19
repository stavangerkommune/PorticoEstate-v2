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
	private $config;

	function __construct($dsn= null, $username = null, $password = null, $options = null)
	{
		$config = Db::getInstance()->get_config();
		if (is_null($dsn))
		{
			switch ($config['db_type'])
			{
				case 'postgres':
				case 'pgsql':
					$dsn = "pgsql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
					break;
				case 'mysql':
					$dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
					break;
				case 'mssqlnative':
					$dsn = "sqlsrv:Server={$config['db_host']},{$config['db_port']};Database={$config['db_name']}";
					break;
				case 'mssql':
				case 'sybase':
					$dsn = "dblib:host={$config['db_host']}:{$config['db_port']};dbname={$config['db_name']}";
					break;
				case 'oci8':
					$port = $config['db_port'] ? $config['db_port'] : 1521;
					$_charset = ';charset=AL32UTF8';
					$dsn = "oci:dbname={$config['db_host']}:{$port}/{$config['db_name']}{$_charset}";
					break;
				default:
					throw new Exception("Database type not supported");
			}

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
