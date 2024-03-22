<?php
namespace App\Providers;

use PDO;
use App\Database\Db;
use Slim\App;
use Exception;
use PDOException;
use App\Services\DatabaseObject;

class DatabaseServiceProvider
{
    public static function register(App $app)
    {
        $container = $app->getContainer();

		$container->set('db', function () use ($container) {
			$config = $container->get('settings')['db'];

			try {
				$dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";
				$options = [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_PERSISTENT => true,
				];
				$db = new Db($dsn, $config['user'], $config['password'], $options);
				//register the database object in a singleton pattern
				DatabaseObject::getInstance($db);
				return $db;
			} catch (PDOException $e) {
				throw new Exception("Error connecting to database: " . $e->getMessage());
			}
		});
    }
}