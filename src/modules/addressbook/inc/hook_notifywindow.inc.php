<?php

/**************************************************************************\
 * phpGroupWare - Addressbook                                               *
 * http://www.phpgroupware.org                                              *
 * --------------------------------------------                             *
 *  This program is free software; you can redistribute it and/or modify it *
 *  under the terms of the GNU General Public License as published by the   *
 *  Free Software Foundation; either version 2 of the License, or (at your  *
 *  option) any later version.                                              *
  \**************************************************************************/

/* $Id$ */

use App\modules\phpgwapi\services\Settings;

$userSettings = Settings::getInstance()->get('user');

$phpgwapi_common = new \phpgwapi_common();

if (
	$userSettings['apps']['addressbook']
	&& $userSettings['preferences']['addressbook']['mainscreen_showbirthdays']
)
{
	echo "\n<!-- Birthday info -->\n";

	$c = CreateObject('phpgwapi.contacts');
	$qfields = array(
		'contact_id' => 'contact_id',
		'per_first_name'  => 'per_first_name',
		'per_last_name' => 'per_last_name',
		'per_birthday'     => 'per_birthday'
	);
	$now = time() - ((60 * 60) * intval($userSettings['preferences']['common']['tz_offset']));
	$today = $phpgwapi_common->show_date($now, 'n/d/');
	//		echo $today."\n";

	//$bdays = $c->read(0,15,$qfields,$today,'tid=n','','',$userSettings['account_id']);
	$criteria = array('per_birthday' => $today);
	$bdays = $c->get_persons($qfields, 15, 0, '', '', $criteria);
	//while(list($key,$val) = @each($bdays))
	if (is_array($bdays))
	{
		foreach ($bdays as $key => $val)
		{
			$tmp = '<a href="'
				. phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook.view_person', 'ab_id' => $val['contact_id'])) . '">'
				. $val['per_first_name'] . ' ' . $val['per_last_name'] . '</a>';
			echo '<tr><td align="left">' . lang("Today is %1's birthday!", $tmp) . "</td></tr>\n";
		}
	}

	$tomorrow = $phpgwapi_common->show_date($now + 86400, 'n/d/');
	//		echo $tomorrow."\n";

	$criteria = array('per_birthday' => $tomorrow);
	$bdays = $c->get_persons($qfields, 15, 0, '', '', $criteria);
	//$bdays = $c->read(0,15,$qfields,$tomorrow,'tid=n','','',$userSettings['account_id']);

	//while(list($key,$val) = @each($bdays))
	if (is_array($bdays))
	{
		foreach ($bdays as $key => $val)
		{
			$tmp = '<a href="'
				. phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook.view_person', 'ab_id' => $val['contact_id'])) . '">'
				. $val['per_first_name'] . ' ' . $val['per_last_name'] . '</a>';
			echo '<tr><td align="left">' . lang("Tomorrow is %1's birthday.", $tmp) . "</td></tr>\n";
		}
	}
	echo "\n<!-- Birthday info -->\n";
}
