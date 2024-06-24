<?php

	class mobilefrontend_menu
	{

		function get_menu()
		{
			$incoming_app = $GLOBALS['phpgw_info']['flags']['currentapp'];
			$GLOBALS['phpgw_info']['flags']['currentapp'] = 'mobilefrontend';

			$menus = array();

			if ($GLOBALS['phpgw']->acl->check('run', Acl::READ, 'admin') || $GLOBALS['phpgw']->acl->check('admin', Acl::ADD, 'mobilefrontend'))
			{
				$menus['admin'] = array
					(
					'index' => array
						(
						'text' => lang('Configuration'),
						'url' => phpgw::link('/index.php', array('menuaction' => 'admin.uiconfig.index',
							'appname' => 'mobilefrontend'))
					),
				);
			}

			$GLOBALS['phpgw_info']['flags']['currentapp'] = $incoming_app;
			return $menus;
		}
	}