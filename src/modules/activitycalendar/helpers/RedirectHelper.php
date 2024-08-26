<?php

namespace App\modules\activitycalendar\helpers;

class RedirectHelper
{
	public function process()
	{
		$start_page = array(
			'menuaction' => 'activitycalendar.uidashboard.index'
		);

		\phpgw::redirect_link('/', $start_page);
	}
}
