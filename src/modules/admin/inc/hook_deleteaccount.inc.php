<?php
  /**************************************************************************\
  * phpGroupWare                                                             *
  * http://www.phpgroupware.org                                              *
  * Written by Mark Peters <skeeter@phpgroupware.org>                        *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

	/* $Id$ */

	$account_id = Sanitizer::get_var('account_id', 'int');
	$serverSettings = \App\modules\phpgwapi\services\Settings::getInstance()->get('server');
	if ( $account_id )
	{
		// delete all mapping to account
		// Using Single Sign-On
		if(isset($serverSettings['mapping']) && ($serverSettings['mapping'] == 'all' || $serverSettings['mapping'] == 'table'))
		{
			$phpgw_map_location = isset($_SERVER['HTTP_SHIB_ORIGIN_SITE']) ? $_SERVER['HTTP_SHIB_ORIGIN_SITE'] : 'local';
			$phpgw_map_authtype = isset($_SERVER['HTTP_SHIB_ORIGIN_SITE']) ? 'shibboleth':'remoteuser';

			$mapping  = CreateObject('phpgwapi.mapping', array('auth_type'=> $phpgw_map_authtype, 'location' => $phpgw_map_location));

			$account = CreateObject('phpgwapi.accounts', $account_id, 'u');
			$data = $account->read();
			$account_lid = $data['account_lid'];
			$mapping ->delete_mapping(array('account_lid' => $account_lid));
		}
	}
