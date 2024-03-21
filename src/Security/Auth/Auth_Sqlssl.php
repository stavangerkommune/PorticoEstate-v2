<?php
	/**
	* Authentication based on SQL, with optional SSL authentication
	* @author Andreas 'Count' Kotes <count@flatline.de>
	* @copyright Copyright (C) 200x Andreas 'Count' Kotes <count@flatline.de>
	* @copyright Portions Copyright (C) 2004-2008 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
	* @package phpgwapi
	* @subpackage accounts
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

	namespace App\Security\Auth;

/**
 * Authentication based on SQL table
 *
 * @package phpgwapi
 * @subpackage accounts
 */
class phpgwapi_auth_sql extends Auth_ // wait for it...
{

	/**
	 * Constructor
	 */
	private $db;

	public function __construct($db)
	{
		parent::__construct();
		$this->db = $db;
	}

	/**
	 * Authenticate a user
	 *
	 * @param string $username the login to authenticate
	 * @param string $passwd the password supplied by the user
	 * @return bool did the user sucessfully authenticate
	 */
	public function authenticate($username, $passwd)
	{
		$username = $this->db->db_addslashes($username);

		$sql = 'SELECT account_pwd FROM phpgw_accounts'
			. " WHERE account_lid = '{$username}'"
			. " AND account_status = 'A'";

		$this->db->query($sql, __LINE__, __FILE__);
		if (!$this->db->next_record()) {
			return false;
		}

		$hash = $this->db->f('account_pwd', true);
		return $this->verify_hash($passwd, $hash);
	}

	/**
	 * Set the user's password to a new value
	 *
	 * @param string $old_passwd the user's old password
	 * @param string $new_passwd the user's new password
	 * @param int $account_id the account to change the password for - defaults to current user
	 * @return string the new encrypted hash, or an empty string on failure
	 */
	public function change_password($old_passwd, $new_passwd, $account_id = 0)
	{
		$account_id = (int) $account_id;
		// Don't allow passwords changes for other accounts when using XML-RPC
		if (!$account_id) {
			$account_id = $GLOBALS['phpgw_info']['user']['account_id'];
		}

		if ($GLOBALS['phpgw_info']['flags']['currentapp'] == 'login') {
			if (!$this->authenticate($GLOBALS['phpgw']->accounts->id2lid($account_id), $old_passwd)) {
				return '';
			}
		}

		$hash = $this->create_hash($new_passwd);
		$hash_safe = $this->db->db_addslashes($hash); // just to be safe :)
		$now = time();

		$sql = 'UPDATE phpgw_accounts'
			. " SET account_pwd = '{$hash_safe}', account_lastpwd_change = {$now}"
			. " WHERE account_id = {$account_id}";

		if (!!$this->db->query($sql, __LINE__, __FILE__)) {
			return $hash;
		}
		return '';
	}

	/**
	 * Update when the user last logged in
	 *
	 * @param int $account_id the user's account id
	 * @param string $ip the source IP adddress for the request
	 */
	public function update_lastlogin($account_id, $ip)
	{
		$ip = $this->db->db_addslashes($ip);
		$account_id = (int) $account_id;
		$now = time();

		$sql = 'UPDATE phpgw_accounts'
			. " SET account_lastloginfrom = '{$ip}',"
			. " account_lastlogin = {$now}"
			. " WHERE account_id = {$account_id}";

		$this->db->query($sql, __LINE__, __FILE__);
	}
}
	/**
	* Authentication based on SQL, with optional SSL authentication
	*
	* @package phpgwapi
	* @subpackage accounts
	*/
	class Auth extends phpgwapi_auth_sql
	{

		/**
		* Constructor
		*/
		private $db;

		public function __construct($db)
		{
			parent::__construct($db);
			$this->db = $db;
		}

		/**
		* Authenticate a user
		*
		* @param string $username the login to authenticate
		* @param string $passwd the password supplied by the user
		* @return bool did the user authenticate?
		* @return bool did the user sucessfully authenticate
		*/
		public function authenticate($username, $passwd)
		{
			if ( isset($_SERVER['SSL_CLIENT_S_DN']) )
			{
				$username = $this->db->db_addslashes($username);

				$sql = 'SELECT account_lid FROM phpgw_accounts'
					. " WHERE account_lid = '{$username}'"
						. " AND account_status = 'A'";
				$this->db->query($sql, __LINE__, __FILE__);
				return $this->db->next_record();
			}
			return parent::authenticate($username, $passwd);
		}
	}
