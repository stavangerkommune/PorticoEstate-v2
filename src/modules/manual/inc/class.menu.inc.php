<?php

/**
 * Admin - Menus
 *
 * @author Dave Hall <skwashd@phpgroupware.org>
 * @copyright Copyright (C) 2007 - 2008 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package addressbook
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

use App\modules\phpgwapi\controllers\Locations;
use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\services\Translation;

/**
 * Menus
 *
 * @package admin
 */
class manual_menu
{

	/**
	 * Get the menus for admin
	 *
	 * @return array available menus for the current user
	 */
	function get_menu()
	{
		$location_obj = new Locations();
		$flags = Settings::getInstance()->get('flags');
		$userSettings = Settings::getInstance()->get('user');
		$translation = Translation::getInstance();

		$incoming_app = $flags['currentapp'];
		Settings::getInstance()->update('flags', ['currentapp' => 'manual']);

		$acl = Acl::getInstance();

		$menus = array();

		$menus['navbar'] = array(
			'manual' => array(
				'text' => $translation->translate('manual', array(), true),
				'url' => phpgw::link(
					'/index.php',
					array(
						'menuaction' => 'manual.uidocuments.index'
					)
				),
				'image' => array('hrm', 'navbar'),
				'order' => -5,
				'group' => 'systools'
			)
		);

		$menus['admin'] = array();

		if ($acl->check('run', Acl::READ, 'admin'))
		{
			$menus['admin'] = array(
				'index' => array(
					'text' => $translation->translate('Categories', array(), true),
					'url' => phpgw::link(
						'/index.php',
						array(
							'menuaction' => 'admin.uicategories.index',
							'appname' => 'manual',
							'location' => '.documents',
							'global_cats' => 'true',
							'menu_selection' => 'admin::manual::index'
						)
					)
				),
				'acl' => array(
					'text' => lang('Configure Access Permissions'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'preferences.uiadmin_acl.list_acl',
						'acl_app' => 'manual'
					))
				)
			);
		}


		$menus['navigation'] = array(
			'add' => array(
				'text' => lang('add'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'manual.uidocuments.add')),
				'image' => array('property', 'location_1'),
			),
			'view' => array(
				'text' => lang('view'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'manual.uidocuments.view')),
				'image' => array('property', 'location_1'),
			),
		);

		if (isset($userSettings['apps']['preferences']))
		{
			$menus['preferences'] = array();
		}

		$menus['toolbar'] = array();

		Settings::getInstance()->update('flags', ['currentapp' => $incoming_app]);

		return $menus;
	}
}
