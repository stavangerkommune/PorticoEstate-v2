<?php

namespace App\modules\bookingfrontend\helpers;

use App\modules\bookingfrontend\helpers\UserHelper;
use App\modules\phpgwapi\security\Sessions;
use App\modules\phpgwapi\services\Hooks;
use Sanitizer;

require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';

class LogoutHelper
{

	public static function process()
	{
		$sessions = Sessions::getInstance();
		$sessionid = Sanitizer::get_var('bookingfrontendsession');

		$verified = $sessions->verify();

		$bookingfrontend_host = '';
		$external_logout = '';
		if ($verified)
		{
			$config = CreateObject('phpgwapi.config', 'bookingfrontend');
			$config->read();

			$bookingfrontend_host = isset($config->config_data['bookingfrontend_host']) && $config->config_data['bookingfrontend_host'] ? $config->config_data['bookingfrontend_host'] : '';
			$bookingfrontend_host = rtrim($bookingfrontend_host, '/');
			$external_logout = isset($config->config_data['external_logout']) && $config->config_data['external_logout'] ? $config->config_data['external_logout'] : '';

			$frontend_user = new UserHelper();
			$frontend_user->log_off();

			execMethod('phpgwapi.menu.clear');
			$hooks = new Hooks();
			$hooks->process('logout');
			$sessions->destroy($sessionid);
		}

		$forward = Sanitizer::get_var('phpgw_forward', 'int');

		if ($forward)
		{
			$extra_vars['phpgw_forward'] = $forward;
			foreach ($_GET as $name => $value)
			{
				if (preg_match('/phpgw_/', $name))
				{
					$extra_vars[$name] = Sanitizer::clean_value($value);
				}
			}
		}

		$redirect = Sanitizer::get_var('redirect_menuaction', 'string');

		if ($redirect)
		{
			$matches = array();
			$extra_vars['menuaction'] = $redirect;
			foreach ($_GET as $name => $value)
			{
				if (preg_match('/^redirect_([\w\_\-]+)/', $name, $matches) && $matches[1] != 'menuaction')
				{
					$extra_vars[$matches[1]] = Sanitizer::clean_value($value);
				}
			}
		}

		if (!isset($extra_vars['menuaction']))
		{
			// $extra_vars['menuaction'] = 'bookingfrontend.uisearch.index';
		}

		if (!$external_logout)
		{
			\phpgw::redirect_link('/bookingfrontend/', $extra_vars);
		}
		else
		{
			$result_redirect = '';
			if (substr($external_logout, -1) == '=')
			{
				$external_logout = rtrim($external_logout, '=');
				$result_redirect = \phpgw::link('/bookingfrontend/', $extra_vars, true);
			}
			$external_logout_url = "{$external_logout}{$bookingfrontend_host}{$result_redirect}";
			Header("Location: {$external_logout_url}");
		}
		exit;
	
	}
}