<?php
namespace App\Modules\PhpGWApi\Services;
use PDO;
use App\Helpers\DebugArray;
use Exception;

class Settings
{
    private static $instance;
    private $settings;
    private $db;
    private $config_data;
	private $account_id; 

    private function __construct($account_id = null)
    {
		$this->db = \App\Database\Db::getInstance();
		$this->account_id = $account_id;
        // Load settings from the database
        $this->settings = $this->loadSettingsFromDatabase();
		if ($account_id) {
			$this->set('account_id', $account_id);
		}
    }

    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function loadSettingsFromDatabase()
    {
       return $this->read_repository('phpgwapi');
    }

    private function read_repository($module)
    {

        static $data_cache = array();

        if(!empty($data_cache[$module]))
        {
            $this->config_data = $data_cache[$module];
            return $this->config_data;
        }

		$rootDir = dirname(__DIR__, 4);

		$settings = require $rootDir . '/config/header.inc.php';
		$this->config_data = $settings['phpgw_info'];

//		_debug_array($this->config_data);die();
		
		//$this->config_data  = require_once SRC_ROOT_PATH . '/../config/config.php';
		require_once (SRC_ROOT_PATH . '/Modules/PhpGWApi/Setup/setup.inc.php');

		/**
		 * check if the config table phpgw_config exists
		 */
		$tables = $this->db->table_names();
		if(!in_array('phpgw_config', $tables))
		{

		}
		else
		{
        
			$stmt = $this->db->prepare("SELECT * FROM phpgw_config WHERE config_app=:module");
			$stmt->execute([':module' => $module]);
		
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$test = @unserialize($row['config_value']);
				if ($test) {
					$this->config_data['server'][$row['config_name']] = $test;
				} else {
					$this->config_data['server'][$row['config_name']] = $row['config_value'];
				}
			}
		}
		
		//check if the temp_dir is set and is writable
		if($module == 'phpgwapi' && (empty($this->config_data['temp_dir']) || !is_writable($this->config_data['temp_dir'])))
		{
			$this->config_data['server']['temp_dir'] = '/tmp';
		}

		if($module == 'phpgwapi' && isset($this->config_data['server']['versions']['header']))
		{
			if($this->config_data['server']['versions']['header'] < $setup_info['phpgwapi']['versions']['current_header'] )
			{
				//check in "/setup" is part of php_self
				if(!preg_match('/\/setup/', $_SERVER['PHP_SELF']))
				{
					$msg =  "You need to port your settings to the new header.inc.php version. <a href='/setup'>Run setup now</a>";
					echo "<div style='background-color: #FF0000; color: #FFFFFF; padding: 5px; text-align: center; font-weight: bold;'>$msg</div>";
					exit;
				}
			}
		}

        $this->config_data['server']['default_domain'] = $this->db->get_domain();
        $data_cache[$module] = $this->config_data;
	//	DebugArray::debug($this->config_data);

        return $this->config_data;

    }

    public function get_config_data()
    {
        return $this->config_data;
    }

    public function setAccountId($account_id)
    {
        $this->account_id = $account_id;
        $this->set('account_id', $account_id);
        $this->settings = array_merge($this->settings, array('user' => array('account_id' => $account_id)));
    }

 
    public function set($name, $value)
    {
        $this->settings = array_merge($this->settings, array($name => $value));
    }

    public function get($name)
    {
        return $this->settings[$name] ?? null;
    }
}