<?php

	class activitycalendarfrontend_menu
	{

		function get_menu()
		{
			$incoming_app = $GLOBALS['phpgw_info']['flags']['currentapp'];
			$GLOBALS['phpgw_info']['flags']['currentapp'] = 'activitycalendarfrontend';

			$menus = array();

			if ($GLOBALS['phpgw']->acl->check('run', Acl::READ, 'admin') || $GLOBALS['phpgw']->acl->check('admin', Acl::ADD, 'activitycalendarfrontend'))
			{
				$menus['admin'] = array
					(
					'index' => array
						(
						'text' => lang('Configuration'),
						'url' => phpgw::link('/index.php', array('menuaction' => 'admin.uiconfig.index',
							'appname' => 'activitycalendarfrontend'))
					),
				);
			}

			$GLOBALS['phpgw_info']['flags']['currentapp'] = $incoming_app;
			return $menus;
		}
	}