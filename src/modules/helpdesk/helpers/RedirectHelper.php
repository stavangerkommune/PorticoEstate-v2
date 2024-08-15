<?php

namespace App\modules\helpdesk\helpers;

class RedirectHelper
{
	public function process()
	{
		$start_page = array(
			'menuaction' => 'helpdesk.uitts.index'
		);

		\phpgw::redirect_link('/', $start_page);
	}
}
