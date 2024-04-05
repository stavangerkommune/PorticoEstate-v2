<?php

namespace App\Modules\Setup\Controllers;

use App\Database\Db;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use App\Modules\Api\Services\Settings;
use App\Modules\Api\Services\Setup\Setup;
use App\Modules\Api\Services\Setup\Detection;
Use App\Modules\Api\Services\Setup\Process;
Use App\Modules\Api\Services\Setup\Html;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\Helpers\Template;

Use App\Modules\Setup\Controllers\SqlToArray;


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
	//	$loader = new FilesystemLoader(SRC_ROOT_PATH . '/Modules/Setup/Templates');
	//	$this->twig = new Environment($loader, [
	//		'cache' => sys_get_temp_dir() . '/cache',
	//		'cache' => false, // To disable cache during development
	//	]);

		//setup_info
		Settings::getInstance()->set('setup_info', []);//$GLOBALS['setup_info']
		//setup_data
		Settings::getInstance()->set('setup', []);//$GLOBALS['phpgw_info']['setup']

		$this->db = Db::getInstance();
		$this->detection = new Detection();
		$this->process = new Process();
		$this->html = new Html();
		$this->setup = new Setup();

		$flags = array(
			'noheader' 		=> True,
			'nonavbar'		=> True,
			'currentapp'	=> 'home',
			'noapi'			=> True,
			'nocachecontrol' => True
		);
		Settings::getInstance()->set('flags', $flags);
    

    }

    public function logout(Request $request, Response $response, $args)
    {
		$_POST['FormLogout'] = $_GET['FormLogout'];
		$this->setup->auth('Config');
		Header('Location: ../setup');
		exit;
    }

	/**
	 * Parse dependencies
	 * 
	 * @param array $depends
	 * @param boolean $main Return a string when true otherwise an array
	 * @return string|array Dependency string or array
	 */
	function parsedep($depends, $main = True)
	{
		$ret = array();
		foreach ($depends as $b) {
			$depstring = '';
			foreach ($b as $c => $d) {
				if (is_array($d)) {
					$depstring .= "($c : " . implode(', ', $d) . ')';
					$depver[] = $d;
				} else {
					$depstring .= $d . " ";
					$depapp[] = $d;
				}
			}
			$ret[] = $depstring;
		}
		if ($main) {
			return implode("<br/>\n", $ret);
		} else {
			return array($depapp, $depver);
		}
	}


	public function	sqltoarray(Request $request, Response $response, $args)
	{

		$SqlToArray = new SqlToArray();
		$ret = $SqlToArray->index();

		$response = new \Slim\Psr7\Response();
		$response->getBody()->write($ret);
		return $response;

	}
	public function applications(Request $request, Response $response, $args)
	{

		if (\Sanitizer::get_var('cancel', 'bool', 'POST')) {
			Header('Location: setup');
			exit;
		}

		@set_time_limit(0);

		$DEBUG = \Sanitizer::get_var('debug', 'bool');


		// Check header and authentication
		if (!$this->setup->auth('Config')) {
			Header('Location: setup');
			exit;
		}
		// Does not return unless user is authorized

		$ConfigDomain = \Sanitizer::get_var('ConfigDomain');

		$tpl_root = $this->html->setup_tpl_dir('setup');
		$setup_tpl = new Template($tpl_root);

		$setup_tpl->set_file(array(
			'T_head' => 'head.tpl',
			'T_footer' => 'footer.tpl',
			'T_alert_msg' => 'msg_alert_msg.tpl',
			'T_login_main' => 'login_main.tpl',
			'T_login_stage_header' => 'login_stage_header.tpl',
			'T_setup_main' => 'applications.tpl'
		));

		$this->html->set_tpl($setup_tpl);

		$setup_tpl->set_block('T_login_stage_header', 'B_multi_domain', 'V_multi_domain');
		$setup_tpl->set_block('T_login_stage_header', 'B_single_domain', 'V_single_domain');
		$setup_tpl->set_block('T_setup_main', 'header', 'header');
		$setup_tpl->set_block('T_setup_main', 'app_header', 'app_header');
		$setup_tpl->set_block('T_setup_main', 'apps', 'apps');
		$setup_tpl->set_block('T_setup_main', 'detail', 'detail');
		$setup_tpl->set_block('T_setup_main', 'table', 'table');
		$setup_tpl->set_block('T_setup_main', 'hook', 'hook');
		$setup_tpl->set_block('T_setup_main', 'dep', 'dep');
		$setup_tpl->set_block('T_setup_main', 'app_footer', 'app_footer');
		$setup_tpl->set_block('T_setup_main', 'submit', 'submit');
		$setup_tpl->set_block('T_setup_main', 'footer', 'footer');
		$setup_tpl->set_var('lang_cookies_must_be_enabled', lang('<b>NOTE:</b> You must have cookies enabled to use setup and header admin!'));


		$this->setup->loaddb();


		$serverSettings = Settings::getInstance()->get('server');
		$setup_data = Settings::getInstance()->get('setup');
		$setup_info = Settings::getInstance()->get('setup_info');


		$setup_data['stage']['db'] = $this->detection->check_db();

		$setup_info = $this->detection->get_versions();
		//var_dump($setup_info);exit;
		$setup_info = $this->detection->get_db_versions($setup_info);
		//var_dump($setup_info);exit;
		$setup_info = $this->detection->compare_versions($setup_info);
		//var_dump($setup_info);exit;
		$setup_info = $this->detection->check_depends($setup_info);
		//var_dump($setup_info);exit;
		ksort($setup_info);

		$db_config = $this->db->get_config();
		$header = '';

		if (\Sanitizer::get_var('submit', 'string', 'POST')) {
			$header .= $this->html->get_header(lang('Application Management'), False, 'config', $this->db->get_domain() . '(' . $db_config['db_type'] . ')');
			$setup_tpl->set_var('description', lang('App install/remove/upgrade') . ':');
			$header .= $setup_tpl->fp('out', 'header');

			$appname = \Sanitizer::get_var('appname', 'string', 'POST');
			$remove  = \Sanitizer::get_var('remove', 'string', 'POST');
			$install = \Sanitizer::get_var('install', 'string', 'POST');
			$upgrade = \Sanitizer::get_var('upgrade', 'string', 'POST');

			if (!isset($this->process->oProc) || !$this->process->oProc) {
				$this->process->init_process();
			}

			//$this->process->add_credential('property');
			if (!empty($remove) && is_array($remove)) {
				$this->process->oProc->m_odb->transaction_begin();
				foreach ($remove as $appname => $key) {
					$header .=  '<h3>' . lang('Processing: %1', lang($appname)) . "</h3>\n<ul>";
					$terror = array($setup_info[$appname]);

					if (
						isset($setup_info[$appname]['tables'])
						&& $setup_info[$appname]['tables']
					) {
						$this->process->droptables($terror, $DEBUG);
						$header .=  '<li>' . lang('%1 tables dropped', lang($appname)) . ".</li>\n";
					}

					if (
						isset($setup_info[$appname]['views'])
						&& $setup_info[$appname]['views']
					) {
						$this->process->dropviews($terror, $DEBUG);
						$header .=  '<li>' . lang('%1 views dropped', lang($appname)) . ".</li>\n";
					}

					$this->setup->deregister_app($appname);
					$header .=  '<li>' . lang('%1 deregistered', lang($appname)) . ".</li>\n";

					if (
						isset($setup_info[$appname]['hooks'])
						&& $setup_info[$appname]['hooks']
					) {
						$this->setup->deregister_hooks($appname);
						$header .=  '<li>' . lang('%1 hooks deregistered', lang($appname)) . ".</li>\n";
					}

					$terror = $this->process->drop_langs($terror, $DEBUG);
					$header .=  '<li>' . lang('%1 translations removed', $appname) . ".</li>\n</ul>\n";
				}
				$this->process->oProc->m_odb->transaction_commit();
			}

			if (!empty($install) && is_array($install)) {
				$this->process->oProc->m_odb->transaction_begin();
				foreach ($install as $appname => $key) {
					$header .=  '<h3>' . lang('Processing: %1', lang($appname)) . "</h3>\n<ul>";
					$terror = array($setup_info[$appname]);

					if (
						isset($setup_info[$appname]['tables'])
						&& is_array($setup_info[$appname]['tables'])
					) {
						$terror = $this->process->current($terror, $DEBUG);
						$header .=  "<li>{$setup_info[$appname]['name']} "
						. lang('tables installed, unless there are errors printed above') . ".</h3>\n";
						$terror = $this->process->default_records($terror, $DEBUG);
						$header .=  '<li>' . lang('%1 default values processed', lang($appname)) . ".</li>\n";
					} else {
						if ($this->setup->app_registered($appname)) {
							$this->setup->update_app($appname);
						} else {
							$this->setup->register_app($appname);
							$header .=  '<li>' . lang('%1 registered', lang($appname)) . ".</li>\n";

							// Default values has be processed - even for apps without tables - after register for locations::add to work
							$terror = $this->process->default_records($terror, $DEBUG);
							$header .=  '<li>' . lang('%1 default values processed', lang($appname)) . ".</li>\n";
						}
						if (
							isset($setup_info[$appname]['hooks'])
							&& is_array($setup_info[$appname]['hooks'])
						) {
							$this->setup->register_hooks($appname);
							$header .=  '<li>' . lang('%1 hooks registered', lang($appname)) . ".</li>\n";
						}
					}
					$force_en = False;
					if ($appname == 'phpgwapi') {
						$force_en = true;
					}
					$terror = $this->process->add_langs($terror, $DEBUG, $force_en);
					$header .=  '<li>' . lang('%1 translations added', lang($appname)) . ".</li>\n</ul>\n";
					// Add credentials to admins
					$this->process->add_credential($appname);
				}
				$this->process->oProc->m_odb->transaction_commit();
			}

			if (!empty($upgrade) && is_array($upgrade)) {
				foreach ($upgrade as $appname => $key) {
					$header .=  '<h3>' . lang('Processing: %1', lang($appname)) . "</h3>\n<ul>";
					$terror = array();
					$terror[] = $setup_info[$appname];

					$this->process->upgrade($terror, $DEBUG);
					if (isset($setup_info[$appname]['tables'])) {
						$header .=  '<li>' . lang('%1 tables upgraded', lang($appname)) . ".</li>";
						// The process_upgrade() function also handles registration
					} else {
						$header .=  '<li>' . lang('%1 upgraded', lang($appname)) . ".</li>";
					}

					// Sigurd sep 2010: very slow - run 'Manage Languages' from setup instead. 
					//	$terror = $this->process->upgrade_langs($terror,$DEBUG);
					//	echo '<li>' . lang('%1 translations upgraded', lang($appname)) . ".</li>\n</ul>\n";
					$header .=  "<li>To upgrade languages - run <b>'Manage Languages'</b> from setup</li>\n</ul>\n";
				}
			}

			$header .=  "<h3><a href=\"applications?debug={$DEBUG}\">" . lang('Done') . "</h3>\n";
			$footer = $setup_tpl->fp('out', 'footer');

			$response = new \Slim\Psr7\Response();
			$response->getBody()->write($header . $footer);
			return $response;
			exit;
		} else {
			$header .= $this->html->get_header(lang('Application Management'), False, 'config', $this->db->get_domain() . '(' . $db_config['db_type'] . ')');
		}

		$detail = \Sanitizer::get_var('detail', 'string', 'GET');
		$resolve = \Sanitizer::get_var('resolve', 'string', 'GET');
		if ($detail) {
			ksort($setup_info[$detail]);
			$name = lang($setup_info[$detail]['name']);
			$setup_tpl->set_var('description', "<h2>{$name}</h2>\n<ul>\n");
			$header .= $setup_tpl->fp('out', 'header');

			$i = 1;
			$details = '';
			foreach ($setup_info[$detail] as $key => $val) {
				switch ($key) {
						// ignore these ones
					case 'application':
					case 'app_group':
					case 'app_order':
					case 'enable':
					case 'name':
					case 'title':
					case '':
						continue 2; //switch is a looping structure in php - see php.net/continue - skwashd jan08

					case 'tables':
						$tblcnt = count((array)$setup_info[$detail][$key]);
						if (is_array($val)) {
							$table_names = $this->db->table_names();
							$tables = array();

							$key = '<a href="sqltoarray?appname=' . $detail . '&amp;submit=True">' . $key . '(' . $tblcnt . ')</a>';

							foreach ($val as &$_val) {
								if (!in_array($_val, $table_names)) {
									$_val .= " <b>(missing)</b>";
								}
							}
							$val = implode(',<br>', $val);
						}
						break;
					case 'hooks':
					case 'views':
						$table_names = $this->db->table_names(true);
						$tblcnt = count($setup_info[$detail][$key]);
						if (is_array($val)) {
							$key =  $key . '(' . $tblcnt . ')';
							foreach ($val as &$_val) {
								if ($key == 'views' && !in_array($_val, $table_names)) {
									$_val .= " <b>(missing)</b>";
								}
							}
							$val = implode(',<br>', $val);
						}
						break;

					case 'depends':
						$val = $this->parsedep($val);
						break;

					case 'hooks':
						if (is_array($val)) {
							$val = implode(', ', $val);
						}
					case 'author':
					case 'maintainer':
						if (is_array($val)) {
							$authors = $val;
							$_authors = array();
							foreach ($authors as $author) {
								$author_str = $author['name'];
								if (!empty($author['email'])) {
									$author_str .= " <{$author['email']}>";
								}
								$_authors[] = htmlentities($author_str);
							}
							$val = implode(', ', $_authors);
						}
					default:
						if (is_array($val)) {
							$val = implode(', ', $val);
						}
				}

				$i = $i % 2;
				$setup_tpl->set_var('name', $key);
				$setup_tpl->set_var('details', $val);
				$details .= $setup_tpl->fp('out', 'detail');
				++$i;
			}
			$setup_tpl->set_var('footer_text', "</ul>\n<a href=\"applications?debug={$DEBUG}\">" . lang('Go back') . '</a>');
			$footer = 	$setup_tpl->fp('out', 'footer');

			$response = new \Slim\Psr7\Response();
			$response->getBody()->write($header . $details . $footer);
			return $response;

			exit;
		} else if ($resolve) {
			$version  = \Sanitizer::get_var('version', 'string', 'GET');
			$notables = \Sanitizer::get_var('notables', 'string', 'GET');
			$setup_tpl->set_var('description', lang('Problem resolution') . ':');
			$header .= $setup_tpl->fp('out', 'header');

			if (\Sanitizer::get_var('post', 'string', 'GET')) {
				$header .=  '"' . $setup_info[$resolve]['name'] . '" ' . lang('may be broken') . ' ';
				$header .=  lang('because an application it depends upon was upgraded');
				$header .=  '<br />';
				$header .=  lang('to a version it does not know about') . '.';
				$header .=  '<br />';
				$header .=  lang('However, the application may still work') . '.';
			} else if (\Sanitizer::get_var('badinstall', 'string', 'GET')) {
				$header .=  '"' . $setup_info[$resolve]['name'] . '" ' . lang('is broken') . ' ';
				$header .=  lang('because of a failed upgrade or install') . '.';
				$header .=  '<br />';
				$header .=  lang('Some or all of its tables are missing') . '.';
				$header .=  '<br />';
				$header .=  lang('You should either uninstall and then reinstall it, or attempt manual repairs') . '.';
			} elseif (!$version) {
				if ($setup_info[$resolve]['enabled']) {
					$header .=  '"' . $setup_info[$resolve]['name'] . '" ' . lang('is broken') . ' ';
				} else {
					$header .=  '"' . $setup_info[$resolve]['name'] . '" ' . lang('is disabled') . ' ';
				}

				if (!$notables) {
					if ($setup_info[$resolve]['status'] == 'D') {
						$header .=  lang('because it depends upon') . ':<br />' . "\n";
						list($depapp, $depver) = $this->parsedep($setup_info[$resolve]['depends'], False);
						$depapp_count = count($depapp);
						for ($i = 0; $i < $depapp_count; ++$i) {
							$header .=  '<br />' . $depapp[$i] . ': ';
							$list = '';
							foreach ($depver[$i] as $x => $y) {
								$list .= $y . ', ';
							}
							$list = substr($list, 0, -2);
							$header .=  "$list\n";
						}
						$header .=  '<br /><br />' . lang('The table definition was correct, and the tables were installed') . '.';
					} else {
						$header .=  lang('because it was manually disabled') . '.';
					}
				} elseif ($setup_info[$resolve]['enable'] == 2) {
					$header .=  lang('because it is not a user application, or access is controlled via acl') . '.';
				} elseif ($setup_info[$resolve]['enable'] == 0) {
					$header .=  lang('because the enable flag for this app is set to 0, or is undefined') . '.';
				} else {
					$header .=  lang('because it requires manual table installation, <br />or the table definition was incorrect') . ".\n"
					. lang("Please check for sql scripts within the application's directory") . '.';
				}
				$header .=  '<br />' . lang('However, the application is otherwise installed') . '.';
			} else {
				$header .=  $setup_info[$resolve]['name'] . ' ' . lang('has a version mismatch') . ' ';
				$header .=  lang('because of a failed upgrade, or the database is newer than the installed version of this app') . '.';
				$header .=  '<br />';
				$header .=  lang('If the application has no defined tables, selecting upgrade should remedy the problem') . '.';
				$header .=  '<br />' . lang('However, the application is otherwise installed') . '.';
			}

			$header .=  '<br /><a href="applications?debug=' . $DEBUG . '">' . lang('Go back') . '</a>';
			$footer = $setup_tpl->fp('out', 'footer');
			//response
			$response = new \Slim\Psr7\Response();
			$response->getBody()->write($header . $footer);
			return $response;

			exit;
		} else if (\Sanitizer::get_var('globals', 'string', 'GET')) {
			$setup_tpl->set_var('description', '<a href="applications?debug=' . $DEBUG . '">' . lang('Go back') . '</a>');
			$header .= $setup_tpl->fp('out', 'header');


			$name = (isset($setup_info[$detail]['title']) ? $setup_info[$detail]['title'] : lang($setup_info[$detail]['name']));
			$setup_tpl->set_var('name', lang('application'));
			$setup_tpl->set_var('details', $name);
			$setup_tpl->set_var('bg_color', 'th');
			$detail = $setup_tpl->fp('out', 'detail');

			$setup_tpl->set_var('bg_color', 'row_on');
			$setup_tpl->set_var('details', lang('register_globals_' . $_GET['globals']));
			$detail .= $setup_tpl->fp('out', 'detail');
			$footer = $setup_tpl->pparse('out', 'footer');
			//response
			$response = new \Slim\Psr7\Response();
			$response->getBody()->write($header . $detail . $footer);
			return $response;

			exit;
		} else {
			$setup_tpl->set_var('description', lang('Select the desired action(s) from the available choices'));
			$header .= $setup_tpl->fp('out', 'header');

			$setup_tpl->set_var('appdata', lang('Application Data'));
			$setup_tpl->set_var('actions', lang('Actions'));
			$setup_tpl->set_var('action_url', '../applications');
			$setup_tpl->set_var('app_info', lang('Application Name'));
			$setup_tpl->set_var('app_status', lang('Application Status'));
			$setup_tpl->set_var('app_currentver', lang('Current Version'));
			$setup_tpl->set_var('app_version', lang('Available Version'));
			$setup_tpl->set_var('app_install', lang('Install'));
			$setup_tpl->set_var('app_remove', lang('Remove'));
			$setup_tpl->set_var('app_upgrade', lang('Upgrade'));
			$setup_tpl->set_var('app_resolve', lang('Resolve'));
			$setup_tpl->set_var('check', 'stock_form-checkbox.png');
			$setup_tpl->set_var('install_all', lang('Install All'));
			$setup_tpl->set_var('upgrade_all', lang('Upgrade All'));
			$setup_tpl->set_var('remove_all', lang('Remove All'));
			$setup_tpl->set_var('lang_debug', lang('enable debug messages'));
			$setup_tpl->set_var('debug', '<input type="checkbox" name="debug" value="True"' . ($DEBUG ? ' checked' : '') . '>');

			$app_header = $setup_tpl->fp('out', 'app_header');

			$i = 0;
			foreach ($setup_info as $key => $value) {
				if (isset($value['name']) && $value['name'] != 'phpgwapi' && $value['name'] != 'notifywindow') {
					++$i;
					$row = $i % 2 ? 'off' : 'on';
					$value['title'] = !isset($value['title']) || !strlen($value['title']) ? str_replace('*', '', lang($value['name'])) : $value['title'];
					$setup_tpl->set_var('apptitle', $value['title']);
					$setup_tpl->set_var('currentver', isset($value['currentver']) ? $value['currentver'] : '');
					$setup_tpl->set_var('version', $value['version']);
					$setup_tpl->set_var('bg_class',  "row_{$row}");
					$setup_tpl->set_var('row_remove', '');

					switch ($value['status']) {
						case 'C':
							$setup_tpl->set_var('row_remove', "row_remove_{$row}");
							$setup_tpl->set_var('remove', '<input type="checkbox" name="remove[' . $value['name'] . ']" />');
							$setup_tpl->set_var('upgrade', '&nbsp;');
							if (!$this->detection->check_app_tables($value['name'])) {
								// App installed and enabled, but some tables are missing
								$setup_tpl->set_var('instimg', 'stock_database.png');
								$setup_tpl->set_var('bg_class', "row_err_table_{$row}");
								$setup_tpl->set_var('instalt', lang('Not Completed'));
								$setup_tpl->set_var('resolution', '<a href="applications?resolve=' . $value['name'] . '&amp;badinstall=True">' . lang('Potential Problem') . '</a>');
								$status = lang('Requires reinstall or manual repair') . ' - ' . $value['status'];
							} else {
								$setup_tpl->set_var('instimg', 'stock_yes.png');
								$setup_tpl->set_var('instalt', lang('%1 status - %2', $value['title'], lang('Completed')));
								$setup_tpl->set_var('install', '&nbsp;');
								if ($value['enabled']) {
									$setup_tpl->set_var('resolution', '');
									$status = "[{$value['status']}] " . lang('OK');
								} else {
									$notables = '';
									if (
										isset($value['tables'][0])
										&& $value['tables'][0] != ''
									) {
										$notables = '&amp;notables=True';
									}
									$setup_tpl->set_var('bg_class', "row_err_gen_{$row}");
									$setup_tpl->set_var(
										'resolution',
										'<a href="applications?resolve=' . $value['name'] .  $notables . '">' . lang('Possible Reasons') . '</a>'
									);
									$status = "[{$value['status']}] " . lang('Disabled');
								}
							}
							break;
						case 'U':
							$setup_tpl->set_var('instimg', 'package-generic.png');
							$setup_tpl->set_var('instalt', lang('Not Completed'));
							if (!isset($value['currentver']) || !$value['currentver']) {
								$setup_tpl->set_var('bg_class', "row_install_{$row}");
								$status = "[{$value['status']}] " . lang('Please install');
								if (isset($value['tables']) && is_array($value['tables']) && $value['tables'] && $this->detection->check_app_tables($value['name'], True)) {
									// Some tables missing
									$setup_tpl->set_var('bg_class', "row_err_gen_{$row}");
									$setup_tpl->set_var('instimg', 'stock_database.png');
									$setup_tpl->set_var('row_remove', 'row_remove_' . ($i ? 'off' : 'on'));
									$setup_tpl->set_var('remove', '<input type="checkbox" name="remove[' . $value['name'] . ']" />');
									$setup_tpl->set_var('resolution', '<a href="applications?resolve=' . $value['name'] . '&amp;badinstall=True">' . lang('Potential Problem') . '</a>');
									$status = "[{$value['status']}] " . lang('Requires reinstall or manual repair');
								} else {
									$setup_tpl->set_var('remove', '&nbsp;');
									$setup_tpl->set_var('resolution', '');
									$status = "[{$value['status']}] " . lang('Available to install');
								}
								$setup_tpl->set_var('install', '<input type="checkbox" name="install[' . $value['name'] . ']" />');
								$setup_tpl->set_var('upgrade', '&nbsp;');
							} else {
								$setup_tpl->set_var('bg_class', "row_upgrade_{$row}");
								$setup_tpl->set_var('install', '&nbsp;');
								// TODO display some info about breakage if you mess with this app
								$setup_tpl->set_var('upgrade', '<input type="checkbox" name="upgrade[' . $value['name'] . ']">');
								$setup_tpl->set_var('row_remove', 'row_remove_' . ($i ? 'off' : 'on'));
								$setup_tpl->set_var('remove', '<input type="checkbox" name="remove[' . $value['name'] . ']">');
								$setup_tpl->set_var('resolution', '');
								$status = "[{$value['status']}] " . lang('Requires upgrade');
							}
							break;
						case 'V':
							$setup_tpl->set_var('instimg', 'package-generic.png');
							$setup_tpl->set_var('instalt', lang('Not Completed'));
							$setup_tpl->set_var('install', '&nbsp;');
							$setup_tpl->set_var('row_remove', 'row_remove_' . ($i ? 'off' : 'on'));
							$setup_tpl->set_var('remove', '<input type="checkbox" name="remove[' . $value['name'] . ']">');
							$setup_tpl->set_var('upgrade', '<input type="checkbox" name="upgrade[' . $value['name'] . ']">');
							$setup_tpl->set_var('resolution', '<a href="applications?resolve=' . $value['name'] . '&amp;version=True">' . lang('Possible Solutions') . '</a>');
							$status = "[{$value['status']}] " . lang('Version Mismatch');
							break;
						case 'D':
							$setup_tpl->set_var('bg_class', "row_err_gen_{$row}");
							$depstring = $this->parsedep($value['depends']);
							$setup_tpl->set_var('instimg', 'stock_no.png');
							$setup_tpl->set_var('instalt', lang('Dependency Failure'));
							$setup_tpl->set_var('install', '&nbsp;');
							$setup_tpl->set_var('remove', '&nbsp;');
							$setup_tpl->set_var('upgrade', '&nbsp;');
							$setup_tpl->set_var('resolution', '<a href="applications?resolve=' . $value['name'] . '">' . lang('Possible Solutions') . '</a>');
							$status = "[{$value['status']}] " . lang('Dependency Failure') . $depstring;
							break;
						case 'P':
							$setup_tpl->set_var('bg_class', "row_err_gen_{$row}");
							$depstring = $this->parsedep($value['depends']);
							$setup_tpl->set_var('instimg', 'stock_no.png');
							$setup_tpl->set_var('instalt', lang('Post-install Dependency Failure'));
							$setup_tpl->set_var('install', '&nbsp;');
							$setup_tpl->set_var('remove', '&nbsp;');
							$setup_tpl->set_var('upgrade', '&nbsp;');
							$setup_tpl->set_var('resolution', '<a href="applications?resolve=' . $value['name'] . '&post=True">' . lang('Possible Solutions') . '</a>');
							$status = "[{$value['status']}] " . lang('Post-install Dependency Failure') . $depstring;
							break;
						default:
							$setup_tpl->set_var('instimg', 'package-generic.png');
							$setup_tpl->set_var('instalt', lang('Not Completed'));
							$setup_tpl->set_var('install', '&nbsp;');
							$setup_tpl->set_var('remove', '&nbsp;');
							$setup_tpl->set_var('upgrade', '&nbsp;');
							$setup_tpl->set_var('resolution', '');
							$status = '';
							break;
					}
					$setup_tpl->set_var('appinfo', $status);
					$setup_tpl->set_var('appname', $value['name']);

					$apps = $setup_tpl->fp('out', 'apps');
				}
			}
		}

		$setup_tpl->set_var('submit', lang('Save'));
		$setup_tpl->set_var('cancel', lang('Cancel'));
		$footer = $setup_tpl->fp('out', 'app_footer');
		$footer .= $setup_tpl->fp('out', 'footer');
		$footer .= $this->html->get_footer();

		$response = new \Slim\Psr7\Response();
		$response->getBody()->write($header . $apps . $footer);


		return $response;

	}

    function index(Request $request, Response $response, $args)
    {
		$setup_data = Settings::getInstance()->get('setup');
		$serverSettings = Settings::getInstance()->get('server');


        $GLOBALS['DEBUG'] = isset($_REQUEST['DEBUG']) && $_REQUEST['DEBUG'];

    
        @set_time_limit(0);
    
        $tpl_root = $this->html->setup_tpl_dir('setup');
        $setup_tpl = new Template($tpl_root);
		$this->html->set_tpl($setup_tpl);
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
      
  		$setup_tpl->set_var('HeaderLoginWarning', lang('Warning: All your passwords (database, portido admin,...)<br /> will be shown in plain text after you log in for header administration.'));
        $setup_tpl->set_var('lang_cookies_must_be_enabled', lang('<b>NOTE:</b> You must have cookies enabled to use setup and header admin!'));

		// Check header and authentication
		$setup_data['stage']['header'] = $this->detection->check_header();

        if ($setup_data['stage']['header'] != '10')
        {
            Header('Location: ../manageheader');
            exit;
        }
        elseif (!$this->setup->auth('Config'))
        {
			Header('Location: setup');
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
				print_r($setup_info['phpgwapi']);
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
		Settings::getInstance()->set('setup', $setup_data);
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
                    'POST','../config',
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
                    'POST','../config',
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
							'POST','../ldap',
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
							'../accounts',
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
                         '../accounts',
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
//		print_r($setup_data['stage']);  
		$setup_data = Settings::getInstance()->get('setup');

		// begin DEBUG code
		//$setup_data['stage']['lang'] = 0;
		// end DEBUG code
   
        switch($setup_data['stage']['lang'])
        {
            case 1:
                $setup_tpl->set_var('lang_status_img',$incomplete);
                $setup_tpl->set_var('lang_status_alt','not completed');
                $btn_install_lang = $this->html->make_frm_btn_simple(
                    lang('You do not have any languages installed. Please install one now <br />'),
                    'POST','../lang',
                    'submit',lang('Install Language'),
                    '');
                $setup_tpl->set_var('lang_table_data',$btn_install_lang);
                break;
            case 10:
                $langs_list = '';
                //reset ($setup_data['installed_langs']);
                //while (list ($key, $value) = each ($setup_data['installed_langs']))
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
                    'POST','../lang',
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
                    '','setup/applications',
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
            
		$header = $this->html->get_header(
            $setup_data['header_msg'],
            False,
            'config',
			$this->db->get_domain() . '(' . $db_config['db_type'] . ')'
        );
		$main = $setup_tpl->fp('out', 'T_setup_main');

		$footer = $this->html->get_footer();

		$response = new \Slim\Psr7\Response();
		$response->getBody()->write($header . $main . $footer);

		return $response;
    }
}