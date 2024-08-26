<?php

namespace App\modules\activitycalendarfrontend\helpers;

class RedirectHelper
{
	public function process()
	{
		$start_page = array(
			'menuaction' => 'activitycalendarfrontend.uiactivity.add'
		);
		\phpgw::redirect_link('/activitycalendarfrontend/', $start_page);
	}
}
