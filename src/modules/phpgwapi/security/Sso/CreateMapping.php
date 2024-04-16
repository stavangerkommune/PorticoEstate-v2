<?php

/**
 * phpGroupware
 *
 * phpgroupware base
 * @author Quang Vu DANG <quang_vu.dang@int-evry.fr>
 * @copyright Copyright (C) 2000-2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package phpgwapi
 * @subpackage sso
 * @version $Id$
 */

/**
 * The script provides an interface for creating the mapping if the user had an 
 * existing account in phpGroupware (to which he/she will have to authenticate 
 * during the process) and phpGroupware is configured to supports the mapping by table.
 *
 * Using with Single Sign-On(Shibbolelt, CAS, ...)
 */

 namespace App\modules\phpgwapi\security\Sso;

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\security\Mapping;
use Exception;

class CreateMapping
{
	/**
	 * @var array $serverSettings the server settings
	 */
	protected $serverSettings;
	private $mapping;

	public function __construct()
	{
		$this->serverSettings = Settings::getInstance()->get('server');
		if (!isset($this->serverSettings['mapping']) || $this->serverSettings['mapping'] == 'id') {
			throw new Exception(lang('Access denied'));
		}

		$this->mapping = new Mapping();

		if (!isset($_SERVER['REMOTE_USER'])) {
			throw new Exception(lang('Wrong configuration') . " REMOTE_USER not set");
		}
		if ($this->mapping->get_mapping($_SERVER['REMOTE_USER']) != '') {
			throw new Exception(lang('Username already taken'));
		}
	}

	public function create_mapping()
	{
		$error = array();
		if (isset($_POST) && isset($_POST['submitit'])) {
			$login		 = $_POST['login'];
			$password	 = $_POST['passwd'];
			$account_lid = $this->mapping->exist_mapping($_SERVER['REMOTE_USER']);
			if ($account_lid == '' || $account_lid == $login) {
				if ($this->mapping->valid_user($login, $password)) {
					$this->mapping->add_mapping($_SERVER['REMOTE_USER'], $login);
//FIXME: redirect..?
					phpgw::redirect_link('/login.php');
				} else {
					$_GET['cd'] = 5;
				}
			} else {
				$_GET['cd']				 = 21;
				$_GET['phpgw_account']	 = $account_lid;
			}
		}

/* FIXME: present the form ????
		$uilogin = new phpgw_uilogin(false);

		//Build vars :
		$variables					 = array();
		$variables['lang_message']	 = lang('this page let you build a mapping to an existing account !');
		$variables['lang_login']	 = lang('new mapping and login');
		$variables['partial_url']	 = 'login.php';
		$variables['extra_vars']	 = array('create_mapping' => true);
		if (isset($this->serverSettings['auto_create_acct']) && $this->serverSettings['auto_create_acct'] == True) {
			$variables['lang_additional_url']	 = lang('new account');
			$variables['additional_url']		 = phpgw::link('login.php', array('create_account' => true));
		}
		$uilogin->phpgw_display_login($variables);
*/
	}
}
