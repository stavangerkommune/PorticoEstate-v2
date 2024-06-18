<?php

use App\modules\phpgwapi\services\Settings;

class CronJobs
{
	function __construct()
	{
	}
	
	public function runTask($arg_value, $arg_count)
	{
		require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';

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

		$phpgwapi_common = new \phpgwapi_common();

		Settings::getInstance()->update('flags', ['currentapp' => 'login']);

		$userSettings = array();

		$userSettings['apps']['admin']	 = true;
		$userSettings['domain']		 = $_GET['domain'];
		$userSettings['account_id']	 = -1;
		$userSettings['account_lid']	 = 'cron_job';

		Settings::getInstance()->set('user', $userSettings);

		$destroy_session = false;
		$sessions = \App\modules\phpgwapi\security\Sessions::getInstance();

		if (!$sessions->get_session_id())
		{
			$sessions->set_session_id(md5($phpgwapi_common->randomstring(10)));
			$destroy_session						 = true;
		}

		$num = ExecMethod('property.custom_functions.index', $data);
		// echo date('Y/m/d H:i:s ').$_GET['domain'].': '.($num ? "$num job(s) executed" : 'Nothing to execute')."\n";

		if ($destroy_session)
		{
			$sessions->destroy($sessions->get_session_id());
		}

		$phpgwapi_common->phpgw_exit();

	}
}
