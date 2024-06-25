<?php

/**
 * phpGroupWare - registration
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2003-2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
 * @package registration
 * @subpackage core
 * @version $Id: class.menu.inc.php 4683 2010-01-30 17:16:00Z sigurd $
 */

use App\modules\phpgwapi\controllers\Locations;
use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\services\Translation;

/**
 * Description
 * @package registration
 */
class registration_menu
{

	/**
	 * Get the menus for the registration
	 *
	 * @return array available menus for the current user
	 */
	public function get_menu()
	{
		$location_obj = new Locations();
		$flags = Settings::getInstance()->get('flags');
		$userSettings = Settings::getInstance()->get('user');
		$translation = Translation::getInstance();

		$incoming_app = $flags['currentapp'];
		Settings::getInstance()->update('flags', ['currentapp' => 'registration']);
		$acl = Acl::getInstance();

		$menus = array();
		$menus['toolbar'] = array();


		$menus['navbar'] = array(
			'registration' => array(
				'url'	 => phpgw::link('/index.php', array('menuaction' => 'registration.uipending.index')),
				'text'	 => lang('registration'),
				'image'	 => array('admin', 'navbar'),
				'order'	 => -4,
				'group'	 => 'systools'
			),
		);


		if ($acl->check('run', Acl::READ, 'admin') || $acl->check('admin', Acl::ADD, 'registration'))
		{
			$menus['admin'] = array(
				'index'	 => array(
					'text'	 => lang('Configuration'),
					'url'	 => phpgw::link('/index.php', array(
						'menuaction' => 'admin.uiconfig.index',
						'appname'	 => 'registration'
					))
				),
				'fields' => array(
					'text'	 => $translation->translate('Manage Fields', array(), true),
					'url'	 => phpgw::link('/index.php', array('menuaction' => 'registration.uimanagefields.admin'))
				)
			);
		}


		//			$menus['navigation'] = array();
		$menus['navigation']['pending'] = array(
			'url'	 => phpgw::link('/index.php', array('menuaction' => 'registration.uipending.index')),
			'text'	 => lang('Pending for approval'),
			'image'	 => array('property', 'location'),
		);

		Settings::getInstance()->update('flags', ['currentapp' => $incoming_app]);
		return $menus;
	}
}
