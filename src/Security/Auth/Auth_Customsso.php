<?php
	/**
	* Authentication based on SQL table
	* @author Dan Kuykendall <seek3r@phpgroupware.org>
	* @author Joseph Engo <jengo@phpgroupware.org>
	* @author Sigurd Nes <sigurdne@online.no>
	* @copyright Copyright (C) 2013 Free Software Foundation, Inc. http://www.fsf.org/
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
	use PDO;

	/**
	* Authentication based on SQL table
	*
	* @package phpgwapi
	* @subpackage accounts
	*/
	class Auth extends Auth_
	{

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
			$sql = 'SELECT account_pwd FROM phpgw_accounts WHERE account_lid = :username AND account_status = :status';
			$stmt = $this->db->prepare($sql);

			$stmt->execute([
				':username' => $username,
				':status' => 'A'
			]);

			return !!$stmt->fetch();
		}
		/* php ping function
		*/
		private function ping($host)
		{
	        exec(sprintf('ping -c 1 -W 5 %s', escapeshellarg($host)), $res, $rval);
	        return $rval === 0;
		}


		public function get_username()
		{
			$headers = getallheaders();
			$ssn = $headers['uid'];

			$remote_user = $headers['REMOTE_USER'] ? $headers['REMOTE_USER'] : $headers['upn'];
			$username_arr  = explode('@', $remote_user);
			$username = $username_arr[0];

			/**
			 * Shibboleth from inside firewall
			 */
			if($username && !$ssn)
			{
				return $username;
			}

			/**
			 * Shibboleth from outside firewall
			 */
			if(!$ssn)
			{
				return;
			}

			$hash_safe = "{SHA}" . base64_encode(self::hex2bin(sha1($ssn)));

			$sql = "SELECT account_lid FROM phpgw_accounts"
				. " JOIN phpgw_accounts_data ON phpgw_accounts.account_id = phpgw_accounts_data.account_id"
				. " WHERE account_data->>'ssn_hash' = :hash_safe";
			$stmt = $this->db->prepare($sql);
			$stmt->execute([':hash_safe' => $hash_safe]);

			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			$username = $row['account_lid'];

			if($username)
			{
				return $username;
			}

		// Alternative config
			$locations = new \App\Controllers\Api\Locations();
			$location_id = $locations->get_id('property', '.admin');

			$config = (new \App\Services\ConfigLocation($location_id))->read();

			try {
				if ($config['fellesdata']['host']) {
					if (!$this->ping($config['fellesdata']['host'])) {
						$message = "Database server {$config['fellesdata']['host']} is not accessible";
						\App\Services\Cache::message_set($message, 'error');
					}

					$dsn = "oci:dbname={$config['fellesdata']['host']}:{$config['fellesdata']['port']}/{$config['fellesdata']['db_name']}";
					$db = new PDO($dsn, $config['fellesdata']['user'], $config['fellesdata']['password']);
				} else {
					$config = (new \App\Services\Config('rental'))->read();

					if (!$config['external_db_host'] || !$this->ping($config['external_db_host'])) {
						$message = "Database server {$config['external_db_host']} is not accessible";
						\App\Services\Cache::message_set($message, 'error');
					}

					$dsn = "{$config['external_db_type']}:host={$config['external_db_host']};port={$config['external_db_port']};dbname={$config['external_db_name']}";
					$db = new PDO($dsn, $config['external_db_user'], $config['external_db_password']);
				}
			} catch (\PDOException $e) {
				$message = lang('unable_to_connect_to_database');
				\App\Services\Cache::message_set($message, 'error');
				return false;
			}

			$sql = "SELECT BRUKERNAVN FROM V_AD_PERSON WHERE FODSELSNR = :ssn";
			$stmt = $db->prepare($sql);
			$stmt->execute([':ssn' => $ssn]);

			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row) {
				$username = $row['BRUKERNAVN'];
				return $username;
			}
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
			$userSettings = \App\Services\Settings::getInstance()->get('user');
			$flags = \App\Services\Settings::getInstance()->get('flags');
			$accounts = new \App\Controllers\Api\Accounts\Accounts();


			$account_id = (int) $account_id;
			// Don't allow passwords changes for other accounts when using XML-RPC
			if ( !$account_id )
			{
				$account_id = $userSettings['account_id'];
			}

			if ($flags['currentapp'] == 'login')
			{
				if ( !$this->authenticate($accounts->id2lid($account_id), $old_passwd) )
				{
					return '';
				}
			}

			$hash_safe = $this->create_hash($new_passwd);
			$now = time();

			$sql = 'UPDATE phpgw_accounts'
				. " SET account_pwd = :hash_safe, account_lastpwd_change = :now"
				. " WHERE account_id = :account_id";

			$stmt = $this->db->prepare($sql);
			$result = $stmt->execute([
				':hash_safe' => $hash_safe,
				':now' => $now,
				':account_id' => $account_id
			]);

			if ($result) {
				return $hash_safe;
			}
			return '';		}

		/**
		* Update when the user last logged in
		*
		* @param int $account_id the user's account id
		* @param string $ip the source IP adddress for the request
		*/
		public function update_lastlogin($account_id, $ip)
		{
			$account_id = (int) $account_id;
			$now = time();

			$sql = 'UPDATE phpgw_accounts SET account_lastloginfrom = :ip, account_lastlogin = :now WHERE account_id = :account_id';
			$stmt = $this->db->prepare($sql);

			$stmt->execute([
				':ip' => $ip,
				':now' => $now,
				':account_id' => $account_id
			]);
		}
	}
