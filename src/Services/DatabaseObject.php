<?php
namespace App\Services;
use PDO;
use Exception;

class DatabaseObject
{
	private static $instance = null;
	private $db;

	private function __construct(PDO $db)
	{
		$this->db = $db;
	}

	public static function getInstance(PDO $db = null)
	{
		if (self::$instance === null) {
			if ($db === null) {
				throw new Exception('No PDO instance provided');
			}
			self::$instance = new DatabaseObject($db);
		}

		return self::$instance;
	}

	public function get($key)
	{
		if ($key === 'db') {
			return $this->db;
		}

		throw new Exception('Invalid key: ' . $key);
	}
}