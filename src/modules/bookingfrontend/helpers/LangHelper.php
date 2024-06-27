<?php

namespace App\modules\bookingfrontend\helpers;

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\services\Cache;
use App\modules\phpgwapi\security\Sessions;
use App\modules\phpgwapi\services\Translation;


use Sanitizer;

class LangHelper
{
	public function process()
	{
		$userSettings = Settings::getInstance()->get('user');
		$selected_lang = Sanitizer::get_var('selected_lang', 'string', 'COOKIE');

		if (Sanitizer::get_var('lang', 'bool', 'GET'))
		{
			$selected_lang = Sanitizer::get_var('lang', 'string', 'GET');
			$sessions = Sessions::getInstance();
			$sessions->phpgw_setcookie('selected_lang', $selected_lang, (time() + (60 * 60 * 24 * 14)));
		}
		$userlang  = $selected_lang ? $selected_lang : $userSettings['preferences']['common']['lang'];

		$return_data = Cache::system_get('phpgwapi', "lang_{$userlang}", true);

		if(!$return_data)
		{
			$translation = Translation::getInstance();
			$translation->set_userlang($userlang,true);
			$translation->populate_cache();
			$return_data = Cache::system_get('phpgwapi', "lang_{$userlang}", true);
		}

		header('Content-Type: application/json');
		echo json_encode($return_data);
		exit;
	}
}
