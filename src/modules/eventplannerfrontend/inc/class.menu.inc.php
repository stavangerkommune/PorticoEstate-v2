<?php

/**
 * phpGroupWare - eventplannerfrontend.
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2016 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
 * @package eventplannerfrontend
 * @subpackage core
 * @version $Id: class.menu.inc.php 14728 2016-02-11 22:28:46Z sigurdne $
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
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\services\Translation;

phpgw::import_class('phpgwapi.uicommon_jquery');

/**
 * Description
 * @package eventplannerfrontend
 */
class eventplannerfrontend_menu
{

	/**
	 * Get the menus for the eventplannerfrontend
	 *
	 * @return array available menus for the current user
	 */
	public function get_menu()
	{

		$userSettings = Settings::getInstance()->get('user');
		$flags = Settings::getInstance()->get('flags');
		$translation = Translation::getInstance();

		$incoming_app			 = $flags['currentapp'];
		Settings::getInstance()->update('flags', ['currentapp' => 'eventplannerfrontend']);
		$acl					 =	Acl::getInstance();
		$menus = array();


		$translation->add_app('eventplanner');

		$menus['navbar'] = array(
			'eventplannerfrontend' => array(
				'text' => lang('eventplannerfrontend'),
				'url' => phpgwapi_uicommon_jquery::link(array('menuaction' => "eventplannerfrontend.uiapplication.index")),
				'image' => array('eventplannerfrontend', 'navbar'),
				'order' => 35,
				'group' => 'office'
			),
		);

		$menus['toolbar'] = array();
		if (isset($userSettings['apps']['admin']))
		{
			$menus['admin'] = array(
				'index' => array(
					'text' => lang('Configuration'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'admin.uiconfig.index',
						'appname' => 'eventplannerfrontend'
					))
				),
				'metasettings' => array(
					'text' => lang('metasettings'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'eventplannerfrontend.uimetasettings.index',
						'appname' => 'eventplannerfrontend'
					))
				),
				'acl' => array(
					'text' => $translation->translate('Configure Access Permissions', array(), true),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'preferences.uiadmin_acl.list_acl',
						'acl_app' => 'eventplannerfrontend'
					))
				),
			);
		}

		if (isset($userSettings['apps']['preferences']))
		{
			$menus['preferences'] = array(
				array(
					'text' => $translation->translate('Grant Access', array(), true),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'preferences.uiadmin_acl.aclprefs',
						'acl_app' => 'eventplanner'
					))
				)
			);

			$menus['toolbar'][] = array(
				'text' => $translation->translate('Preferences', array(), true),
				'url' => phpgw::link('/preferences/section', array('appname' => 'eventplanner')),
				'image' => array('eventplanner', 'preferences')
			);
		}

		$menus['navigation'] = array(
			'events' => array(
				'text' => lang('events'),
				'url' => phpgwapi_uicommon_jquery::link(array('menuaction' => "eventplannerfrontend.uievents.index")),
				'image' => array('events', 'navbar'),
			),
			'vendor' => array(
				'text' => lang('vendor'),
				'url' =>  phpgwapi_uicommon_jquery::link(array('menuaction' => "eventplannerfrontend.uivendor.index")),
				'image' => array('vendor', 'navbar'),
				'children'	=> array(
					'new_vendor' => array(
						'text' => lang('new vendor'),
						'url' => phpgwapi_uicommon_jquery::link(array('menuaction' => 'eventplannerfrontend.uivendor.add'))
					),
					'new_application' => array(
						'text' => lang('new application'),
						'url' => phpgwapi_uicommon_jquery::link(array('menuaction' => 'eventplannerfrontend.uiapplication.add'))
					),
					'application' => array(
						'text' => lang('my applications'),
						'url' => phpgwapi_uicommon_jquery::link(array('menuaction' => 'eventplannerfrontend.uiapplication.index')),
					)
				),

			),
			'customer' => array(
				'text' => lang('customer'),
				'url' =>  phpgwapi_uicommon_jquery::link(array('menuaction' => "eventplannerfrontend.uicustomer.index")),
				'image' => array('customer', 'navbar'),
			),
			'new_user' => array(
				'text' => lang('new user'),
				'url' => phpgw::link('/registration/', array()),
				'image' => array('user', 'navbar'),
			)
		);

		if ($acl->check('.booking', ACL_READ, 'eventplannerfrontend'))
		{
			$menus['navigation']['customer']['children'] = array(
				'booking' => array(
					'text' => lang('my bookings'),
					'url' => phpgwapi_uicommon_jquery::link(array('menuaction' => "eventplannerfrontend.uibooking.index")),
					'image' => array('customer', 'navbar'),
				)
			);
			$menus['navigation']['customer']['children'] = array(
				'booking' => array(
					'text' => lang('my bookings'),
					'url' => phpgwapi_uicommon_jquery::link(array('menuaction' => "eventplannerfrontend.uibooking.index")),
					'image' => array('customer', 'navbar'),
				)
			);
			$menus['navigation']['customer']['children']['customer_report'] = array(
				'text' => lang('My customer report'),
				'url' => phpgwapi_uicommon_jquery::link(array('menuaction' => "eventplannerfrontend.uicustomer_report.index")),
				'image' => array('customer_report', 'navbar'),
			);
		}

		Settings::getInstance()->update('flags', ['currentapp' => $incoming_app]);
		return $menus;
	}
}
