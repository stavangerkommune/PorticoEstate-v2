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

use App\helpers\Template;
use App\modules\phpgwapi\services\Cache;
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\services\Settings;


class admin_uicurrentsessions
{
	private $template;

	private $bo;
	private $acl;
	private $userSettings;
	private $serverSettings;
	private $flags;

	public $public_functions = array(
		'list_sessions' => true,
		'kill'          => true
	);

	public function __construct()
	{
		$this->bo = createobject('admin.bocurrentsessions');
		$this->acl = Acl::getInstance();
		$this->userSettings = Settings::getInstance()->get('user');
		$this->serverSettings = Settings::getInstance()->get('server');
		$this->flags = Settings::getInstance()->get('flags');


	}

	private function header()
	{

		(new \phpgwapi_common())->phpgw_header(true);

		$this->template = new Template();
		$this->template->set_root(PHPGW_APP_TPL);
	}

	private function store_location($info)
	{
		Cache::session_set('admin', 'currentsessions_session_data', $info);
	}

	public function list_sessions()
	{
		$this->flags['menu_selection'] = 'admin::admin::sessions';
		Settings::getInstance()->set('flags', $this->flags);

		$info = Cache::session_get('admin', 'currentsessions_session_data');

		if (!is_array($info))
		{
			$info = array(
				'start' => 0,
				'sort'  => 'asc',
				'order' => 'session_dla'
			);
			$this->store_location($info);
		}

		$vars = array(
			'start'	=> 'int',
			'sort'	=> 'string',
			'order'	=> 'string'
		);
		foreach ($vars as $var => $type)
		{
			$val = Sanitizer::get_var($var, $type, 'GET');
			if ($val)
			{
				$info[$var] = $val;
			}
		}

		$this->store_location($info);

		$this->flags['app_header'] = lang('Admin') . ' - ' . lang('List of current users');
		Settings::getInstance()->set('flags', $this->flags);


		$can_kill = false;
		$lang_kill = '';
		if (!$this->acl->check('current_sessions_access', Acl::DELETE, 'admin'))
		{
			$can_kill = true;
			$lang_kill = lang('kill');
		}

		$total = $this->bo->total();
		$nextmatchs = createobject('phpgwapi.nextmatchs');

		$header = array(
			'left_next_matchs'	=> $nextmatchs->left('/admin/currentusers.php', $info['start'], $total),
			'right_next_matchs' => $nextmatchs->right('/admin/currentusers.php', $info['start'], $total),
			'sort_loginid'		=> $nextmatchs->show_sort_order($info['sort'], 'lid', $info['order'], '/admin/currentusers.php', lang('LoginID')),
			'sort_ip'			=> $nextmatchs->show_sort_order($info['sort'], 'ip', $info['order'], '/admin/currentusers.php', lang('IP')),
			'sort_login_time'	=> $nextmatchs->show_sort_order($info['sort'], 'logints', $info['order'], '/admin/currentusers.php', lang('Login Time')),
			'sort_action'		=> $nextmatchs->show_sort_order($info['sort'], 'action', $info['order'], '/admin/currentusers.php', lang('Action')),
			'sort_idle'			=> $nextmatchs->show_sort_order($info['sort'], 'dla', $info['order'], '/admin/currentusers.php', lang('idle')),
			'lang_kill'			=> $lang_kill
		);

		$this->header();
		$this->template->set_file('current', 'currentusers.tpl');
		$this->template->set_block('current', 'rows', 'row');
		$this->template->set_block('current', 'list', 'list');

		$this->template->set_var($header);

		$tr_class = '';

		$values = $this->bo->list_sessions($info['start'], $info['order'], $info['sort']);
		foreach ($values as $value)
		{
			$tr_class = $nextmatchs->alternate_row_class($tr_class);
			$value['tr_class'] = $tr_class;
			$value['kill'] = '&nbsp;';

			if ($can_kill && $value['id'] != $this->userSettings['sessionid'])
			{
				$kill_url = phpgw::link('/index.php', array(
					'menuaction'	=> 'admin.uicurrentsessions.kill',
					'ksession'		=> $value['id'],
					'kill'			=> 'true'
				));
				$value['kill'] = "<a href=\"{$kill_url}\">{$lang_kill}</a>";
			}

			$this->template->set_var($value);
			$this->template->parse('row', 'rows', true);
		}

		$this->template->pfp('out', 'list');
	}

	public function kill()
	{
		if ($this->acl->check('current_sessions_access', Acl::DELETE, 'admin'))
		{
			$this->list_sessions();
			return False;
		}

		$this->flags['app_header'] = lang('Admin') . ' - ' . lang('Kill session');
		Settings::getInstance()->set('flags', $this->flags);

		$this->header();
		$this->template->set_file('form', 'kill_session.tpl');

		$this->template->set_var('lang_message', lang('Are you sure you want to kill this session ?'));
		$this->template->set_var('link_no', '<a href="' . phpgw::link('/index.php', array('menuaction' => 'admin.uicurrentsessions.list_sessions')) . '">' . lang('No') . '</a>');
		$this->template->set_var('link_yes', '<a href="' . phpgw::link('/index.php', array('menuaction' => 'admin.bocurrentsessions.kill', 'ksession' => $_GET['ksession'])) . '">' . lang('Yes') . '</a>');

		$this->template->pfp('out', 'form');
	}
}
