<?php

namespace App\modules\sms\helpers;

use App\modules\phpgwapi\services\Settings;

class RedirectHelper
{
	public function process()
	{
		$userSettings = Settings::getInstance()->get('user');

		$start_page = 'sms.index';

		if (!empty($userSettings['preferences']['sms']['default_start_page']))
		{
			$start_page = $userSettings['preferences']['sms']['default_start_page'];
		}

		\phpgw::redirect_link('/index.php', array('menuaction' => "sms.ui{$start_page}"));
	}
}
