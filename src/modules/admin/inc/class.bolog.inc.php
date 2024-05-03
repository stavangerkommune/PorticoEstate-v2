<?php

/**************************************************************************\
 * phpGroupWare - Administration                                            *
 * http://www.phpgroupware.org                                              *
 * --------------------------------------------                             *
 *  This program is free software; you can redistribute it and/or modify it *
 *  under the terms of the GNU General Public License as published by the   *
 *  Free Software Foundation; either version 2 of the License, or (at your  *
 *  option) any later version.                                              *
	\**************************************************************************/

/* $Id$ */

use App\modules\phpgwapi\services\Log;
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\services\Settings;




class bolog
{
	var $public_functions = array(
		'list_log'		=> True,
		'purge_log'		=> True
	);
	var $so;
	var $userSettings;

	function __construct()
	{
		$this->so       = createobject('admin.solog');
		$this->userSettings = Settings::getInstance()->get('user');
	}

	function list_log($account_id, $start, $order, $sort, $date = null)
	{
		if (Acl::getInstance()->check('error_log_access', 1, 'admin'))
		{
			return array();
		}

		$_records = array();
		$records = $this->so->list_log($account_id, $start, $order, $sort, $date);

		$log = new Log();

		foreach ($records as $record)
		{
			// build and pass the format by hand as we want to show the seconds
			$record['log_date'] = (new \phpgwapi_common)->show_date(
				strtotime($record['log_date']),
				$this->userSettings['preferences']['common']['dateformat'] . ' - H:i:s'
			);

			if (preg_match('/@/', $record['log_account_lid']))
			{
				$t = preg_split('/@/', $record['log_account_lid']);
				$record['log_account_lid'] = $t[0];
			}

			$level_name = $log->get_level_name($record['log_severity']);

			$_records[] = array(
				'log_date'    		=> $record['log_date'],
				'log_account_lid'   => $record['log_account_lid'],
				'log_app'         	=> $record['log_app'],
				'log_severity'      => lang($level_name),
				'log_file' 			=> $record['log_file'],
				'log_line'  		=> $record['log_line'],
				'log_msg'  			=> $record['log_msg']
			);
		}
		return $_records;
	}

	function purge_log($account_id)
	{
		return $this->so->purge_log($account_id);
	}

	function total($account_id, $date = null)
	{
		return $this->so->total($account_id, $date);
	}
}
