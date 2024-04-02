<?php
// File: src/Controllers/Setup/SetupController.php

namespace App\Controllers\Setup;

use App\Database\Db;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Services\Settings;
use App\Services\Setup\Setup;
use App\Services\Setup\Detection;
Use App\Services\Setup\Process;
Use App\Services\Setup\Html;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;


class SetupController
{
    private $twig;
	private $db;
	private $detection;
	private $process;
	private $html;
	private $setup;


    public function __construct()
    {
		$loader = new FilesystemLoader(SRC_ROOT_PATH . '/Modules/Setup/Templates');
		$this->twig = new Environment($loader, [
			'cache' => sys_get_temp_dir() . '/cache',
			'cache' => false, // To disable cache during development
		]);

		//setup_info
		Settings::getInstance()->set('setup_info', []);//$GLOBALS['setup_info']
		//setup_data
		Settings::getInstance()->set('setup', []);//$GLOBALS['phpgw_info']['setup']

		$this->db = Db::getInstance();
		$this->detection = new Detection();
		$this->process = new Process();
		$this->html = new Html();
		$this->setup = new Setup();
    }

    public function handler_eksempel(Request $request, Response $response, $args)
    {
        return $this->twig->render($response, '/Setup/head.twig', $args);
    }

    public function index(Request $request, Response $response, $args)
    {
		$setup_data = Settings::getInstance()->get('setup');
		$serverSettings = Settings::getInstance()->get('server');


        $GLOBALS['DEBUG'] = isset($_REQUEST['DEBUG']) && $_REQUEST['DEBUG'];

        $flags = array
        (
            'noheader' 		=> True,
            'nonavbar'		=> True,
            'currentapp'	=> 'home',
            'noapi'			=> True,
            'nocachecontrol'=> True
        );
		Settings::getInstance()->set('flags', $flags);
    
        /**
         * Include setup functions
         */
    //    require_once('./inc/functions.inc.php');
    
        @set_time_limit(0);
    
 /*        $tpl_root = $this->html->setup_tpl_dir('setup');
        $setup_tpl = CreateObject('phpgwapi.template',$tpl_root);
        $setup_tpl->set_file(array
        (
            'T_head'		=> 'head.tpl',
            'T_footer'		=> 'footer.tpl',
            'T_alert_msg'		=> 'msg_alert_msg.tpl',
            'T_login_main'		=> 'login_main.tpl',
            'T_login_stage_header'	=> 'login_stage_header.tpl',
            'T_setup_main'		=> 'setup_main.tpl',
            'T_setup_db_blocks'	=> 'setup_db_blocks.tpl',
            'T_setup_svn_blocks'	=> 'setup_svn_blocks.tpl',
    
        ));
    
        $setup_tpl->set_block('T_login_stage_header','B_multi_domain','V_multi_domain');
        $setup_tpl->set_block('T_login_stage_header','B_single_domain','V_single_domain');
    
        if(false)//enable svn check from setup
        {
            $setup_tpl->set_block('T_setup_svn_blocks','B_svn_stage_1','V_svn_stage_1');
            $setup_tpl->set_block('T_setup_svn_blocks','B_svn_stage_2','V_svn_stage_2');
            $setup_tpl->set_var('svn_step_text',lang('Step 0 - check for updates. The user %1 has to be member of sudoers and have a password',getenv('APACHE_RUN_USER')));
        }
    
        $setup_tpl->set_block('T_setup_db_blocks','B_db_stage_1','V_db_stage_1');
        $setup_tpl->set_block('T_setup_db_blocks','B_db_stage_2','V_db_stage_2');
        $setup_tpl->set_block('T_setup_db_blocks','B_db_stage_3','V_db_stage_3');
        $setup_tpl->set_block('T_setup_db_blocks','B_db_stage_4','V_db_stage_4');
        $setup_tpl->set_block('T_setup_db_blocks','B_db_stage_5','V_db_stage_5');
        $setup_tpl->set_block('T_setup_db_blocks','B_db_stage_6_pre','V_db_stage_6_pre');
        $setup_tpl->set_block('T_setup_db_blocks','B_db_stage_6_post','V_db_stage_6_post');
        $setup_tpl->set_block('T_setup_db_blocks','B_db_stage_10','V_db_stage_10');
        $setup_tpl->set_block('T_setup_db_blocks','B_db_stage_default','V_db_stage_default');
  */     
  	//	$setup_tpl->set_var('HeaderLoginWarning', lang('Warning: All your passwords (database, phpGroupWare admin,...)<br /> will be shown in plain text after you log in for header administration.'));
    //    $setup_tpl->set_var('lang_cookies_must_be_enabled', lang('<b>NOTE:</b> You must have cookies enabled to use setup and header admin!'));

		$templateVariables = [
			'HeaderLoginWarning' => lang('Warning: All your passwords (database, phpGroupWare admin,...)<br /> will be shown in plain text after you log in for header administration.'),
			'lang_cookies_must_be_enabled' => lang('<b>NOTE:</b> You must have cookies enabled to use setup and header admin!'),
			// Add other template variables here...
		];

		// Render a template
//		$output = $this->twig->render('login_main.twig', $templateVariables);

		// Write the rendered template to the response
	//	$response->getBody()->write($output);
//		return $response;
	

		// Check header and authentication
		$setup_data['stage']['header'] = $this->detection->check_header();

        if ($setup_data['stage']['header'] != '10')
        {
            Header('Location: manageheader.php');
            exit;
        }
        elseif (!$this->setup->auth('Config'))
        {

            $_POST['ConfigLang'] = isset($serverSettings['default_lang']) ? $serverSettings['default_lang'] : '';
            $this->html->show_header(lang('Please login'),True);
            $this->html->login_form();
            $this->html->show_footer();
            exit;
        }
    
        $this->setup->loaddb();
    
        // Add cleaning of app_sessions per skeeter, but with a check for the table being there, just in case
        // $this->setup->clear_session_cache();
    
        // Database actions
        $setup_info = $this->detection->get_versions();
		$setup_data['stage']['db'] = $this->detection->check_db();
        if ($setup_data['stage']['db'] != 1)
        {
            $setup_info = $this->detection->get_versions();
            $setup_info = $this->detection->get_db_versions($setup_info);
            $setup_data['stage']['db'] = $this->detection->check_db();
            if($GLOBALS['DEBUG'])
            {
				echo '<pre>';
                print_r($setup_info);
				echo '</pre>';
            }
        }
    
        if ($GLOBALS['DEBUG']) { echo 'Stage: ' . $setup_data['stage']['db']; }
        // begin DEBUG code
        //$setup_data['stage']['db'] = 0;
        //$action = 'Upgrade';
        // end DEBUG code
        /**
         * Update code  from SVN
         */
        $subtitle = '';
        $submsg = '';
        $subaction = '';
        $setup_data['stage']['svn'] = 1;//default
    
        switch( \Sanitizer::get_var('action_svn') )
        {
            case 'check_for_svn_update':
                $subtitle = lang('check for update');
                $submsg = lang('At your request, this script is going to attempt to check for updates from the svn server');
                $setup_data['currentver']['phpgwapi'] = 'check_for_svn_update';
                $setup_data['stage']['svn'] = 2;
                break;
            case 'perform_svn_update':
                $subtitle = lang('uppdating code');
                $submsg = lang('At your request, this script is going to attempt updating the system from the svn server') . '.';
                $setup_data['currentver']['phpgwapi'] = 'perform_svn_update';
                $setup_data['stage']['svn'] = 1; // alternate
                break;
        }
    
        $subtitle = '';
        $submsg = '';
        $subaction = '';
        switch( \Sanitizer::get_var('action') )
        {
            case 'Uninstall all applications':
                $subtitle = lang('Deleting Tables');
                $submsg = lang('Are you sure you want to delete your existing tables and data?') . '.';
                $subaction = lang('uninstall');
                $setup_data['currentver']['phpgwapi'] = 'predrop';
                $setup_data['stage']['db'] = 5;
                break;
            case 'Create Database':
                $subtitle = lang('Create Database');
                $submsg = lang('At your request, this script is going to attempt to create the database and assign the db user rights to it');
                $subaction = lang('created');
                $setup_data['currentver']['phpgwapi'] = 'dbcreate';
                $setup_data['stage']['db'] = 6;
                break;
            case 'REALLY Uninstall all applications':
                $subtitle = lang('Deleting Tables');
                $submsg = lang('At your request, this script is going to take the evil action of uninstalling all your apps, which deletes your existing tables and data') . '.';
                $subaction = lang('uninstalled');
                $setup_data['currentver']['phpgwapi'] = 'drop';
                $setup_data['stage']['db'] = 6;
                break;
            case 'Upgrade':
                $subtitle = lang('Upgrading Tables');
                $submsg = lang('At your request, this script is going to attempt to upgrade your old applications to the current versions').'.';
                $subaction = lang('upgraded');
                $setup_data['currentver']['phpgwapi'] = 'oldversion';
                $setup_data['stage']['db'] = 6;
                break;
            case 'Install':
                $subtitle = lang('Creating Tables');
                $submsg = lang('At your request, this script is going to attempt to install the core tables and the admin and preferences applications for you').'.';
                $subaction = lang('installed');
                $setup_data['currentver']['phpgwapi'] = 'new';
                $setup_data['stage']['db'] = 6;
                break;
        }
        $setup_tpl->set_var('subtitle', $subtitle);
        $setup_tpl->set_var('submsg', $submsg);
        $setup_tpl->set_var('subaction', $subaction);
    
        // Old PHP
        if (version_compare(phpversion(), '8.0.0', '<'))
        {
            $this->html->show_header($setup_data['header_msg'],True);
            $this->html->show_alert_msg('Error',
                 lang('You appear to be using PHP %1. Portico now requires PHP 8.0 or later', phpversion()) );
            $this->html->show_footer();
            exit;
        }
    
        // BEGIN setup page
    
        //$this->setup->app_status();
        $serverSettings['app_images'] = 'templates/base/images';
        $serverSettings['api_images'] = '../phpgwapi/templates/base/images';
        $incomplete = "{$serverSettings['api_images']}/stock_no.png";
        $completed  = "{$serverSettings['api_images']}/stock_yes.png";
    
        $setup_tpl->set_var('img_incomplete', $incomplete);
        $setup_tpl->set_var('img_completed', $completed);
    
        $setup_tpl->set_var('db_step_text',lang('Step 1 - Simple Application Management'));
    
        switch($setup_data['stage']['svn'])
        {
            case 1:
                $setup_tpl->set_var('sudo_user',lang('sudo user'));
                $setup_tpl->set_var('sudo_password',lang('password for %1', getenv('APACHE_RUN_USER')));
                $setup_tpl->set_var('svnwarn',lang('will try to perform a svn status -u'));
                $setup_tpl->set_var('check_for_svn_update',lang('check update'));
                $_svn_message = '';
                if(isset($setup_data['currentver']['phpgwapi']) && $setup_data['currentver']['phpgwapi'] == 'perform_svn_update')
                {
                    // $sudo_user		=  \Sanitizer::get_var('sudo_user');
                    // $sudo_password	=  \Sanitizer::get_var('sudo_password');
    
                    // $tmpfname = tempnam(sys_get_temp_dir(), "SVN");
                    // $handle = fopen($tmpfname, "w+");
                    // fwrite($handle, "{$sudo_password}\n");
                    // fclose($handle);
                    // putenv('LANG=en_US.UTF-8');
                    // $_command = "sudo -u {$sudo_user} -S svn up " . SRC_ROOT_PATH . " --config-dir /etc/subversion < {$tmpfname} 2>&1";
                    // exec($_command, $output, $returnStatus);
                    // unlink($tmpfname);
                    // $_svn_message = '<pre>' . print_r($output,true) . '</pre>';
                }
                $setup_tpl->set_var('svn_message',$_svn_message);
                $setup_tpl->parse('V_svn_stage_1','B_svn_stage_1');
                $svn_filled_block = $setup_tpl->get_var('V_svn_stage_1');
                $setup_tpl->set_var('V_svn_filled_block',$svn_filled_block);
    
                break;
            case 2:
                $setup_tpl->set_var('sudo_user',lang('sudo user'));
                $setup_tpl->set_var('value_sudo_user', \Sanitizer::get_var('sudo_user'));
                $setup_tpl->set_var('value_sudo_password', \Sanitizer::get_var('sudo_password'));
                $setup_tpl->set_var('sudo_password',lang('password for %1', getenv('APACHE_RUN_USER')));
                $setup_tpl->set_var('perform_svn_update',lang('perform svn update'));
                $setup_tpl->set_var('sudo_user',lang('sudo user'));
                $setup_tpl->set_var('sudo_password',lang('sudo password'));
                $setup_tpl->set_var('execute',lang('execute'));
                $setup_tpl->set_var('svnwarn',lang('will try to perform a svn up'));
                $_svn_message = '';
                if(isset($setup_data['currentver']['phpgwapi']) && $setup_data['currentver']['phpgwapi'] == 'check_for_svn_update')
                {
                    // $sudo_user		=  \Sanitizer::get_var('sudo_user');
                    // $sudo_password	=  \Sanitizer::get_var('sudo_password');
    
                    // $tmpfname = tempnam(sys_get_temp_dir(), "SVN");
                    // $handle = fopen($tmpfname, "w+");
                    // fwrite($handle, "{$sudo_password}\n");
                    // fclose($handle);
                    // putenv('LANG=en_US.UTF-8');
                    // $_command = "sudo -u {$sudo_user} -S svn status -u " . SRC_ROOT_PATH . " --config-dir /etc/subversion < {$tmpfname} 2>&1";
                    // exec($_command, $output, $returnStatus);
                    // unlink($tmpfname);
                    // $_svn_message = '<pre>' . print_r($output,true) . '</pre>';
                }
                $setup_tpl->set_var('svn_message',$_svn_message);
                $setup_tpl->parse('V_svn_stage_2','B_svn_stage_2');
                $svn_filled_block = $setup_tpl->get_var('V_svn_stage_2');
                $setup_tpl->set_var('V_svn_filled_block',$svn_filled_block);
    
                break;
            default:
                // 1 is default
        }
		$db_config = $this->db->get_config();

        switch($setup_data['stage']['db'])
        {
            case 1:
                $setup_tpl->set_var('dbnotexist',lang('Your Database is not working!'));
                $setup_tpl->set_var('makesure',lang('makesure'));
                $setup_tpl->set_var('notcomplete',lang('not complete'));
                $setup_tpl->set_var('oncesetup',lang('Once the database is setup correctly'));
                $setup_tpl->set_var('createdb',lang('Or we can attempt to create the database for you:'));
                $setup_tpl->set_var('create_database',lang('Create database'));

				switch ($db_config['db_type'])
                {
                    case 'mysql':
                        $setup_tpl->set_var('instr',lang('mysqlinstr %1', $db_config['db_name']));
                        $setup_tpl->set_var('db_root','root');
                        break;
					case 'pgsql':
					case 'postgres':
                        $setup_tpl->set_var('instr',lang('pgsqlinstr %1', $db_config['db_name']));
                        $setup_tpl->set_var('db_root','postgres');
                        break;
                }
                $setup_tpl->parse('V_db_stage_1','B_db_stage_1');
                $db_filled_block = $setup_tpl->get_var('V_db_stage_1');
                $setup_tpl->set_var('V_db_filled_block',$db_filled_block);
                break;
            case 2:
                $setup_tpl->set_var('prebeta',lang('You appear to be running a pre-beta version of phpGroupWare.<br />These versions are no longer supported, and there is no upgrade path for them in setup.<br /> You may wish to first upgrade to 0.9.10 (the last version to support pre-beta upgrades) <br />and then upgrade from there with the current version.'));
                $setup_tpl->set_var('notcomplete',lang('not complete'));
                $setup_tpl->parse('V_db_stage_2','B_db_stage_2');
                $db_filled_block = $setup_tpl->get_var('V_db_stage_2');
                $setup_tpl->set_var('V_db_filled_block',$db_filled_block);
                break;
            case 3:
                $setup_tpl->set_var('dbexists',lang('Your database is working, but you dont have any applications installed'));
                $setup_tpl->set_var('install',lang('Install'));
                $setup_tpl->set_var('proceed',lang('We can proceed'));
                $setup_tpl->set_var('coreapps',lang('all core tables and the admin and preferences applications'));
                $setup_tpl->parse('V_db_stage_3','B_db_stage_3');
                $db_filled_block = $setup_tpl->get_var('V_db_stage_3');
                $setup_tpl->set_var('V_db_filled_block',$db_filled_block);
                break;
            case 4:
                $setup_tpl->set_var('oldver',lang('You appear to be running version %1 of phpGroupWare',$setup_info['phpgwapi']['currentver']));
                $setup_tpl->set_var('automatic',lang('We will automatically update your tables/records to %1',$setup_info['phpgwapi']['version']));
                $setup_tpl->set_var('backupwarn',lang('backupwarn'));
                $setup_tpl->set_var('upgrade',lang('Upgrade'));
                $setup_tpl->set_var('goto',lang('Go to'));
                $setup_tpl->set_var('configuration',lang('configuration'));
                $setup_tpl->set_var('applications',lang('Manage Applications'));
                $setup_tpl->set_var('language_management',lang('Manage Languages'));
                $setup_tpl->set_var('uninstall_all_applications',lang('Uninstall all applications'));
                $setup_tpl->set_var('dont_touch_my_data',lang('Dont touch my data'));
                $setup_tpl->set_var('dropwarn',lang('Your tables may be altered and you may lose data'));
    
                $setup_tpl->parse('V_db_stage_4','B_db_stage_4');
                $db_filled_block = $setup_tpl->get_var('V_db_stage_4');
                $setup_tpl->set_var('V_db_filled_block',$db_filled_block);
                break;
            case 5:
                $setup_tpl->set_var('are_you_sure',lang('ARE YOU SURE?'));
                $setup_tpl->set_var('really_uninstall_all_applications',lang('REALLY Uninstall all applications'));
                $setup_tpl->set_var('dropwarn',lang('Your tables will be dropped and you will lose data'));
                $setup_tpl->set_var('cancel',lang('cancel'));
                $setup_tpl->parse('V_db_stage_5','B_db_stage_5');
                $db_filled_block = $setup_tpl->get_var('V_db_stage_5');
                $setup_tpl->set_var('V_db_filled_block',$db_filled_block);
                break;
            case 6:
                $setup_tpl->set_var('status',lang('Status'));
                $setup_tpl->set_var('notcomplete',lang('not complete'));
                $setup_tpl->set_var('tblchange',lang('Table Change Messages'));
                $setup_tpl->parse('V_db_stage_6_pre','B_db_stage_6_pre');
                $db_filled_block = $setup_tpl->get_var('V_db_stage_6_pre');
    
                flush();
                //ob_start();
               $this->db->set_halt_on_error('yes');
    
                switch ($setup_data['currentver']['phpgwapi'])
                {
                    case 'dbcreate':
                        try
                        {
                           $this->db->create_database($_POST['db_root'], $_POST['db_pass']);
                        }
                        catch (\Exception $e)
                        {
                            if($e)
                            {
                                $setup_tpl->set_var('status','Error: ' . $e->getMessage());
                            }
                        }
                        break;
                    case 'drop':
                        $setup_info = $this->detection->get_versions($setup_info);
                        $setup_info = $this->process->droptables($setup_info);
                        break;
                    case 'new':
                        // process all apps and langs(last param True), excluding apps with the no_mass_update flag set.
                        //$setup_info = $this->detection->upgrade_exclude($setup_info);
    
                        // Only process phpgwapi, admin and preferences.
                        $setup_info = $this->detection->base_install($setup_info);
                        $setup_info = $this->process->pass($setup_info, 'new', false, true);
                        $GLOBALS['included'] = True;
                        include_once('lang.php');
                        $setup_data['currentver']['phpgwapi'] = 'oldversion';
                        break;
                    case 'oldversion':
                        $setup_info = $this->process->pass($GLOBALS['setup_info'],'upgrade',$GLOBALS['DEBUG']);
                        $setup_data['currentver']['phpgwapi'] = 'oldversion';
                        break;
                }
                //ob_end_clean();
    
               $this->db->set_halt_on_error('no');
    
                $setup_tpl->set_var('tableshave',lang('If you did not receive any errors, your applications have been'));
                $setup_tpl->set_var('re-check_my_installation',lang('Re-Check My Installation'));
                $setup_tpl->parse('V_db_stage_6_post','B_db_stage_6_post');
                $db_filled_block = $db_filled_block . $setup_tpl->get_var('V_db_stage_6_post');
                $setup_tpl->set_var('V_db_filled_block',$db_filled_block);
                break;
            case 10:
                $setup_tpl->set_var('tablescurrent',lang('Your applications are current'));
                $setup_tpl->set_var('uninstall_all_applications',lang('Uninstall all applications'));
                $setup_tpl->set_var('insanity',lang('Insanity'));
                $setup_tpl->set_var('dropwarn',lang('Your tables will be dropped and you will lose data'));
                $setup_tpl->set_var('deletetables',lang('Uninstall all applications'));
                $setup_tpl->parse('V_db_stage_10','B_db_stage_10');
                $db_filled_block = $setup_tpl->get_var('V_db_stage_10');
                $setup_tpl->set_var('V_db_filled_block',$db_filled_block);
                break;
            default:
                $setup_tpl->set_var('dbnotexist',lang('Your database does not exist'));
                $setup_tpl->parse('V_db_stage_default','B_db_stage_default');
                $db_filled_block = $setup_tpl->get_var('V_db_stage_default');
                $setup_tpl->set_var('V_db_filled_block',$db_filled_block);
        }
    
        // Config Section
        $setup_tpl->set_var('config_step_text',lang('Step 2 - Configuration'));
        $setup_data['stage']['config'] = $this->detection->check_config();
    
        // begin DEBUG code
        //$setup_data['stage']['config'] = 10;
        // end DEBUG code
    
        switch($setup_data['stage']['config'])
        {
            case 1:
                $setup_tpl->set_var('config_status_img',$incomplete);
                $setup_tpl->set_var('config_status_alt',lang('not completed'));
                $btn_config_now = $this->html->make_frm_btn_simple(
                    lang('Please configure phpGroupWare for your environment'),
                    'POST','config.php',
                    'submit',lang('Configure Now'),
                    '');
                $setup_tpl->set_var('config_table_data',$btn_config_now);
                $setup_tpl->set_var('ldap_table_data','&nbsp;');
                break;
            case 10:
                $setup_tpl->set_var('config_status_img',$completed);
                $setup_tpl->set_var('config_status_alt',lang('completed'));
                $completed_notice = '';
				$stmt = $this->db->prepare("SELECT config_value FROM phpgw_config WHERE config_app = 'phpgwapi' AND config_name='files_dir'");
				$stmt->execute();
				$files_dir = $stmt->fetchColumn();

				$stmt = $this->db->prepare("SELECT config_value FROM phpgw_config WHERE config_app = 'phpgwapi' AND config_name='file_store_contents'");
				$stmt->execute();
				$file_store_contents = $stmt->fetchColumn();
                if($files_dir && $file_store_contents == 'filesystem')
                {
                    if(!is_dir($files_dir))
                    {
                        $completed_notice .= '<br /><b>' . lang('files dir %1 is not a directory', $files_dir) . '</b>';
                    }
                    if(!is_readable($files_dir))
                    {
                        $completed_notice .= '<br /><b>' . lang('files dir %1 is not readable', $files_dir) . '</b>';
                    }
                    if(!is_writable($files_dir))
                    {
                        $completed_notice .= '<br /><b>' . lang('files dir %1 is not writeable', $files_dir) . '</b>';
                    }
                }
				$stmt = $this->db->prepare("SELECT config_value FROM phpgw_config WHERE config_app = 'phpgwapi' AND config_name='temp_dir'");
				$stmt->execute();
				$temp_dir = $stmt->fetchColumn();
                if($temp_dir)
                {
                    if(!is_dir($temp_dir))
                    {
                        $completed_notice .= '<br /><b>' . lang('temp dir %1 is not a directory', $temp_dir) . '</b>';
                    }
                    if(!is_readable($temp_dir))
                    {
                        $completed_notice .= '<br /><b>' . lang('temp dir %1 is not readable', $temp_dir) . '</b>';
                    }
                    if(!is_writable($temp_dir))
                    {
                        $completed_notice .= '<br /><b>' . lang('temp dir %1 is not writeable', $temp_dir) . '</b>';
                    }
                }
    
                $btn_edit_config = $this->html->make_frm_btn_simple(
                    lang('Configuration completed'),
                    'POST','config.php',
                    'submit',lang('Edit Current Configuration'),
                    $completed_notice
                );
    
                if($completed_notice)
                {
                    $this->html->show_alert_msg('Error', $completed_notice );
                }
    
				$stmt = $this->db->prepare("SELECT config_value FROM phpgw_config WHERE config_name='auth_type'");
				$stmt->execute();
				$auth_type = $stmt->fetchColumn();

				if ($auth_type == 'ldap') {
					$stmt = $this->db->prepare("SELECT config_value FROM phpgw_config WHERE config_name='ldap_host'");
					$stmt->execute();
					$ldap_host = $stmt->fetchColumn();

					if ($ldap_host != '') {
						$btn_config_ldap = $this->html->make_frm_btn_simple(
							lang('LDAP account import/export'),
							'POST','ldap.php',
							'submit',lang('Configure LDAP accounts'),
							''
						);
					} else {
						$btn_config_ldap = '';
					}

					$stmt = $this->db->prepare("SELECT config_value FROM phpgw_config WHERE config_name='webserver_url'");
					$stmt->execute();
					$webserver_url = $stmt->fetchColumn();

					if ($webserver_url) {
						/* NOTE: we assume here ldap doesn't delete accounts */
						$link_make_accts = $this->html->make_href_link_simple(
							'<br>',
							'accounts.php',
							lang('Setup an Admininstrator account'),
							lang('and optional demo accounts.')
						);
					} else {
						$link_make_accts = '&nbsp;';
					}
				}
                else
                {
					$btn_config_ldap = '';

					$stmt = $this->db->prepare("SELECT config_value FROM phpgw_config WHERE config_name = 'account_repository'");
					$stmt->execute();
					$account_repository = $stmt->fetchColumn();

					$account_creation_notice = lang('and optional demo accounts.');

					if ($account_repository == 'sql') {
						$stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM phpgw_accounts");
						$stmt->execute();
						$number_of_accounts = (int) $stmt->fetchColumn();

						if ($number_of_accounts > 0) {
							$account_creation_notice .= lang('<br /><b>This will delete all existing accounts.</b>');
						}
					}    
                     $link_make_accts = $this->html->make_href_link_simple(
                         '<br>',
                         'accounts.php',
                         lang('Setup an Admininstrator account'),
                        $account_creation_notice
                     );
                }
                $config_td = "$btn_edit_config" ."$link_make_accts";
                $setup_tpl->set_var('config_table_data',$config_td);
                $setup_tpl->set_var('ldap_table_data',$btn_config_ldap);
                break;
            default:
                $setup_tpl->set_var('config_status_img',$incomplete);
                $setup_tpl->set_var('config_status_alt',lang('not completed'));
                $setup_tpl->set_var('config_table_data',lang('Not ready for this stage yet'));
                $setup_tpl->set_var('ldap_table_data','&nbsp;');
        }
    
        // Lang Section
        $setup_tpl->set_var('lang_step_text',lang('Step 3 - Language Management'));
		$setup_data['stage']['lang'] = $this->detection->check_lang();
		$setup_data = Settings::getInstance()->get('setup');
    
        // begin DEBUG code
        //$GLOBALS['phpgw_info']['setup']['stage']['lang'] = 0;
        // end DEBUG code
    
        switch($setup_data['stage']['lang'])
        {
            case 1:
                $setup_tpl->set_var('lang_status_img',$incomplete);
                $setup_tpl->set_var('lang_status_alt','not completed');
                $btn_install_lang = $this->html->make_frm_btn_simple(
                    lang('You do not have any languages installed. Please install one now <br />'),
                    'POST','lang.php',
                    'submit',lang('Install Language'),
                    '');
                $setup_tpl->set_var('lang_table_data',$btn_install_lang);
                break;
            case 10:
                $langs_list = '';
                //reset ($GLOBALS['phpgw_info']['setup']['installed_langs']);
                //while (list ($key, $value) = each ($GLOBALS['phpgw_info']['setup']['installed_langs']))
                foreach($setup_data['installed_langs'] as $key => $value)
                {
                    if($value)
                    {
                        $langs_list .= ($langs_list?', ':'') . $value;
                    }
                }
                $setup_tpl->set_var('lang_status_img',$completed);
                $setup_tpl->set_var('lang_status_alt','completed');
                $btn_manage_lang = $this->html->make_frm_btn_simple(
                    lang('This stage is completed') . '<br/>' .  lang('Currently installed languages: %1',$langs_list) . ' <br/>',
                    'POST','lang.php',
                    'submit',lang('Manage Languages'),
                    '');
                $setup_tpl->set_var('lang_table_data',$btn_manage_lang);
                break;
            default:
                $setup_tpl->set_var('lang_status_img',$incomplete);
                $setup_tpl->set_var('lang_status_alt',lang('not completed'));
                $setup_tpl->set_var('lang_table_data',lang('Not ready for this stage yet'));
        }
    
        $setup_tpl->set_var('apps_step_text',lang('Step 4 - Advanced Application Management'));
    //	$setup_data['stage']['apps'] = $this->setup->check_apps();
        if ( !isset($setup_data['stage']['db']) )
        {
            $setup_data['stage']['db'] = null;
        }
    
        switch($setup_data['stage']['db'])
        {
            case 10:
                $setup_tpl->set_var('apps_status_img',$completed);
                $setup_tpl->set_var('apps_status_alt',lang('completed'));
                $btn_manage_apps = $this->html->make_frm_btn_simple(
                    lang('This stage is completed')  . '<br/>',
                    '','applications.php',
                    'submit',lang('Manage Applications'),
                    '');
                $setup_tpl->set_var('apps_table_data',$btn_manage_apps);
                break;
            default:
                $setup_tpl->set_var('apps_status_img',$incomplete);
                $setup_tpl->set_var('apps_status_alt',lang('not completed'));
                $setup_tpl->set_var('apps_table_data',lang('Not ready for this stage yet'));
        }
    
        if ( !isset($setup_data['header_msg']) )
        {
            $setup_data['header_msg'] = '';
        }
    
        
		$this->html->show_header(
            $setup_data['header_msg'],
            False,
            'config',
			$this->db->get_domain() . '(' . $db_config['db_type'] . ')'
        );
        $setup_tpl->pparse('out','T_setup_main');
        $this->html->show_footer();

    }
}