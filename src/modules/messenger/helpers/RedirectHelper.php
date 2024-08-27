<?php

namespace App\modules\messenger\helpers;

class RedirectHelper
{
	public function process()
	{
		$parms = array(
			'menuaction' => 'messenger.uimessenger.index'
		);

		\phpgw::redirect_link('/', $parms);
	}
}
