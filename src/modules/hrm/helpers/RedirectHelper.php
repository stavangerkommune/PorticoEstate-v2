<?php

namespace App\modules\hrm\helpers;

class RedirectHelper
{
	public function process()
	{
		$start_page = array(
			'menuaction' => 'hrm.uiuser.index'
		);

		\phpgw::redirect_link('/', $start_page);
	}
}
