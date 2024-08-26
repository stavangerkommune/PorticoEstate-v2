<?php

namespace App\modules\eventplannerfrontend\helpers;

class RedirectHelper
{
	public function process()
	{

		\phpgw::redirect_link('/eventplannerfrontend/home');
	}
}
