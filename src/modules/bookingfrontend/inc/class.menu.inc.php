<?php

	class bookingfrontend_menu
	{

		function get_menu()
		{
			$incoming_app = $GLOBALS['phpgw_info']['flags']['currentapp'];

			$GLOBALS['phpgw_info']['flags']['currentapp'] = 'bookingfrontend';

			$menus = array();

			if ($GLOBALS['phpgw']->acl->check('run', Acl::READ, 'admin') || $GLOBALS['phpgw']->acl->check('admin', Acl::ADD, 'bookingfrontend'))
			{
				$menus['admin'] = array
					(
					'index'			=> array
						(
						'text' => lang('Configuration'),
						'url'  => phpgw::link('/index.php', array('menuaction' => 'admin.uiconfig.index',
							'appname'	 => 'bookingfrontend'))
					),
					'metasettings'	=> array
						(
						'text' => lang('Metadata'),
						'url'  => phpgw::link('/index.php', array('menuaction' => 'booking.uimetasettings.index',
							'appname'	 => 'booking'))
					),
					'multi_domain' => array(
						'text' => $GLOBALS['phpgw']->translation->translate('multi domain', array(), false, 'booking'),
						'url'  => phpgw::link('/index.php', array('menuaction' => 'booking.uigeneric.index',
							'type'		 => 'multi_domain')),
					),
				);
			}

			$GLOBALS['phpgw_info']['flags']['currentapp'] = $incoming_app;
			return $menus;
		}
	}