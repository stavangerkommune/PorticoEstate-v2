<?php
namespace App\Services;

class Settings
{
    private static $instance;
    private $settings;
    public $account_id;


    public function __construct($account_id = null )
    {
		$this->account_id = $account_id;
        // Load settings from the database
        $this->settings = $this->loadSettingsFromDatabase();

        if ($account_id)
        {
            $this->set('account_id', $account_id);
        }
    }

    public function setAccountId($account_id)
    {
        $this->account_id = $account_id;
        $this->set('account_id', $account_id);
    }

    public static function getInstance($account_id = null)
    {
        if (null === static::$instance) {
            static::$instance = new static($account_id);
        }
    
        return static::$instance;
    }

    private function loadSettingsFromDatabase()
    {
		return [
        ];
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