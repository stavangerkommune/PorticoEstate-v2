<?php
namespace App\Services;
use PDO;


class ServerSettings
{
    private static $instance;
    private $settings;
    private $db;
    private $config_data;
    

    public function __construct($db = null)
    {
        $this->db = $db;

        // Load settings from the database
        $this->settings = $this->loadSettingsFromDatabase();
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

        // Your code to fetch settings from the database goes here
        // This is just a placeholder
        return [
            'server' => $this->read_repository('phpgwapi')
        ];
    }

    private function read_repository($module)
    {


        static $data_cache = array();

        if(!empty($data_cache[$module]))
        {
            $this->config_data = $data_cache[$module];
            return $this->config_data;
        }

        $this->config_data = array();
        
        $stmt = $this->db->prepare("SELECT * FROM phpgw_config WHERE config_app=:module");
        $stmt->execute([':module' => $module]);

       
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['config_name'] == 'phpgwapi' ? 'server' : $row['config_name'];
            $test = @unserialize($row['config_value']);
            if ($test) {
                $this->config_data[$row['config_name']] = $test;
            } else {
                $this->config_data[$row['config_name']] = $row['config_value'];
            }
        }

        $data_cache[$module] = $this->config_data;

        return $this->config_data;

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