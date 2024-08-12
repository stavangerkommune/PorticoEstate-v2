<?php

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\services\Translation;
use App\modules\phpgwapi\controllers\Locations;

phpgw::import_class('frontend.bofrontend');

class frontend_menu
{

	function get_menu()
	{
		$userSettings = Settings::getInstance()->get('user');
		$flags = Settings::getInstance()->get('flags');
		$translation = Translation::getInstance();
		$location_obj = new Locations();


		$incoming_app			 = $flags['currentapp'];
		Settings::getInstance()->update('flags', ['currentapp' => 'property']);
		$acl					 =	Acl::getInstance();
		$menus = array();

		if ($acl->check('run', Acl::READ, 'admin') || $acl->check('admin', Acl::ADD, 'frontend'))
		{
			$menus['admin'] = array(
				'index' => array(
					'text' => lang('Configuration'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'admin.uiconfig.index',
						'appname' => 'frontend'
					))
				),
				'acl' => array(
					'text' => lang('Configure Access Permissions'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'preferences.uiadmin_acl.list_acl',
						'acl_app' => 'frontend'
					))
				),
				'documents' => array(
					'text' => lang('upload_userdoc'),
					'url' => phpgw::link('/index.php', array(
						'menuaction' => 'frontend.uidocumentupload.index',
						'appname' => 'frontend'
					))
				)
			);
		}

		$menus['navbar'] = array(
			'frontend' => array(
				'text' => lang('frontend'),
				'url' => phpgw::link('/index.php', array('menuaction' => 'frontend.uifrontend.index')),
				'image' => array('frontend', 'navbar'),
				'order' => 35,
				'group' => 'office'
			),
		);


		$menus['navigation'] = array();


		$locations = frontend_bofrontend::get_sections();

		$tabs = array();
		foreach ($locations as $key => $entry)
		{
			$name = $entry['name'];
			$location = $entry['location'];

			if ($acl->check($location, ACL_READ, 'frontend'))
			{
				$location_id = $location_obj->get_id('frontend', $location);
				$menus['navigation'][$location_id] = array(
					'text' => lang($name),
					'url' => phpgw::link('/', array(
						'menuaction' => "frontend.ui{$name}.index",
						'type' => $location_id,
						'noframework' => $noframework
					))
				);
			}
		}

		Settings::getInstance()->update('flags', ['currentapp' => $incoming_app]);
		return $menus;
	}
}
