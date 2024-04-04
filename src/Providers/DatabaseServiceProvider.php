<?php
namespace App\Providers;

use PDO;
use App\Database\Db;
use Slim\App;
use Exception;
use PDOException;

class DatabaseServiceProvider
{
    public static function register(App $app)
    {
        $container = $app->getContainer();

		$container->set('db', function () use ($container) {
			$config = $container->get('settings')['db'];
			try {

				switch ($config['db_type']) {
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
				$options = [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_PERSISTENT => true,
				];
				//register the database object in a singleton pattern
				$db = Db::getInstance($dsn, $config['db_user'], $config['db_pass'], $options);
				$db->set_domain($config['domain']);
				$db->set_config($config);
				return $db;
			} catch (PDOException $e) {
				throw new Exception("Error connecting to database: " . $e->getMessage());
			}
		});
    }
}