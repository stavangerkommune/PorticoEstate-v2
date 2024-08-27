<?php

namespace App\modules\frontend\helpers;

class RedirectHelper
{
	public function process()
	{

		\phpgw::redirect_link('/', array('menuaction' => 'frontend.uifrontend.index'));
	}
}
