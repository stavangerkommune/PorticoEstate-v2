<?php

/**
 * Object Factory
 *
 * @author Dirk Schaller <dschaller@probusiness.de>
 * @author Dave Hall <skwashd@phpgroupware.org>
 * @author mdean
 * @author milosch
 * @author (thanks to jengo and ralf)
 * @copyright Copyright (C) 2003-2008 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License v2 or later
 * @package phpgroupware
 * @subpackage phpgwapi
 * @version $Id$
 */

/*
	   This program is free software: you can redistribute it and/or modify
	   it under the terms of the GNU Lesser General Public License as published by
	   the Free Software Foundation, either version 2 of the License, or
	   (at your option) any later version.

	   This program is distributed in the hope that it will be useful,
	   but WITHOUT ANY WARRANTY; without even the implied warranty of
	   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	   GNU General Public License for more details.

	   You should have received a copy of the GNU Lesser General Public License
	   along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */

/**
 * Object factory
 *
 * @package phpgroupware
 * @subpackage phpgwapi
 */
class phpgwapi_ofphpgwapi extends phpgwapi_object_factory
{
	/**
	 * Instantiate a class
	 *
	 * @param string $class name of class
	 * @param mixed  $p1    paramater for constructor of class (optional)
	 * @param mixed  $p2    paramater for constructor of class (optional)
	 * @param mixed  $p3    paramater for constructor of class (optional)
	 * @param mixed  $p4    paramater for constructor of class (optional)
	 * @param mixed  $p5    paramater for constructor of class (optional)
	 * @param mixed  $p6    paramater for constructor of class (optional)
	 * @param mixed  $p7    paramater for constructor of class (optional)
	 * @param mixed  $p8    paramater for constructor of class (optional)
	 * @param mixed  $p9    paramater for constructor of class (optional)
	 * @param mixed  $p10   paramater for constructor of class (optional)
	 * @param mixed  $p11   paramater for constructor of class (optional)
	 * @param mixed  $p12   paramater for constructor of class (optional)
	 * @param mixed  $p13   paramater for constructor of class (optional)
	 * @param mixed  $p14   paramater for constructor of class (optional)
	 * @param mixed  $p15   paramater for constructor of class (optional)
	 * @param mixed  $p16   paramater for constructor of class (optional)
	 *
	 * @return object the instantiated class
	 */
	public static function createObject(
		$class,
		$p1 = '_UNDEF_',
		$p2 = '_UNDEF_',
		$p3 = '_UNDEF_',
		$p4 = '_UNDEF_',
		$p5 = '_UNDEF_',
		$p6 = '_UNDEF_',
		$p7 = '_UNDEF_',
		$p8 = '_UNDEF_',
		$p9 = '_UNDEF_',
		$p10 = '_UNDEF_',
		$p11 = '_UNDEF_',
		$p12 = '_UNDEF_',
		$p13 = '_UNDEF_',
		$p14 = '_UNDEF_',
		$p15 = '_UNDEF_',
		$p16 = '_UNDEF_'
	)
	{
		list($appname, $classname) = explode('.', $class, 2);
		switch ($classname)
		{
			case 'auth':
				return self::_create_auth_object();

			case 'accounts':
				$account_id   = ($p1 !== '_UNDEF_') ? $p1 : null;
				$account_type = ($p2 !== '_UNDEF_') ? $p2 : null;
				return new \App\modules\phpgwapi\controllers\Accounts\Accounts($account_id, $account_type);

			case 'acl':
				$account_id   = ($p1 !== '_UNDEF_') ? $p1 : null;
				return \App\modules\phpgwapi\security\Acl::getInstance($account_id);

			case 'mapping':
				$auth_info = ($p1 !== '_UNDEF_') ? $p1 : null;
				return new \App\modules\phpgwapi\security\Sso\Mapping($auth_info);

			case 'db':
				$query = ($p1 !== '_UNDEF_') ? $p1 : null;
				$db_type = ($p2 !== '_UNDEF_') ? $p2 : null;
				$delay_connect = ($p3 !== '_UNDEF_') ? $p3 : null;
				return \App\Database\Db::getInstance();
			case 'hooks':
				$db = ($p1 !== '_UNDEF_') ? $p1 : null;
				return new \App\modules\phpgwapi\services\Hooks($db);
			default:
				return parent::createObject(
					$class,
					$p1,
					$p2,
					$p3,
					$p4,
					$p5,
					$p6,
					$p7,
					$p8,
					$p9,
					$p10,
					$p11,
					$p12,
					$p13,
					$p14,
					$p15,
					$p16
				);
		}
	}


	/**
	 * Create a new authentication object
	 *
	 * @return object new authentication object
	 */
	protected static function _create_auth_object()
	{
		include_once PHPGW_API_INC . '/auth/class.auth_.inc.php';

		$auth_type = $GLOBALS['phpgw_info']['server']['auth_type'];
		switch ($auth_type)
		{
			case 'sqlssl':
				include_once PHPGW_API_INC . '/auth/class.auth_sql.inc.php';
				// fall through

			case 'ads':
			case 'exchange':
			case 'http':
			case 'ldap':
			case 'mail':
				// case 'nis': - doesn't currently work AFAIK - skwashd may08
			case 'customsso':
			case 'ntlm':
			case 'remoteuser':
			case 'sql':
			case 'azure':
				$class = "auth_{$auth_type}";
				include_once PHPGW_API_INC . "/auth/class.{$class}.inc.php";

				$class = "phpgwapi_{$class}";
				return new $class();

			default:
				include_once PHPGW_API_INC . '/auth/class.auth_sql.inc.php';
				return new phpgwapi_auth_sql();
		}
	}


}
