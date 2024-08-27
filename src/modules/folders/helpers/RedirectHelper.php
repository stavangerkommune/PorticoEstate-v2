<?php

namespace App\modules\folders\helpers;

class RedirectHelper
{
	public function process()
	{
		$parms = array(
			'menuaction' => 'folders.uifolders.showFolders'
		);

		\phpgw::redirect_link('/', $parms);
	}
}
