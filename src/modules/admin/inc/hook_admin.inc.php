<?php
	/**************************************************************************\
	* phpGroupWare - administration                                            *
	* http://www.phpgroupware.org                                              *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/
	/* $Id$ */

	if (! $GLOBALS['phpgw']->acl->check('site_config_access',1,'admin'))
	{
		$file['Site Configuration'] = phpgw::link('/index.php', array('menuaction' => 'admin.uiconfig.index', 'appname' => 'admin') );
	}

	/*
	if (! $GLOBALS['phpgw']->acl->check('peer_server_access',1,'admin'))
	{
		$file['Peer Servers'] = phpgw::link('/index.php', array('menuaction' => 'admin.uiserver.list_servers') );
	}
	*/

	if (! $GLOBALS['phpgw']->acl->check('account_access',1,'admin'))
	{
		$file['addressmasters'] = phpgw::link('/index.php', array('menuaction' => 'admin.uiaclmanager.list_addressmasters',
											'account_id' => $GLOBALS['phpgw_info']['user']['account_id']) );
	}

	if (! $GLOBALS['phpgw']->acl->check('account_access',1,'admin'))
	{
		$file['User Accounts'] = phpgw::link('/index.php', array('menuaction' => 'admin.uiaccounts.list_users') );
	}

	if (! $GLOBALS['phpgw']->acl->check('group_access',1,'admin'))
	{
		$file['User Groups'] = phpgw::link('/index.php', array('menuaction' => 'admin.uiaccounts.list_groups') );
	}

	if (! $GLOBALS['phpgw']->acl->check('applications_access',1,'admin'))
	{
		$file['Applications'] = phpgw::link('/index.php', array('menuaction' => 'admin.uiapplications.get_list') );
	}

	if (! $GLOBALS['phpgw']->acl->check('global_categories_access',1,'admin'))
	{
		$file['Global Categories'] = phpgw::link('/index.php', array('menuaction' => 'admin.uicategories.index') );
	}

	if (! $GLOBALS['phpgw']->acl->check('custom_fields_access',1,'admin'))
	{
		$file['custom functions'] = phpgw::link('/index.php', array('menuaction' => 'admin.ui_custom.list_custom_function', 'appname' => 'tts') );
	}

	if (!$GLOBALS['phpgw']->acl->check('mainscreen_message_access',1,'admin') || !$GLOBALS['phpgw']->acl->check('mainscreen_message_access',2,'admin'))
	{
		$file['Change Main Screen Message'] = phpgw::link('/index.php', array('menuaction' => 'admin.uimainscreen.index') );
	}

	if (! $GLOBALS['phpgw']->acl->check('current_sessions_access',1,'admin'))
	{
		$file['View Sessions'] = phpgw::link('/index.php', array('menuaction' => 'admin.uicurrentsessions.list_sessions') );
	}

	if (! $GLOBALS['phpgw']->acl->check('access_log_access',1,'admin'))
	{
		$file['View Access Log'] = phpgw::link('/index.php', array('menuaction' => 'admin.uiaccess_history.list_history') );
	}

	if (! $GLOBALS['phpgw']->acl->check('error_log_access',1,'admin'))
	{
		$file['View Error Log']  = phpgw::link('/index.php', array('menuaction' => 'admin.uilog.list_log') );
	}

	if (! $GLOBALS['phpgw']->acl->check('error_log_access',1,'admin'))
	{
		$file['Edit Log Levels']  = phpgw::link('/index.php', array('menuaction' => 'admin.uiloglevels.edit_log_levels') );
	}

	if (! $GLOBALS['phpgw']->acl->check('applications_access',16,'admin'))
	{
		$file['Find and Register all Application Hooks'] = phpgw::link('/index.php', array('menuaction' => 'admin.uiapplications.register_all_hooks') );
	}

	if (! $GLOBALS['phpgw']->acl->check('asyncservice_access',1,'admin'))
	{
		$file['Asynchronous timed services'] = phpgw::link('/index.php', array('menuaction' => 'admin.uiasyncservice.index') );
	}

	if (! $GLOBALS['phpgw']->acl->check('info_access',1,'admin'))
	{
		$file['phpInfo'] = "javascript:openwindow('" . phpgw::link('/admin/phpinfo.php') . "','700','600')";
	}
 
	/* Do not modify below this line */

	$GLOBALS['phpgw']->common->display_mainscreen('admin',$file);
