<?php

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\security\Acl;

class mobilefrontend_menu
{

	function get_menu()
	{
		$flags = Settings::getInstance()->get('flags');

		$incoming_app			 = $flags['currentapp'];
		Settings::getInstance()->update('flags', ['currentapp' => 'mobilefrontend']);
		$acl					 =	Acl::getInstance();

		$menus = array();

		if ($acl->check('run', Acl::READ, 'admin') || $acl->check('admin', Acl::ADD, 'mobilefrontend'))
		{
			$menus['admin'] = array(
				'index' => array(
					'text' => lang('Configuration'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'admin.uiconfig.index',
						'appname' => 'mobilefrontend'
					))
				),
			);
		}

		Settings::getInstance()->update('flags', ['currentapp' => $incoming_app]);
		return $menus;
	}
}
