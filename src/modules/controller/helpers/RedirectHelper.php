<?php

namespace App\modules\controller\helpers;

use App\modules\phpgwapi\services\Settings;

class RedirectHelper
{
	public function process()
	{
		$userSettings = Settings::getInstance()->get('user');
		$flags = Settings::getInstance()->get('flags');
		$currentapp = $flags['currentapp'];

		$start_page = array('menuaction' => $currentapp . '.uicalendar_planner.index');

		if (!empty($userSettings['preferences'][$currentapp]['default_start_page']))
		{
			$start_page = $userSettings['preferences'][$currentapp]['default_start_page'];
		}

		\phpgw::redirect_link('/', $start_page);

	}
}
