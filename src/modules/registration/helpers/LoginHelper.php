<?php

namespace App\modules\registration\helpers;

use App\modules\phpgwapi\security\Sessions;
use App\modules\phpgwapi\services\Config;
use Sanitizer;

class LoginHelper
{
	public static function process()
	{
		self::login();
	}

	private static function login()
	{

		$sessions = Sessions::getInstance();

		if (!Sanitizer::get_var(session_name(), 'string', 'COOKIE') || !$sessions->verify())
		{
			$config = (new Config('registration'))->read();


			$login		 = $config['anonymous_user'];
			$logindomain = Sanitizer::get_var('domain', 'string', 'GET');
			if ($logindomain && strstr($login, '#') === false)
			{
				$login .= "#{$logindomain}";
			}

			$passwd				 = $config['anonymous_pass'];
			$_POST['submitit']	 = "";

			$sessionid = $sessions->create($login, $passwd);
			if (!$sessionid)
			{
				$lang_denied_message = lang('Anonymous access not correctly configured');
				$reason = '';
				if ($sessions->reason)
				{
					$reason = $sessions->reason;
				}
				echo <<<HTML
<!DOCTYPE html>
<html>
<head>
	<title>Error</title>
	<style>
		body {
			background-color: #f2f2f2;
			font-family: Arial, sans-serif;
		}
		h1 {
			font-size: 48px;
			color: #333333;
			text-align: center;
			margin-top: 100px;
		}
		p {
			font-size: 24px;
			color: #666666;
			text-align: center;
			margin-top: 50px;
		}
		.footer {
			font-size: 14px;
			color: #666666;
			text-align: center;
			position: fixed;
			bottom: 0;
			width: 100%;
			margin-bottom: 10px;
		}
	</style>
</head>
<body>
	<h1>$lang_denied_message</h1>
	<div class="footer">$reason</div>
</body>
</html>

HTML;
				/**
				 * Used for footer on exit
				 */
				require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';
				$phpgwapi_common = new \phpgwapi_common();
				$phpgwapi_common->phpgw_exit(True);
			}
		}
		else
		{
			return true;
		}
	}

}
