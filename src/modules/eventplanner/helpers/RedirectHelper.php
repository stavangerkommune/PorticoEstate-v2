<?php

namespace App\modules\eventplanner\helpers;

class RedirectHelper
{
	public function process()
	{
		$start_page = array(
			'menuaction' => 'eventplanner.uiapplication.index'
		);

		\phpgw::redirect_link('/', $start_page);
	}
}
