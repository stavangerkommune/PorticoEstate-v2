<?php

/**************************************************************************\
 * phpGroupWare - Administration                                            *
 * http://www.phpgroupware.org                                              *
 *  This file written by Joseph Engo <jengo@phpgroupware.org>               *
 * --------------------------------------------                             *
 *  This program is free software; you can redistribute it and/or modify it *
 *  under the terms of the GNU General Public License as published by the   *
 *  Free Software Foundation; either version 2 of the License, or (at your  *
 *  option) any later version.                                              *
	\**************************************************************************/

/* $Id$ */

use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\security\Sessions;



class bocurrentsessions
{
	var $ui;
	var $so;
	var $public_functions = array(
		'kill' => true
	);

	function total()
	{
		return Sessions::getInstance()->total();
	}

	function list_sessions($start, $order, $sort)
	{

		$acl = Acl::getInstance();
		$view_ip = false;
		if (!$acl->check('current_sessions_access', Acl::EDIT, 'admin'))
		{
			$view_ip = true;
		}

		$view_action = false;
		if (!$acl->check('current_sessions_access', Acl::ADD, 'admin'))
		{
			$view_action = true;
		}

		$values = Sessions::getInstance()->list_sessions($start, $sort, $order);
		foreach ($values as &$value)
		{
			if (preg_match('/^(.*)#(.*)$/', $value['lid'], $m))
			{
				$value['lid'] = $m[1];
			}

			if (!$view_action)
			{
				$value['action'] = ' -- ';
			}

			if (!$view_ip)
			{
				$value['ip'] = ' -- ';
			}

			$value['idle'] = gmdate('G:i:s', time() - $value['dla']);
			$value['logintime'] = (new \phpgwapi_common())->show_date($value['logints']);
		}

		return $values;
	}

	function kill()
	{
		$acl = Acl::getInstance();
		$sessions = Sessions::getInstance();

		if ((isset($_GET['ksession']) && $_GET['ksession']) &&
			($sessions->get_session_id() != $_GET['ksession']) &&
			!$acl->check('current_sessions_access', 8, 'admin')
		)
		{
			$sessions->destroy($_GET['ksession']);
		}
		$this->ui = createobject('admin.uicurrentsessions');
		$this->ui->list_sessions();
	}
}
