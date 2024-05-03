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

use App\modules\phpgwapi\services\Settings;
use App\helpers\Template;

use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\controllers\Locations;
use App\modules\phpgwapi\services\Cache;

class admin_uiapplications
{
	public $public_functions = array(
		'get_list'	=> true,
		'add'		=> true,
		'edit'		=> true,
		'delete'	=> true,
		'register_all_hooks' => true
	);

	private $bo;
	private $nextmatchs;
	private $flags;
	private $phpgwapi_common;
	private $template;

	public function __construct()
	{
		$this->flags = Settings::getInstance()->get('flags');
		$this->flags['menu_selection'] = 'admin::admin';
		Settings::getInstance()->set('flags', $this->flags);
		$this->bo = createObject('admin.boapplications');
		$this->nextmatchs = createObject('phpgwapi.nextmatchs', false);
		$this->phpgwapi_common = new \phpgwapi_common();
		$this->template = new Template();
	}

	public function get_list()
	{
		$this->flags['menu_selection'] .= '::apps';
		Settings::getInstance()->set('flags', $this->flags);

		$this->phpgwapi_common->phpgw_header(true);

		$this->template->set_root(PHPGW_APP_TPL);
		$this->template->set_file(array('applications' => 'applications.tpl'));
		$this->template->set_block('applications', 'list', 'list');
		$this->template->set_block('applications', 'row', 'row');

		$start	= Sanitizer::get_var('start', 'int', 'GET');
		$sort	= Sanitizer::get_var('sort', 'string', 'GET');
		$order	= Sanitizer::get_var('order', 'string', 'GET');
		$userSettings = Settings::getInstance()->get('user');
		$offset	= $userSettings['preferences']['common']['maxmatchs'];

		$apps = $this->bo->get_list();
		$total = count($apps);

		$sort = $sort ? $sort : 'ASC';

		if ($sort == 'ASC')
		{
			ksort($apps);
		}
		else
		{
			krsort($apps);
		}

		if ($start && $offset)
		{
			$limit = $start + $offset;
		}
		else if ($start && !$offset)
		{
			$limit = $start;
		}
		else if (!$start && !$offset)
		{
			$limit = $total;
		}
		else
		{
			$start = 0;
			$limit = $offset;
		}

		if ($limit > $total)
		{
			$limit = $total;
		}

		$i = 0;
		$applications = array();
		foreach ($apps as $app => $data)
		{
			if (
				$i >= $start
				&& $i <= $limit
			)
			{
				$applications[$app] = $data;
			}
			$i++;
		}

		$this->template->set_var('lang_installed', lang('Installed applications'));

		$this->template->set_var('sort_title', $this->nextmatchs->show_sort_order($sort, 'title', 'title', '/index.php', lang('Title'), '&menuaction=admin.uiapplications.get_list'));
		$this->template->set_var('lang_showing', $this->nextmatchs->show_hits($total, $start));
		$this->template->set_var('left', $this->nextmatchs->left('/index.php', $start, $total, 'menuaction=admin.uiapplications.get_list'));
		$this->template->set_var('right', $this->nextmatchs->right('index.php', $start, $total, 'menuaction=admin.uiapplications.get_list'));

		$this->template->set_var('lang_version', lang('version'));
		$this->template->set_var('lang_edit', lang('Edit'));
		$this->template->set_var('lang_delete', lang('Delete'));
		$this->template->set_var('lang_enabled', lang('Enabled'));

		$this->template->set_var('new_action', phpgw::link('/index.php', array('menuaction' => 'admin.uiapplications.add')));
		$this->template->set_var('lang_note', lang('(To install new applications use<br><a href="setup/" target="setup">Setup</a> [Manage Applications] !!!)'));
		$this->template->set_var('lang_add', lang('add'));

		$tr_color = '';
		foreach ($applications as $key => $app)
		{
			$tr_color = $this->nextmatchs->alternate_row_class($tr_color);

			if ($app['title'])
			{
				$name = $app['title'];
			}
			elseif ($app['name'])
			{
				$name = $app['name'];
			}
			else
			{
				$name = '&nbsp;';
			}

			$this->template->set_var('tr_color', $tr_color);
			$this->template->set_var('name', $name);
			$this->template->set_var('version', $app['version']);

			$this->template->set_var('edit', '<a href="' . phpgw::link('/index.php', array('menuaction' => 'admin.uiapplications.edit', 'app_name' => urlencode($app['name']))) . '"> ' . lang('Edit') . ' </a>');
			$this->template->set_var('delete', '<a href="' . phpgw::link('/index.php', array('menuaction' => 'admin.uiapplications.delete', 'app_name' => urlencode($app['name']))) . '"> ' . lang('Delete') . ' </a>');

			if ($app['status'] == 1)
			{
				$status = lang('yes');
			}
			else if ($app['status'] == 2)
			{
				$status = lang('hidden');
			}
			else
			{
				$status = '<b>' . lang('no') . '</b>';
			}
			$this->template->set_var('status', $status);

			$this->template->parse('rows', 'row', True);
		}

		$this->template->pparse('out', 'list');
	}

	private function display_row($label, $value)
	{
		$this->template->set_var('tr_color', $this->nextmatchs->alternate_row_class());
		$this->template->set_var('label', $label);
		$this->template->set_var('value', $value);
		$this->template->parse('rows', 'row', True);
	}

	public function add()
	{
		$this->flags['menu_selection'] .= '::apps';
		Settings::getInstance()->set('flags', $this->flags);


		$this->template->set_root(PHPGW_APP_TPL);
		$this->template->set_file(array('application' => 'application_form.tpl'));
		$this->template->set_block('application', 'form', 'form');
		$this->template->set_block('application', 'row', 'row');

		if (Sanitizer::get_var('submit', 'bool', 'POST'))
		{
			$totalerrors = 0;

			$app_order    = Sanitizer::get_var('app_order', 'int', 'POST');
			$n_app_name   = Sanitizer::get_var('n_app_name', 'string', 'POST');
			$n_app_title  = Sanitizer::get_var('n_app_title', 'string', 'POST');
			$n_app_status = Sanitizer::get_var('n_app_status', 'int', 'POST');

			if ($this->bo->exists($n_app_name))
			{
				$error[$totalerrors++] = lang('That application name already exists.');
			}
			if (preg_match("/\D/", $app_order))
			{
				$error[$totalerrors++] = lang('That application order must be a number.');
			}
			if (!$n_app_name)
			{
				$error[$totalerrors++] = lang('You must enter an application name.');
			}

			if (!$totalerrors)
			{
				$this->bo->add(array(
					'n_app_name'   => $n_app_name,
					'n_app_status' => $n_app_status,
					'app_order'    => $app_order
				));

				$this->flags['nodisplay'] = True;
				Settings::getInstance()->set('flags', $this->flags);

				phpgw::redirect_link('/index.php', array('menuaction' => 'admin.uiapplications.get_list'));
				exit;
			}
			else
			{
				$this->template->set_var('error', '<p><center>' . $this->phpgwapi_common->error_list($error) . '</center><br>');
			}
		}
		else
		{	// else submit
			$this->template->set_var('error', '');
		}

		$this->phpgwapi_common->phpgw_header();
		echo parse_navbar();

		$this->template->set_var('lang_header', lang('Add new application'));

		$this->template->set_var('hidden_vars', '');
		$this->template->set_var('form_action', phpgw::link('/index.php', array('menuaction' => 'admin.uiapplications.add')));

		$this->display_row(lang('application name'), '<input name="n_app_name" value="' . $n_app_name . '">');

		if (!isset($n_app_status))
		{
			$n_app_status = 1;
		}

		$selected[$n_app_status] = ' selected';
		$status_html = '<option value="0"' . $selected[0] . '>' . lang('Disabled') . '</option>'
			. '<option value="1"' . $selected[1] . '>' . lang('Enabled') . '</option>'
			. '<option value="2"' . $selected[2] . '>' . lang('Enabled - Hidden from navbar') . '</option>';
		$this->display_row(lang('Status'), '<select name="n_app_status">' . $status_html . '</select>');

		if (!$app_order)
		{
			$app_order = $this->bo->app_order();
		}

		$this->display_row(lang('Select which location this app should appear on the navbar, lowest (left) to highest (right)'), '<input name="app_order" value="' . $app_order . '">');

		$this->template->set_var('lang_submit_button', lang('add'));
		$this->template->pparse('out', 'form');
	}

	public function edit()
	{
		$this->flags['menu_selection'] .= '::apps';
		Settings::getInstance()->set('flags', $this->flags);


		$app_name = Sanitizer::get_var('app_name', 'string', 'GET');

		$this->template->set_root(PHPGW_APP_TPL);
		$this->template->set_file(array('application' => 'application_form.tpl'));
		$this->template->set_block('application', 'form', 'form');
		$this->template->set_block('application', 'row', 'row');

		if (Sanitizer::get_var('submit', 'bool', 'POST'))
		{
			$totalerrors = 0;

			$old_app_name = Sanitizer::get_var('old_app_name', 'string', 'POST');
			$app_order    = Sanitizer::get_var('app_order', 'int', 'POST');
			$n_app_name   = Sanitizer::get_var('n_app_name', 'string', 'POST');
			$n_app_title  = Sanitizer::get_var('n_app_title', 'string', 'POST');
			$n_app_status = Sanitizer::get_var('n_app_status', 'int', 'POST');

			if (!$n_app_name)
			{
				$error[$totalerrors++] = lang('You must enter an application name.');
			}

			if ($old_app_name != $n_app_name)
			{
				if ($this->bo->exists($n_app_name))
				{
					$error[$totalerrors++] = lang('That application name already exists.');
				}
			}

			if (!$totalerrors)
			{
				$this->bo->save(array(
					'n_app_name'   => $n_app_name,
					'n_app_status' => $n_app_status,
					'app_order'    => $app_order,
					'old_app_name' => $old_app_name
				));

				$this->flags['nodisplay'] = True;
				Settings::getInstance()->set('flags', $this->flags);

				phpgw::redirect_link('/index.php', array('menuaction' => 'admin.uiapplications.get_list'));
				exit;
			}
		}

		$this->phpgwapi_common->phpgw_header();
		echo parse_navbar();

		if (isset($totalerrors) && $totalerrors)
		{
			$this->template->set_var('error', '<p><center>' . $this->phpgwapi_common->error_list($error) . '</center><br>');
		}
		else
		{
			$this->template->set_var('error', '');
			list($n_app_name, $n_app_title, $n_app_status, $old_app_name, $app_order) = $this->bo->read($app_name);
		}

		$this->template->set_var('lang_header', lang('Edit application'));
		$this->template->set_var('hidden_vars', '<input type="hidden" name="old_app_name" value="' . $old_app_name . '">');
		$this->template->set_var('form_action', phpgw::link('/index.php', array('menuaction' => 'admin.uiapplications.edit')));

		$this->display_row(lang('application name'), '<input name="n_app_name" value="' . $n_app_name . '">');

		$this->template->set_var('lang_status', lang('Status'));
		$this->template->set_var('lang_submit_button', lang('edit'));

		$selected[$n_app_status] = ' selected';
		$status_html = '<option value="0"' . (isset($selected[0]) ? $selected[0] : '') . '>' . lang('Disabled') . '</option>'
			. '<option value="1"' . (isset($selected[1]) ? $selected[1] : '') . '>' . lang('Enabled') . '</option>'
			. '<option value="2"' . (isset($selected[2]) ? $selected[2] : '') . '>' . lang('Enabled - Hidden from navbar') . '</option>';

		$this->display_row(lang("Status"), '<select name="n_app_status">' . $status_html . '</select>');
		$this->display_row(lang("Select which location this app should appear on the navbar, lowest (left) to highest (right)"), '<input name="app_order" value="' . $app_order . '">');

		$this->template->set_var('select_status', $status_html);
		$this->template->pparse('out', 'form');
	}

	public function delete()
	{
		$this->flags['menu_selection'] .= '::apps';
		Settings::getInstance()->set('flags', $this->flags);


		$app_name = Sanitizer::get_var('app_name', 'string', 'GET');

		if (!$app_name)
		{
			phpgw::redirect_link('/index.php', array('menuaction' => 'admin.uiapplications.get_list'));
		}

		$this->template->set_root(PHPGW_APP_TPL);
		$this->template->set_file(array('body' => 'delete_common.tpl'));

		if (Sanitizer::get_var('confirm', 'bool'))
		{
			$this->bo->delete($app_name);
			phpgw::redirect_link('/index.php', array('menuaction' => 'admin.uiapplications.get_list'));
			$this->flags['nodisplay'] = True;
			Settings::getInstance()->set('flags', $this->flags);

			exit;
		}

		$this->phpgwapi_common->phpgw_header(true);

		$this->template->set_var('messages', lang('Are you sure you want to delete this application ?'));
		$this->template->set_var('no', '<a href="' . phpgw::link('/index.php', array('menuaction' => 'admin.uiapplications.get_list')) . '">' . lang('No') . '</a>');
		$this->template->set_var('yes', '<a href="' . phpgw::link('/index.php', array('menuaction' => 'admin.uiapplications.delete', 'app_name' => urlencode($app_name), 'confirm' => 'True')) . '">' . lang('Yes') . '</a>');
		$this->template->pparse('out', 'body');
	}

	function register_all_hooks()
	{
		$this->flags['menu_selection'] .= '::hooks';
		Settings::getInstance()->set('flags', $this->flags);

		(new \App\modules\phpgwapi\services\Hooks())->register_all_hooks();

		$this->phpgwapi_common->phpgw_header(true);
		$updated = lang('hooks updated');
		$detail = lang('the new hooks should be available to all users');
		echo <<<HTML
				<h1>$updated</h1>
				<p>$detail</p>

HTML;
	}
}
