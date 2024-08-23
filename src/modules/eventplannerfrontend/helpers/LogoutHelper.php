<?php

namespace App\modules\eventplannerfrontend\helpers;

use App\modules\phpgwapi\security\Sessions;
use App\modules\phpgwapi\services\Hooks;
use Sanitizer;

require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';

class LogoutHelper
{

	public static function process()
	{
		$sessions = Sessions::getInstance();
		$sessionid = Sanitizer::get_var('eventplannerfrontendsession');

		$verified = $sessions->verify();

		$eventplannerfrontend_host = '';
		$external_logout = '';
		if ($verified)
		{
			$config = CreateObject('phpgwapi.config', 'eventplannerfrontend');
			$config->read();

			$eventplannerfrontend_host = isset($config->config_data['eventplannerfrontend_host']) && $config->config_data['eventplannerfrontend_host'] ? $config->config_data['eventplannerfrontend_host'] : '';
			$eventplannerfrontend_host = rtrim($eventplannerfrontend_host, '/');
			$external_logout = isset($config->config_data['external_logout']) && $config->config_data['external_logout'] ? $config->config_data['external_logout'] : '';

			$frontend_user = CreateObject('eventplannerfrontend.bouser');
			$frontend_user->log_off();

			execMethod('phpgwapi.menu.clear');
			$hooks = new Hooks();
			$hooks->process('logout');
			$sessions->destroy($sessionid);
		}

		$login = Sanitizer::get_var('login', 'bool');

		if ($login)
		{
			\phpgw::redirect_link('/eventplannerfrontend/login/', array('after' => Sanitizer::get_var('after', 'raw')));
		}

		\phpgw::redirect_link('/eventplannerfrontend/', array('cd' => 1, 'logout' => true));
	}
}
