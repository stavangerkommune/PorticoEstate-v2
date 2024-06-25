<?php
/*	 * *****************************************************************\
	 * phpGroupWare API - help system manager                            *
	 * Written by Bettina Gille [ceb@phpgroupware.org]                   *
	 * Manager for the phpGroupWare help system                          *
	 * Copyright (C) 2002, 2003 Bettina Gille                            *
	 * ----------------------------------------------------------------- *
	 * This library is part of the phpGroupWare API                      *
	 * http://www.phpgroupware.org                                       *
	 * ----------------------------------------------------------------- *
	 * This library is free software; you can redistribute it and/or     *
	 * modify it under the terms of the GNU General Public License as    *
	 * published by the Free Software Foundation; either version 2 of    *
	 * the License, or (at your option) any later version.               *
	 *                                                                   *
	 * This program is distributed in the hope that it will be useful,   *
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of    *
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU  *
	 * General Public License for more details.                          *
	 *                                                                   *
	 * You should have received a copy of the GNU General Public License *
	 * along with this program; if not, write to the Free Software       *
	 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.         *
	  \****************************************************************** */
/* $Id$ */

use App\modules\phpgwapi\services\Settings;

class help
{

	var $lang;
	var $app_name;
	var $app_version;
	var $app_id;
	var $up;
	var $down;
	var $intro;
	var $app_intro;
	var $note;
	var $extrabox;
	var $xhelp;
	var $listbox;
	var $output;
	var $data;
	var $title;
	var $section;
	var $currentapp;
	var $appsection = -1;
	var $phpgwapi_common;

	/* This is the constructor for the object. */

	function __construct($reset = False)
	{
		$this->phpgwapi_common = new \phpgwapi_common();
		$userSettings = Settings::getInstance()->get('user');
		$this->lang = $userSettings['preferences']['common']['lang'];
		$this->title = '';
		$this->app_name = '';
		$this->app_version = '';
		$this->app_id = 0;

		$this->up = '';
		$this->down = '';
		$this->intro = '';
		$this->app_intro = '';
		$this->note = '';

		$this->extrabox = '';
		$this->xhelp = '';
		$this->listbox = '';
		$this->data = array();

		if (!$reset)
		{
			$this->output = array();
		}
		phpgwapi_xslttemplates::getInstance()->add_file($this->phpgwapi_common->get_tpl_dir('manual', 'base') . '/help');
	}
	/*
		  Use these functions to get and set the values of this
		  object's variables. This is good OO practice, as it means
		  that datatype checking can be completed and errors raised accordingly.
		 */

	function setvar($var, $value = '')
	{
		if ($value == '')
		{
			global $$var;
			$value = $$var;
		}
		$this->$var = $value;
		// echo $var." = ".$this->$var."<br>\n";
	}

	function getvar($var = '')
	{
		if ($var == '' || !isset($this->$var))
		{
			echo 'Programming Error: ' . $this->getvar('classname') . '->getvar(' . $var . ')!<br>' . "\n";
			Settings::getInstance()->update('flags', ['nodisplay' => True]);
			exit;
		}
		//echo "Var = ".$var."<br>\n";
		//echo $var." = ".$this->$var."<br>\n";
		return $this->$var;
	}

	function start_template()
	{
		if ($this->app_name)
		{
			phpgwapi_xslttemplates::getInstance()->add_file($this->phpgwapi_common->get_tpl_dir('manual', 'base') . '/help_data');
			//		phpgwapi_xslttemplates::getInstance()->add_file($this->phpgwapi_common->get_tpl_dir($this->app_name,'base') . '/help_data');
		}
	}

	function set_controls($type = 'app', $control = '', $control_url = '')
	{
		switch ($type)
		{
			case 'app':
				if ($control != '' && $control_url != '')
				{
					$this->setvar($control, $this->check_help_file($control_url));
				}
				break;
			default:
				$this->setvar('intro', phpgw::link('/help.php'));
				$this->setvar('app_intro', phpgw::link('/help.php', array('app' => $this->app_name)));
				$this->setvar('note', phpgw::link('/help.php', array('note' => 'True')));
				break;
		}
	}

	function set_internal($extra_data = '')
	{
		if ($extra_data != '')
		{
			$this->extrabox = $extra_data;
		}
	}

	function set_xinternal($extra_data = '')
	{
		if ($extra_data != '')
		{
			$this->xhelp = $extra_data;
		}
	}

	function draw_box()
	{
		$control_array = array(
			'intro' => True
		);

		if ($this->app_intro)
		{
			$control_array['app_intro'] = True;
		}
		if ($this->up)
		{
			$control_array['up'] = True;
		}
		if ($this->down)
		{
			$control_array['down'] = True;
		}
		$control_array['note'] = True;

		//_debug_array($control_array);

		//@reset($control_array);
		//while (list($param, $value) = each($control_array))
		foreach ($control_array as $param => $value)
		{
			if (isset($this->$param) && $this->$param)
			{
				$image_width = 15;

				$control_link[] = array(
					'param_url' => $this->$param,
					'link_img' => $this->phpgwapi_common->image('phpgwapi', $param . '_help'),
					'img_width' => $image_width,
					'lang_param_title' => lang($param)
				);
			}
		}

		if ($this->app_name == 'manual')
		{
			$logo_img = $this->phpgwapi_common->image('phpgwapi', 'logo', '', True);
		}
		else
		{
			$logo_img = $this->phpgwapi_common->image($this->app_name, 'navbar', '', True);
		}

		$this->output['help_values'][] = array(
			'img' => $logo_img,
			'title' => $this->title,
			'lang_version' => lang('version'),
			'version' => $this->app_version,
			'control_link' => (isset($control_link) ? $control_link : ''),
			'listbox' => $this->listbox,
			'extrabox' => $this->extrabox,
			'xhelp' => $this->xhelp
		);
	}

	function check_file($file)
	{
		$check_file = PHPGW_SERVER_ROOT . $file;

		if (@is_file($check_file))
		{
			return $file;
		}
		else
		{
			return '';
		}
	}

	function check_help_file($file)
	{
		$lang = strtoupper($this->lang);

		$help_file = $this->check_file('/' . $this->app_name . '/help/' . $lang . '/' . $file);

		if ($help_file == '')
		{
			$help_file = $this->check_file('/' . $this->app_name . '/help/EN/' . $file);
		}

		if ($this->section == basename($help_file, ".odt") && $this->app_name == $this->currentapp)
		{
			$this->appsection = count($this->data);
		}
		//	if ($help_file)
		{
			return phpgw::link('/index.php', array(
				'menuaction' => 'manual.uimanual.help',
				'app' => $this->app_name, 'section' => basename($help_file, ".odt"), 'navbar' => true
			));
		}

		return False;
	}

}
