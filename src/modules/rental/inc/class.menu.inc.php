<?php

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\services\Translation;

phpgw::import_class('rental.uicommon');

class rental_menu
{

	function get_menu()
	{
		$userSettings = Settings::getInstance()->get('user');
		$flags = Settings::getInstance()->get('flags');
		$translation = Translation::getInstance();

		$incoming_app			 = $flags['currentapp'];
		Settings::getInstance()->update('flags', ['currentapp' => 'rental']);
		$acl					 =	Acl::getInstance();

		$config = CreateObject('phpgwapi.config', 'rental');
		$config->read();
		$use_fellesdata = $config->config_data['use_fellesdata'];

		$menus = array();

		$menus['navbar'] = array(
			'rental' => array(
				'text' => lang('rental'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'rental.uifrontpage.index')),
				'image' => array('rental', 'user-home'),
				'order' => 10,
				'group' => 'office'
			)
		);


		if (
			$acl->check(rental_uicommon::LOCATION_IN, ACL_ADD, 'rental') ||
			$acl->check(rental_uicommon::LOCATION_OUT, ACL_ADD, 'rental') ||
			$acl->check(rental_uicommon::LOCATION_INTERNAL, ACL_ADD, 'rental')
		)
		{
			$billing = array(
				'invoice' => array(
					'text' => lang('invoice_menu'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'rental.uibilling.index',
						'appname' => 'rental'
					)),
					'image' => array('rental', 'x-office-document')
				),
				'price_item_list' => array(
					'text' => lang('price_list'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'rental.uiprice_item.index',
						'appname' => 'rental'
					)),
					'image' => array('rental', 'x-office-spreadsheet'),
					'children' => array(
						'manual_adjustment' => array(
							'text' => lang('manual_adjustment'),
							'url' => phpgw::link('/index.php', array(
								'menuaction' => 'rental.uiprice_item.manual_adjustment',
								'appname' => 'rental'
							)),
							'image' => array('rental', 'x-office-spreadsheet')
						)
					)
				),
				'adjustment' => array(
					'text' => lang('adjustment'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'rental.uiadjustment.index',
						'appname' => 'rental'
					)),
					'image' => array('rental', 'x-office-spreadsheet')
				)
			);

			$sync_choices = array(
				'sync_org_unit' => array(
					'text' => lang('sync_org_unit'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'rental.uiparty.sync',
						'sync' => 'org_unit',
						'appname' => 'rental'
					)),
					'image' => array('rental', 'x-office-document')
				),
				'sync_resp_and_service' => array(
					'text' => lang('sync_resp_and_service'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'rental.uiparty.sync',
						'sync' => 'resp_and_service',
						'appname' => 'rental'
					)),
					'image' => array('rental', 'x-office-document')
				),
				'sync_res_units' => array(
					'text' => lang('sync_res_units'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'rental.uiparty.sync',
						'sync' => 'res_unit_number',
						'appname' => 'rental'
					)),
					'image' => array('rental', 'x-office-document')
				),
				'sync_identifier' => array(
					'text' => lang('sync_identifier'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'rental.uiparty.sync',
						'sync' => 'identifier',
						'appname' => 'rental'
					)),
					'image' => array('rental', 'x-office-document')
				)
			);

			$sub_parties = array(
				'sync' => array(
					'text' => lang('sync_menu'),
					'url' => '#',
					'image' => array('rental', 'x-office-document'),
					'children' => $sync_choices
				),
				'resultunit' => array(
					'text' => lang('delegates'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'rental.uiresultunit.index',
						'appname' => 'rental'
					)),
					'image' => array('rental', 'system-users')
				)
			);
		}

		$menus['navigation'] = array(
			'contracts' => array(
				'text' => lang('contracts'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'rental.uicontract.index')),
				'image' => array('rental', 'text-x-generic'),
				'children' => $billing
			),
			'composites' => array(
				'text' => lang('rc'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'rental.uicomposite.index')),
				'image' => array('rental', 'go-home')
			),
			'parties' => array(
				'text' => lang('parties'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'rental.uiparty.index')),
				'image' => array('rental', 'x-office-address-book')
			)
		);

		//temporary check
		if (!$use_fellesdata)
		{
			$menus['navigation'] = array_reverse($menus['navigation'], true);
			$menus['navigation']['email_out'] = array(
				'text' => lang('email out'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'rental.uiemail_out.index')),
				'image' => array('rental', 'text-x-generic'),
				'children' => array(
					'email_template' => array(
						'text' => lang('email template'),
						'url' => phpgw::link('/index.php', array(
							'menuaction' => 'rental.uigeneric.index',
							'type' => 'email_template',
							'admin' => true
						))
					)
				)
			);

			$menus['navigation']['moveout'] = array(
				'text' => lang('moveout'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'rental.uimoveout.index')),
				'image' => array('rental', 'text-x-generic'),
			);
			$menus['navigation']['movein'] = array(
				'text' => lang('movein'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'rental.uimovein.index')),
				'image' => array('rental', 'text-x-generic'),
			);
			$menus['navigation']['schedule'] = array(
				'text' => lang('schedule'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'rental.uicomposite.schedule')),
				'image' => array('rental', 'text-x-generic'),
			);
			$menus['navigation']['application'] = array(
				'text' => lang('application'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'rental.uiapplication.index')),
				'image' => array('rental', 'text-x-generic'),
			);

			$menus['navigation'] = array_reverse($menus['navigation'], true);
		}

		if ($use_fellesdata)
		{
			$menus['navigation']['parties']['children'] = $sub_parties;
		}

		$menus['admin'] = array(
			'index' => array(
				'text' => lang('Configuration'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'admin.uiconfig.index',
					'appname' => 'rental'
				))
			),
			'acl' => array(
				'text' => lang('Configure Access Permissions'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'preferences.uiadmin_acl.list_acl',
					'acl_app' => 'rental'
				))
			),
			'composite_type' => array(
				'text' => lang('composite type'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'rental.uigeneric.index',
					'type' => 'composite_type',
					'admin' => true
				))
			),
			'composite_standard' => array(
				'text' => lang('composite standard'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'rental.uigeneric.index',
					'type' => 'composite_standard',
					'admin' => true
				))
			),
			'location_factor' => array(
				'text' => lang('location factor'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'rental.uigeneric.index',
					'type' => 'location_factor',
					'admin' => true
				))
			),
			'responsibility_unit' => array(
				'text' => lang('responsibility'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'rental.uigeneric.index',
					'type' => 'responsibility_unit',
					'admin' => true
				))
			),
			'import' => array(
				'text' => lang('facilit_import'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'rental.uiimport.index',
					'appname' => 'rental'
				)),
				'image' => array('rental', 'document-save')
			),
			'import_adjustments' => array(
				'text' => lang('import_adjustments'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'rental.uiimport.import_regulations',
					'appname' => 'rental'
				)),
				'image' => array('rental', 'document-save')
			),
			'custom_functions' => array(
				'text' => lang('custom functions'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'admin.ui_custom.list_custom_function',
					'appname' => 'rental',
					'location' => '.contract',
					'menu_selection' => 'admin::rental::custom_functions'
				))
			),
			'custom_field_groups' => array(
				'text' => lang('custom field groups'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'admin.ui_custom.list_attribute_group',
					'appname' => 'rental',
					'menu_selection' => 'admin::rental::custom_field_groups'
				))
			),
			'custom_fields' => array(
				'text' => lang('custom fields'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'admin.ui_custom.list_attribute',
					'appname' => 'rental',
					'menu_selection' => 'admin::rental::custom_fields'
				))
			),
		);

		$menus['folders'] = phpgwapi_menu::get_categories('bergen');

		$menus['preferences'] = array(
			array(
				'text' => lang('Preferences'),
				'url'	 => phpgw::link(
					'/preferences/section',
					array(
						'appname'	 => 'rental',
						'type'		 => 'user'
				))
			),
			array(
				'text' => lang('Grant Access'),
				'url' => phpgw::link('/index.php', array(
					'menuaction' => 'preferences.uiadmin_acl.list_acl',
					'acl_app' => 'rental'
				))
			)
		);
		Settings::getInstance()->update('flags', ['currentapp' => $incoming_app]);
		return $menus;
	}
}
