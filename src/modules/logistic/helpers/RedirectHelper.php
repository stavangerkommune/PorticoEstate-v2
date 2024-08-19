<?php

namespace App\modules\logistic\helpers;

class RedirectHelper
{
	public function process()
	{
		$start_page = array(
			'menuaction' => 'logistic.uiproject.index'
		);

		\phpgw::redirect_link('/', $start_page);
	}
}
