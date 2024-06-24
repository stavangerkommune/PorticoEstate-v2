<?php

namespace App\modules\property\helpers;

use App\modules\phpgwapi\services\Settings;

class RedirectHelper
{
	public function process()
	{
		$userSettings = Settings::getInstance()->get('user');

		$start_page = 'location';

		if (!empty($userSettings['preferences']['property']['default_start_page']))
		{
			$start_page = $userSettings['preferences']['property']['default_start_page'];
		}

		\phpgw::redirect_link('/index.php', array('menuaction' => "property.ui{$start_page}.index"));
	}
}
