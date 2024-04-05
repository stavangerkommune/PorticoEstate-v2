<?php
	/**
	* Setup html
	* @author Tony Puglisi (Angles) <angles@phpgroupware.org>
	* @author Miles Lott <milosch@phpgroupware.org>
	* @copyright Portions Copyright (C) 2004 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.fsf.org/licenses/gpl.html GNU General Public License
	* @package phpgwapi
	* @subpackage application
	* @version $Id$
	*/
    namespace App\Modules\Api\Services\Setup;

	use App\Modules\Api\Services\Settings;
	use Sanitizer;

	/**
	* Setup html
	*
	* @package phpgwapi
	* @subpackage application
	*/
	class Html
	{
		protected $setup_tpl;

		function set_tpl($tpl)
		{
			$this->setup_tpl = $tpl;
		}
		/**
		 * generate header.inc.php file output - NOT a generic html header function
		*
		 */
		function generate_header()
		{
			$GLOBALS['header_template']->set_file(array('header' => 'header.inc.php.template'));
			$GLOBALS['header_template']->set_block('header','domain','domain');
			$var = Array();

			$deletedomain = Sanitizer::get_var('deletedomain', 'string', 'POST');
			$domains = Sanitizer::get_var('domains', 'string', 'POST');
			if( !is_array($domains) )
			{
				$domains = array();
			}

			$setting = Sanitizer::get_var('setting', 'raw', 'POST');
			$settings = Sanitizer::get_var("settings", 'raw', 'POST');

			foreach($domains as $k => $v)
			{
				if(isset($deletedomain[$k]))
				{
					continue;
				}
				$dom = $settings[$k];
				$GLOBALS['header_template']->set_var('DB_DOMAIN',$v);
				foreach($dom as $x => $y)
				{
					if( ((isset($setting['enable_mcrypt']) && $setting['enable_mcrypt'] == 'True') || !empty($setting['enable_crypto'])) && ($x == 'db_pass' || $x == 'db_host' || $x == 'db_port' || $x == 'db_name' || $x == 'db_user' || $x == 'config_pass'))
					{
						$y = $GLOBALS['phpgw']->crypto->encrypt($y);
					}
					$GLOBALS['header_template']->set_var(strtoupper($x),$y);
				}
				$GLOBALS['header_template']->parse('domains','domain',True);
			}

			$GLOBALS['header_template']->set_var('domain','');

			if(!empty($setting) && is_array($setting))
			{
				foreach($setting as $k => $v)
				{
					if (((isset($setting['enable_mcrypt']) && $setting['enable_mcrypt'] == 'True')  || !empty($setting['enable_crypto']))&& $k == 'HEADER_ADMIN_PASSWORD')
					{
						$v = $GLOBALS['phpgw']->crypto->encrypt($v);
					}
					
					if( in_array( $k, array( 'server_root', 'include_root' ) )
						&& substr(PHP_OS, 0, 3) == 'WIN' )
					{
						$v = str_replace( '\\', '/', $v );
					}
					$var[strtoupper($k)] = $v;
				}
			}
			$GLOBALS['header_template']->set_var($var);
			return $GLOBALS['header_template']->parse('out','header');
		}

		function setup_tpl_dir($app_name='setup')
		{
			/* hack to get tpl dir */
			if (is_dir(SRC_ROOT_PATH))
			{
				$srv_root = SRC_ROOT_PATH . "/Modules/" . ucfirst($app_name);
			}
			else
			{
				$srv_root = '';
			}

			return "{$srv_root}/Templates/base";
		}

		function show_header($title = '', $nologoutbutton = False, $logoutfrom = 'config', $configdomain = '')
		{
			print $this->get_header($title, $nologoutbutton, $logoutfrom, $configdomain);
		}

		function get_header($title = '', $nologoutbutton = False, $logoutfrom = 'config', $configdomain = '')
		{
			$serverSettings = Settings::getInstance()->get('server');
			$this->setup_tpl->set_var('lang_charset',lang('charset'));
			$style = array('th_bg'		=> '#486591',
					'th_text'	=> '#FFFFFF',
					'row_on'	=> '#DDDDDD',
					'row_off'	=> '#EEEEEE',
					'banner_bg'	=> '#4865F1',
					'msg'		=> '#FF0000',
					);
			$this->setup_tpl->set_var($style);
			if ($nologoutbutton)
			{
				$btn_logout = '&nbsp;';
			}
			else
			{
				$btn_logout = '<a href="/setup/logout?FormLogout=' . $logoutfrom . '" class="link">' . lang('Logout').'</a>';
			}

			$this->setup_tpl->set_var('lang_version', lang('version'));
			$this->setup_tpl->set_var('lang_setup', lang('setup'));
			$this->setup_tpl->set_var('page_title',$title);
			if ($configdomain == '')
			{
				$this->setup_tpl->set_var('configdomain','');
			}
			else
			{
				$this->setup_tpl->set_var('configdomain',' - ' . lang('Domain') . ': ' . $configdomain);
			}

			$api_version = isset($serverSettings['versions']['phpgwapi']) ? $serverSettings['versions']['phpgwapi'] : '';
			
			$version = isset($serverSettings['versions']['system']) ? $serverSettings['versions']['system'] : $api_version;

			$this->setup_tpl->set_var('pgw_ver',$version);
			$this->setup_tpl->set_var('logoutbutton',$btn_logout);
			return $this->setup_tpl->fp('out','T_head');
			/* $setup_tpl->set_var('T_head',''); */
		}

		function get_footer()
		{
			$footer = $this->setup_tpl->fp('out', 'T_footer');
			unset($this->setup_tpl);
			return $footer;
		}
		function show_footer()
		{
			print $this->get_footer();
		}

		function show_alert_msg($alert_word='Setup alert',$alert_msg='setup alert (generic)')
		{
			$this->setup_tpl->set_var('V_alert_word',$alert_word);
			$this->setup_tpl->set_var('V_alert_msg',$alert_msg);
			$this->setup_tpl->pparse('out','T_alert_msg');
		}

		function make_frm_btn_simple($pre_frm_blurb='',$frm_method='POST',$frm_action='',$input_type='submit',$input_value='',$post_frm_blurb='')
		{
			/* a simple form has simple components */
			$simple_form = $pre_frm_blurb  ."\n"
				. '<form method="' . $frm_method . '" action="' . $frm_action  . '">' . "\n"
				. '<input type="'  . $input_type . '" value="'  . $input_value . '">' . "\n"
				. '</form>' . "\n"
				. $post_frm_blurb . "\n";
			return $simple_form;
		}

		function make_href_link_simple($pre_link_blurb='',$href_link='',$href_text='default text',$post_link_blurb='')
		{
			/* a simple href link has simple components */
			$simple_link = $pre_link_blurb
				. '<a href="' . $href_link . '">' . $href_text . '</a> '
				. $post_link_blurb . "\n";
			return $simple_link;
		}
	
	}
