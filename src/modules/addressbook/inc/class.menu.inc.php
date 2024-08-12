<?php

/**
 * Addressbook - Menus
 *
 * @author Dave Hall <skwashd@phpgroupware.org>
 * @copyright Copyright (C) 2007 Free Software Foundation, Inc. http://www.fsf.org/
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

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\services\Translation;

/**
 * Menus
 *
 * @package addressbook
 */
class addressbook_menu
{
	/**
	 * Get the menus for the addressbook
	 *
	 * @return array available menus for the current user
	 */
	function get_menu()
	{
		$userSettings = Settings::getInstance()->get('user');
		$flags = Settings::getInstance()->get('flags');
		$translation = Translation::getInstance();

		$incoming_app			 = $flags['currentapp'];
		Settings::getInstance()->update('flags', ['currentapp' => 'addressbook']);
		$acl					 =	Acl::getInstance();
		$menus					 = array();

		$menus['navbar'] = array(
			'addressbook'	=> array(
				'text'	=> $translation->translate('Contacts', array(), true),
				'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook_persons.index')),
				'image'	=> array('addressbook', 'navbar'),
				'order'	=> 2,
				'group'	=> 'office'
			)
		);
		if (
			$acl->check('run', Acl::READ, 'admin')
			|| $acl->check('admin', Acl::ADD, 'addressbook')
		)
		{
			$menus['admin'] = array(
				'index' => array(
					'text'	=> $translation->translate('Site Configuration', array(), true),
					'url'	=> phpgw::link('/index.php', array('menuaction' => 'admin.uiconfig.index', 'appname' => 'addressbook'))
				),

				'custom_fields' => array(
					'text'	=> $translation->translate('Edit custom fields', array(), true),
					'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uifields.index'))
				),

				'categories' => array(
					'text'	=> $translation->translate('Global Categories', array(), true),
					'url'	=> phpgw::link('/index.php', array('menuaction' => 'admin.uicategories.index', 'appname' => 'addressbook'))
				),

				'contact_comm_type' => array(
					'text'	=> $translation->translate('Communication Types Manager', array(), true),
					'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uicatalog_contact_comm_type.view'))
				),

				'contact_comm_descr' => array(
					'text'	=> $translation->translate('Communication Descriptions Manager', array(), true),
					'url'	=>  phpgw::link('/index.php', array('menuaction' => 'addressbook.uicatalog_contact_comm_descr.view'))
				),

				'contact_addr_type' => array(
					'text'	=> $translation->translate('Location Manager', array(), true),
					'url'	=>  phpgw::link('/index.php', array('menuaction' => 'addressbook.uicatalog_contact_addr_type.view'))
				),

				'contact_note_type' => array(
					'text'	=> $translation->translate('Notes Types Manager', array(), true),
					'url'	=>  phpgw::link('/index.php', array('menuaction' => 'addressbook.uicatalog_contact_note_type.view'))
				),

				'custom_attribute' => array(
					'text'	=> lang('Custom fields on org-person'),
					'url'	=> phpgw::link('/index.php', array(
						'menuaction' => 'admin.ui_custom.list_attribute',
						'appname' => 'addressbook',
						'location' => 'org_person',
						'menu_selection' => 'admin::addressbook::custom_attribute'
					))
				)
			);
		}

		$menus['toolbar'] = array(
			array(
				'text'	=> $translation->translate('New Person', array(), true),
				'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook.add_person'))
			),

			array(
				'text'	=> $translation->translate('New Organisation', array(), true),
				'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook.add_org'))
			)
		);

		$menus['navigation'] = array(
			'persons' => array(
				'text'	=> $translation->translate('Person', array(), true),
				'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook_persons.index'))
			),
			'organizations' => array(
				'text'	=> $translation->translate('Organisation', array(), true),
				'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook_organizations.index'))
			),
			'vcard' => array(
				'text'	=> $translation->translate('Import VCard', array(), true),
				'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uivcard.in'))
			),
			'categorize_contacts' => array(
				'text'	=> $translation->translate('Categorise Persons', array(), true),
				'url'	=>  phpgw::link('/index.php', array('menuaction' => 'addressbook.uicategorize_contacts.index'))
			),
			'xport_import' => array(
				'text'	=> $translation->translate('Bulk Import - Contacts', array(), true),
				'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiXport.import'))
			),
/*			'import' => array(
				'text'	=> $translation->translate('Bulk Import - CSV', array(), true),
				'url'	=> phpgw::link('/addressbook/csv_import.php')
			),*/
			'xport_export' => array(
				'text'	=> $translation->translate('Export Contacts', array(), true),
				'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiXport.export'))
			)
		);

		if (isset($userSettings['apps']['preferences']))
		{
			$menus['preferences'] = array(
				array(
					'text'	=> $translation->translate('Preferences', array(), true),
					'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook_prefs.index'))
				),

				array(
					'text'	=> $translation->translate('Grant Access', array(), true),
					'url'	=> phpgw::link('/index.php', array('menuaction' => 'preferences.uiadmin_acl.aclprefs', 'acl_app' => 'addressbook'))
				),

				array(
					'text'	=> $translation->translate('Edit Categories', array(), true),
					'url'	=> phpgw::link('/index.php', array('menuaction' => 'preferences.uicategories.index', 'cats_app' => 'addressbook', 'cats_level' => true, 'global_cats' => true))
				)
			);

			$menus['toolbar'][] = array(
				'text'	=> $translation->translate('Preferences', array(), true),
				'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook_prefs.index'))
			);
		}

		$menus['folders'] = phpgwapi_menu::get_categories('addressbook');
		Settings::getInstance()->update('flags', ['currentapp' => $incoming_app]);

		return $menus;
	}
}
