<?php

namespace App\modules\todo\helpers;

class RedirectHelper
{
	public function process()
	{
		$parms = array(
			'menuaction' => 'todo.uitodo.show_list'
		);

		\phpgw::redirect_link('/', $parms);
	}
}
