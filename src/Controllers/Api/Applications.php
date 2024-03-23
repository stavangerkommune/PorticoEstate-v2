<?php

/**
 * Applications manager functions
 * @author Mark Peters <skeeter@phpgroupware.org>
 * @author Dave Hall <skwashd@phpgroupware.org>
 * @copyright Copyright (C) 2001,2002 Mark Peters
 * @copyright Copyright (C) 2003 - 2008 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @package phpgwapi
 * @subpackage application
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
		GNU Lesser General Public License for more details.

		You should have received a copy of the GNU Lesser General Public License
		along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */

/**
 * Class for managing and installing applications
 *
 * @package phpgwapi
 * @subpackage application
 */
namespace App\Controllers\Api;
use App\Services\DatabaseObject;
use App\Services\Settings;
use App\Security\Acl;
use App\Services\Translation;
use PDOException;

class Applications
{
	/**
	 * @var int $account_id the current users' account id
	 */
	private $account_id;

	/**
	 * @var array data about the applications installed
	 */
	private $data = array();

	/**
	 * @var object $db Local reference to the global database object
	 */
	private $db;
	private $acl;

	/**
	 * @var array $public_function the methods of the class available via menuaction calls
	 */
	public $public_functions = array(
		'list_methods' => True,
		'read'         => True
	);

	/**
	 * Standard constructor for setting $account_id
	 *
	 * @param integer $account_id Account id
	 */
	public function __construct($account_id = 0)
	{
		$this->db = DatabaseObject::getInstance()->get('db');
		$this->acl =Acl::getInstance($account_id);

		$this->set_account_id($account_id);
	}

	/**
	 * Set the user's id
	 */
	public function set_account_id($account_id)
	{
		$this->account_id = $account_id  ? $account_id : Settings::getInstance()->get('account_id');
	}

	/**
	 * Get available xmlrpc or soap methods
	 *
	 * @param string|array $_type Type of methods to list: 'xmlrpc' or 'soap'
	 * @return array array touple (might be empty) with the following fields in the keys value array: function, signature, docstring
	 * This handles introspection or discovery by the logged in client,
	 * in which case the input might be an array.  The server always calls
	 * this function to fill the server dispatch map using a string.
	 */
	public function list_methods($_type = 'xmlrpc')
	{
		$translation = Translation::getInstance();

		if (is_array($_type)) {
			$_type = $_type['type'] ? $_type['type'] : $_type[0];
		}
		switch ($_type) {
			case 'xmlrpc':
				$xml_functions = array(
					'read' => array(
						'function'  => 'read',
						'signature' => array(array(xmlrpcStruct)),
						'docstring' => $translation->translate('Returns struct of users application access')
					),
					'list_methods' => array(
						'function'  => 'list_methods',
						'signature' => array(array(xmlrpcStruct, xmlrpcString)),
						'docstring' => $translation->translate('Read this list of methods.')
					)
				);
				return $xml_functions;
				/* SOAP disabled - no instance variable
				case 'soap':
					return $this->soap_functions;
				*/
			default:
				return array();
		}
	}


	// These are the standard $this->account_id specific functions


	/**
	 * Read application repository from ACLs
	 *
	 * @return array|boolean array with list of available applications or false
	 * @access private
	 */
	public function read_repository()
	{
		if (empty(Settings::getInstance()->get('apps'))) {
			$this->read_installed_apps();
		}
		$this->data = array();
		if ($this->account_id == False) {
			return array();
		}

		$apps = $this->acl->get_user_applications($this->account_id);
		$apps_admin = $this->acl->get_app_list_for_id('admin', Acl::ADD, $this->account_id);
		if ($apps_admin) {
			$apps['admin'] = true;
		}
		foreach ($apps_admin as $app_admin) {
			$apps[$app_admin] = true;
		}

		$installed_apps = Settings::getInstance()->get('apps');
		foreach ($installed_apps as $app) {
			if (isset($apps[$app['name']])) {
				$this->data[$app['name']] = array(
					'title'   => $installed_apps[$app['name']]['title'],
					'name'    => $app['name'],
					'enabled' => True,
					'status'  => $installed_apps[$app['name']]['status'],
					'id'      => $installed_apps[$app['name']]['id']
				);
			}
		}
		Settings::getInstance()->set('apps', $this->data);
		return $this->data;
	}

	/**
	 * Determine what applications a user has rights to
	 * 
	 * @return array List with applications for the user
	 */
	public function read(): array
	{
		if (!count($this->data)) {
			$this->read_repository();
		}
		return $this->data;
	}

	/**
	 * Add an application to a user profile
	 *
	 * @param string|array $apps array or string containing application names to add for a user
	 * @return array List with applications for the user
	 */
	public function add($apps)
	{
		$translation = Translation::getInstance();
		$installed_apps = Settings::getInstance()->get('apps');

		if (is_array($apps)) {
			foreach ($apps as $app) {
				$this->data[$app[1]] = array(
					'title'   =>  $translation->translate($app[1], array(), false, $app[1]),
					'name'    => $app[1],
					'enabled' => true,
					'status'  => $installed_apps[$app[1]]['status'],
					'id'      => $installed_apps[$app[1]]['id']
				);
			}
		} else if (is_string($apps)) {
			$this->data[$apps] = array(
				'title'   => $translation->translate($apps, array(), false, $apps),
				'name'    => $apps,
				'enabled' => true,
				'status'  => $installed_apps[$apps]['status'],
				'id'      => $installed_apps[$apps]['id']
			);
		}
		return $this->data;
	}

	/**
	 * Delete an application from a user profile
	 *
	 * @param string $appname Application name
	 * @return array List with applications for the user
	 */
	public function delete($appname)
	{
		if ($this->data[$appname]) {
			unset($this->data[$appname]);
		}
		return $this->data;
	}

	/**
	 * Update list of applications for a user
	 *
	 * @param array $data Update the list of applications
	 * @return array List with applications for the user
	 */
	public function update_data($data)
	{
		$this->data = $data;
		return $this->data;
	}

	/**
	 * Save the repository to the ACLs
	 *
	 * @return array List with applications for the user
	 */
	public function save_repository()
	{
		$num_rows = $this->acl->delete_repository("%%", 'run', $this->account_id);

		if (!is_array($this->data) || !count($this->data)) {
			return array();
		}

		foreach ($this->data as $app) {
			if (!$this->is_system_enabled($app)) {
				continue;
			}
			$this->acl->add_repository($app, 'run', $this->account_id, ACL_READ);
		}
		return $this->data;
	}


	// These are the non-standard $account_id specific functions


	public function app_perms()
	{
		if (count($this->data) == 0) {
			$this->read_repository();
		}
		foreach (array_keys($this->data) as $key) {
			$app[] = $this->data[$key]['name'];
		}
		return $app;
	}

	/**
	 * Get the list of installed application available for the current user
	 * @param bool $inherited - if to include rights inherited from groups
	 * @return array
	 */
	public function read_account_specific($inherited = false)
	{
		$translation = Translation::getInstance();

		if (!is_array(Settings::getInstance()->get('apps'))) {
			$this->read_installed_apps();
		}

		$installed_apps = Settings::getInstance()->get('apps');

		$app_list = $this->acl->get_app_list_for_id('run', 1, $this->account_id, $inherited);

		if (!is_array($app_list) || !count($app_list)) {
			return $this->data;
		}
		foreach ($app_list as $app) {
			if ($this->is_system_enabled($app)) {
				$this->data[$app] = array(
					'title'   => $translation->translate($app, array(), false, $app),
					'name'    => $app,
					'enabled' => true,
					'status'  => $installed_apps[$app]['status'],
					'id'      => $installed_apps[$app]['id']
				);
			}
		}
		return $this->data;
	}

	/*
		 * These are the generic functions. Not specific to $account_id
		 */

	/**
	 * Populate array with a list of installed apps
	 */
	public function read_installed_apps()
	{

		$translation = Translation::getInstance();

		$sql = 'SELECT * FROM phpgw_applications WHERE app_enabled != 0 ORDER BY app_order ASC';
		// get all installed apps
		try {
			$apps = $this->db->query($sql)->fetchAll();
		} catch (PDOException $e) {
			die("Error executing query: " . $e->getMessage());
		}
		$values = [];
		foreach ($apps as $key => $value) {
			$values[$value['app_name']] =
				[
					'name'    => $value['app_name'],
					'title'   => $translation->translate($value['app_name'], array(), false, $value['app_name']),
					'enabled' => true,
					'status'  => $value['app_enabled'],
					'id'      => (int) $value['app_id'],
					'version' => $value['app_version']
				];
		}

		Settings::getInstance()->set('apps', $values);
		return $values;
	}

	/**
	 * Test if an application is enabled
	 *
	 * @param array $appname Names of the applications to test for. When the type is different read_installed_apps() will be used.
	 * @return boolean True when the application is available otherwise false
	 * @see read_installed_apps()
	 */
	public function is_system_enabled($appname)
	{
		$installed_apps = Settings::getInstance()->get('apps');

		if (empty($installed_apps)) {
			$this->read_installed_apps();
		}
		return isset($installed_apps[$appname]) && $installed_apps[$appname]['enabled'];
	}

	/**
	 * Get the application name associated with the application id
	 *
	 * @param int $id the application id to look up
	 * @return string the application name - empty string if invalid
	 */
	public function id2name($id)
	{
		static $names = array();
		$installed_apps = Settings::getInstance()->get('apps');

		if (!isset($names[$id])) {
			$names[$id] = '';
			$id = (int) $id;
			foreach ($installed_apps as $appname => $app) {
				if ($app['id'] == $id) {
					$names[$id] = $appname;
				}
			}
		}
		return $names[$id];
	}

	/**
	 * Convert an application name to an id
	 *
	 * @param string $appname the application to lookup
	 * @return int the application id - 0 if invalid
	 */
	public function name2id($appname)
	{
		$installed_apps = Settings::getInstance()->get('apps');

		if (empty($installed_apps)|| !is_array($installed_apps)) {
			$this->read_installed_apps();
			$installed_apps = Settings::getInstance()->get('apps');
		}

		if (isset($installed_apps[$appname]) && is_array($installed_apps[$appname])	) {
			return $installed_apps[$appname]['id'];
		}
		return 0;
	}
}
