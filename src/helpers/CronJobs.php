<?php

use App\modules\phpgwapi\services\Settings;
require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';

class CronJobs
{
	private $phpgwapi_common;
	function __construct()
	{
		define('ACL_READ', 1);
		define('ACL_ADD', 2);
		define('ACL_EDIT', 4);
		define('ACL_DELETE', 8);
		define('ACL_PRIVATE', 16);
		define('ACL_GROUP_MANAGERS', 32);
		define('ACL_CUSTOM_1', 64);
		define('ACL_CUSTOM_2', 128);
		define('ACL_CUSTOM_3', 256);

		Settings::getInstance()->update('flags', ['currentapp' => 'login']);

		$userSettings = array();

		$userSettings['apps']['admin']	 = true;
		$userSettings['domain']		 = $_GET['domain'];
		$userSettings['account_id']	 = -1;
		$userSettings['account_lid']	 = 'cron_job';

		Settings::getInstance()->set('user', $userSettings);
		$this->phpgwapi_common = new \phpgwapi_common();

	}
	
	public function runTask($arg_value, $arg_count)
	{

		if (!$function = $_SERVER['argv'][2])
		{
			echo "Nothing to execute\n";
			return;
		}

		$data = array('function' => $function, 'enabled' => 1);

		while ($arg_count > 3)
		{
			list($key, $value) = explode('=', $arg_value[3]);
			$data[$key] = $value;
			array_shift($arg_value);
			--$arg_count;
		}

		$destroy_session = false;
		$sessions = \App\modules\phpgwapi\security\Sessions::getInstance();

		if (!$sessions->get_session_id())
		{
			$sessions->set_session_id(md5($this->phpgwapi_common->randomstring(10)));
			$destroy_session						 = true;
		}

		$num = ExecMethod('property.custom_functions.index', $data);
		// echo date('Y/m/d H:i:s ').$_GET['domain'].': '.($num ? "$num job(s) executed" : 'Nothing to execute')."\n";

		if ($destroy_session)
		{
			$sessions->destroy($sessions->get_session_id());
		}


	}

	function CheckRun()
	{
		$userSettings = Settings::getInstance()->get('user');
		echo "Domain: {$userSettings['domain']}\n";
		echo 'Start cron: ' . date('Y/m/d H:i:s ') . "\n";
		$num = (int) ExecMethod('phpgwapi.asyncservice.check_run', 'crontab');
		echo "Number of jobs: {$num}\n";
		echo 'End cron: ' . date('Y/m/d H:i:s ') . "\n";

		$this->phpgwapi_common->phpgw_exit();
	}
}
