<?php

/**
 * phpsysinfo - Menus
 *
 * @author Dave Hall <skwashd@phpgroupware.org>
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2007,2008 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package property
 * @version $Id$
 */

/*
	   This program is free software: you can redistribute it and/or modify
	   it under the terms of the GNU General Public License as published by
	   the Free Software Foundation, either version 2 of the License, or
	   (at your option) any later version.

	   This program is distributed in the hope that it will be useful,
	   but WITHOUT ANY WARRANTY; without even the implied warranty of
	   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	   GNU General Public License for more details.

	   You should have received a copy of the GNU General Public License
	   along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\services\Translation;

/**
 * Menus
 *
 * @package phpsysinfo
 */
class phpsysinfo_menu
{
	/**
	 * Get the menus for the phpsysinfo
	 *
	 * @return array available menus for the current user
	 */
	public function get_menu()
	{
		$userSettings = Settings::getInstance()->get('user');
		$flags = Settings::getInstance()->get('flags');
		$translation = Translation::getInstance();

		$incoming_app			 = $flags['currentapp'];
		Settings::getInstance()->update('flags', ['currentapp' => 'admin']);

		$menus = array();

		$menus['toolbar'] = array();

		if (isset($userSettings['apps']['admin']))
		{

			$menus['admin'] = array(
				'index' => array(
					'text'	=> $translation->translate('phpSysInfo', array(), true),
					'url'	=> phpgw::link('/phpsysinfo', array())
				),
			);
		}

		Settings::getInstance()->update('flags', ['currentapp' => $incoming_app]);
		return $menus;
	}
}
