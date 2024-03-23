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
				$dsn = "pgsql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}";
				$options = [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_PERSISTENT => true,
				];
				$db = new Db($dsn, $config['db_user'], $config['db_pass'], $options);
				$db->set_domain($config['domain']);
				//register the database object in a singleton pattern
				DatabaseObject::getInstance($db);
				return $db;
			} catch (PDOException $e) {
				throw new Exception("Error connecting to database: " . $e->getMessage());
			}
		});
    }
}