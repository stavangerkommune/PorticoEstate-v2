<?php

namespace App\modules\bim\helpers;

/**
 * RedirectHelper
 *
 * @package bim
 */
class RedirectHelper
{
	public function process()
	{
		\phpgw::redirect_link('/', array('menuaction' => 'bim.uibim.showModels'));
	}
}
