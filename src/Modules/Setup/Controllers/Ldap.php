<?php
	/**
	* Setup
	*
	* @copyright Copyright (C) 2000-2005 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @package setup
	* @version $Id$
	*/

	namespace App\Modules\Setup\Controllers;

	use App\Database\Db;
	use Psr\Http\Message\ResponseInterface as Response;
	use Psr\Http\Message\ServerRequestInterface as Request;
	use App\Modules\PhpGWApi\Services\Settings;
	use App\Modules\PhpGWApi\Services\Setup\Setup;
	use App\Modules\PhpGWApi\Services\Setup\Detection;
	use App\Modules\PhpGWApi\Services\Setup\Process;
	use App\Modules\PhpGWApi\Services\Setup\Html;
	use App\Helpers\Template;
	use App\Modules\PhpGWApi\Services\Setup\SetupTranslation;
	use App\Modules\PhpGWApi\Services\Sanitizer;
    use PDO;

	class Ldap
	{
		/**
		 * @var object
		 */
		private $db;
		private $detection;
		private $process;
		private $html;
		private $setup;
		private $setup_tpl;
		private $translation;
		
		public function __construct()
		{

			//setup_info
			Settings::getInstance()->set('setup_info', []); //$GLOBALS['setup_info']
			//setup_data
			Settings::getInstance()->set('setup', []); //$setup_data

			$this->db = Db::getInstance();
			$this->detection = new Detection();
			$this->process = new Process();
			$this->html = new Html();
			$this->setup = new Setup();
            $this->translation = new SetupTranslation();

			$flags = array(
				'noheader' 		=> True,
				'nonavbar'		=> True,
				'currentapp'	=> 'home',
				'noapi'			=> True,
				'nocachecontrol' => True
			);
			Settings::getInstance()->set('flags', $flags);


			// Check header and authentication
			if (!$this->setup->auth('Config')) {
				Header('Location: ../setup');
				exit;
			}

			$tpl_root = $this->html->setup_tpl_dir('setup');
			$this->setup_tpl = new Template($tpl_root);

			$this->html->set_tpl($this->setup_tpl);
		
		}

		
		public function index()
		{
	
			if (\Sanitizer::get_var('cancel', 'bool', 'POST')) {
				Header('Location: ../setup');
				exit;
			}

			$this->setup_tpl->set_file(array(
				'ldap'   => 'ldap.tpl',
				'T_head' => 'head.tpl',
				'T_footer' => 'footer.tpl',
				'T_alert_msg' => 'msg_alert_msg.tpl'
			));

			$ret_header = $this->html->get_header(lang('LDAP Config'), '', 'config', $this->db->get_domain());

			if (
				isset($GLOBALS['error']) && $GLOBALS['error']
			) {
				//echo '<br /><center><b>Error:</b> '.$error.'</center>';
				$this->html->show_alert_msg('Error', $GLOBALS['error']);
			}

			$this->setup_tpl->set_block('ldap', 'header', 'header');
			$this->setup_tpl->set_block('ldap', 'jump', 'jump');
			$this->setup_tpl->set_block('ldap', 'cancel_only', 'cancel_only');
			$this->setup_tpl->set_block('ldap', 'footer', 'footer');

			$this->setup_tpl->set_var('description', lang('LDAP Accounts Configuration'));
			$this->setup_tpl->set_var('lang_ldapmodify', lang('Modify an existing LDAP account store for use with phpGroupWare (for a new install using LDAP accounts)'));
			$this->setup_tpl->set_var('lang_ldapimport', lang('Import accounts from LDAP to the phpGroupware accounts table (for a new install using SQL accounts)'));
			$this->setup_tpl->set_var('lang_ldapexport', lang('Export phpGroupware accounts from SQL to LDAP'));
			$this->setup_tpl->set_var('lang_ldapdummy', lang('Setup demo accounts in LDAP'));
			$this->setup_tpl->set_var('ldapmodify', 'ldapmodify');
			$this->setup_tpl->set_var('ldapimport', 'ldapimport');
			$this->setup_tpl->set_var('ldapexport', 'ldapexport');
			$this->setup_tpl->set_var('ldapdummy', 'accounts');
			$this->setup_tpl->set_var('action_url', '/setup');
			$this->setup_tpl->set_var('cancel', lang('Cancel'));

			$header = $this->setup_tpl->fp('out', 'header');
			$jump = $this->setup_tpl->fp('out', 'jump');
			$cancel_only = 	$this->setup_tpl->fp('out', 'cancel_only');
			$footer = 	$this->setup_tpl->fp('out', 'footer');
			$ret_footer = $this->html->get_footer();

			return $ret_header . $header . $jump . $cancel_only . $footer . $ret_footer;
		}
	}