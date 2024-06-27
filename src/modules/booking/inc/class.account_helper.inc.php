<?php

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\controllers\Accounts\Accounts;
use App\modules\phpgwapi\security\Acl;

class booking_account_helper
{

	const ADMIN_GROUP = 'Admins';

	protected static $account_is_admin;
	protected static $current_account_lid;

	/**
	 * Returns the current user's account_id in the phpgw_accounts table
	 */
	public static function current_account_id()
	{
		//return get_account_id();
		$userSettings = Settings::getInstance()->get('user');
		return $userSettings['account_id'];

	}

	/**
	 * Returns the current user's login name
	 */
	public static function current_account_lid()
	{
		$userSettings = Settings::getInstance()->get('user');
		return $userSettings['account_lid'];
	}

	/**
	 * Returns the current user's full name
	 */
	public static function current_account_fullname()
	{
		$userSettings = Settings::getInstance()->get('user');
		return $userSettings['fullname'];
	}

	public static function current_account_memberships()
	{
		$accounts_obj = new Accounts();
		return $accounts_obj->membership();
	}
	/* 		public static function current_account_member_of_admins()
		  {
		  if (!isset(self::$account_is_admin))
		  {
		  self::$account_is_admin = false;

		  $memberships = self::current_account_memberships();
		  while($memberships && list($index,$group_info) = each($memberships))
		  {
		  if ($group_info->lid == self::ADMIN_GROUP)
		  {
		  self::$account_is_admin = true;
		  break;
		  }
		  }
		  }

		  return self::$account_is_admin;
		  } */

	public static function current_account_member_of_admins()
	{

		if (!isset(self::$account_is_admin))
		{
			$acl = Acl::getInstance();
			self::$account_is_admin = false;
			if ($acl->check('run', Acl::READ, 'admin') || $acl->check('admin', Acl::ADD, 'booking'))
			{
				self::$account_is_admin = true;
			}
		}

		return self::$account_is_admin;
	}
}
