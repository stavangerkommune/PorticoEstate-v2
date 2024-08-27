<?php

namespace App\modules\notes\helpers;

class RedirectHelper
{
	public function process()
	{
		$parms = array(
			'menuaction' => 'notes.uinotes.index'
		);

		\phpgw::redirect_link('/', $parms);
	}
}
