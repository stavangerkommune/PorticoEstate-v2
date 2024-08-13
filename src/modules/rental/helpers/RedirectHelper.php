<?php

namespace App\modules\rental\helpers;

use App\modules\phpgwapi\services\Settings;

class RedirectHelper
{
	public function process()
	{
		$userSettings = Settings::getInstance()->get('user');

		$start_page = 'frontpage';

		if (!empty($userSettings['preferences']['rental']['default_start_page']))
		{
			$start_page = $userSettings['preferences']['rental']['default_start_page'];
		}

		\phpgw::redirect_link('/index.php', array('menuaction' => "rental.ui{$start_page}.index"));
	}
}
