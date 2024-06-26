<?php

use App\modules\phpgwapi\security\Sessions;

class bookingfrontend_preferences
{

	public $public_functions = array(
		'set' => true,
	);

	public function __construct()
	{
	}

	public function set()
	{
		$sessions			 = Sessions::getInstance();

		$template_set = Sanitizer::get_var('template_set', 'string', 'POST');

		if ($template_set)
		{
			switch ($template_set)
			{
				case 'bookingfrontend_2':
				case 'bookingfrontend':
					$sessions->phpgw_setcookie('template_set', $template_set, (time() + (60 * 60 * 24 * 14)));
					break;
				default:
					break;
			}
		}

		if (Sanitizer::get_var('lang', 'bool', 'POST'))
		{
			$selected_lang = Sanitizer::get_var('lang', 'string', 'POST');
			$sessions->phpgw_setcookie('selected_lang', $selected_lang, (time() + (60 * 60 * 24 * 14)));
		}
	}
}
