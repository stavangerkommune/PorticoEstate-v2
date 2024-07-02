<?php

namespace App\modules\phpgwapi\controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\modules\phpgwapi\services\Log;
use App\modules\phpgwapi\services\Settings;
use Sanitizer;

class StartPoint
{
	private $log;
	private $invalid_data = false;
	private $api_requested = false;
	private $app;
	private $class;
	private $method;


	public function __construct()
	{
		$this->init();
		$this->log = new Log();
	}

	public function init()
	{
		require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';

		$this->loadLanguage();
		$this->loadSession();
		$this->loadUser();
		$this->loadHooks();
		$this->loadApp();
	}

	public function loadConfig()
	{

		define('PHPGW_TEMPLATE_DIR', ExecMethod('phpgwapi.phpgw.common.get_tpl_dir', 'phpgwapi'));
		define('PHPGW_IMAGES_DIR', ExecMethod('phpgwapi.phpgw.common.get_image_path', 'phpgwapi'));
		define('PHPGW_IMAGES_FILEDIR', ExecMethod('phpgwapi.phpgw.common.get_image_dir', 'phpgwapi'));
		define('PHPGW_APP_ROOT', ExecMethod('phpgwapi.phpgw.common.get_app_dir'));
		define('PHPGW_APP_INC', ExecMethod('phpgwapi.phpgw.common.get_inc_dir'));
		define('PHPGW_APP_TPL', ExecMethod('phpgwapi.phpgw.common.get_tpl_dir'));
		define('PHPGW_IMAGES', ExecMethod('phpgwapi.phpgw.common.get_image_path'));
		define('PHPGW_APP_IMAGES_DIR', ExecMethod('phpgwapi.phpgw.common.get_image_dir'));


		/************************************************************************\
		 * Load the menuaction                                                    *
		\*********************************************************************** */
		if (Sanitizer::get_var('menuaction', 'bool'))
		{
			Settings::getInstance()->set('menuaction', Sanitizer::get_var('menuaction', 'string'));
		}
	}

	public function loadLanguage()
	{
	}

	public function loadSession()
	{
	}

	public function loadUser()
	{
	}

	public function loadHooks()
	{
	}

	public function loadApp()
	{
	}

	public function run(Request $request, Response $response)
	{
		$this->loadConfig();
		$this->validate_object_method();
		$phpgwapi_common = new \phpgwapi_common();


		$this->api_requested = false;
		if ($this->app == 'phpgwapi')
		{
			$this->app = 'home';
			$this->api_requested = true;
		}


		if ($this->app == 'home' && !$this->api_requested)
		{
			\phpgw::redirect_link('/home/');
		}

		if ($this->api_requested)
		{
			$this->app = 'phpgwapi';
		}

		$Object = CreateObject("{$this->app}.{$this->class}");

		if (
			!$this->invalid_data
			&& is_object($Object)
			&& isset($Object->public_functions)
			&& is_array($Object->public_functions)
			&& isset($Object->public_functions[$this->method])
			&& $Object->public_functions[$this->method]
		)

		{
			if (
				Sanitizer::get_var('X-Requested-With', 'string', 'SERVER') == 'XMLHttpRequest'
				// deprecated
				|| Sanitizer::get_var('phpgw_return_as', 'string', 'GET') == 'json'
			)
			{
				$return_data = $Object->{$this->method}();
				$response_str = json_encode($return_data);
				$response->getBody()->write($response_str);
				return $response->withHeader('Content-Type', 'application/json');

				//                $flags['nofooter'] = true;
				//               Settings::getInstance()->set('flags', $flags);    
				//           $phpgwapi_common->phpgw_exit();
			}
			else
			{
				if (Sanitizer::get_var('phpgw_return_as', 'string', 'GET') == 'noframes')
				{
					$flags = Settings::getInstance()->get('flags');
					$flags['noframework'] = true;
					$flags['headonly'] = true;
					Settings::getInstance()->set('flags', $flags);
				}
				$Object->{$this->method}();

				if (!empty(Settings::getInstance()->get('flags')['xslt_app']))
				{
					$return_data =  \phpgwapi_xslttemplates::getInstance()->parse();
				}
				else
				{
					$return_data = '';
				}

				register_shutdown_function(array($phpgwapi_common, 'phpgw_final'));

				$response->getBody()->write($return_data);
				return $response->withHeader('Content-Type', 'text/html');
			}
			unset($this->app);
			unset($this->class);
			unset($this->method);
			unset($this->invalid_data);
			unset($this->api_requested);
		}
		else
		{
			//FIXME make this handle invalid data better
			if (!$this->app || !$this->class || !$this->method)
			{
				$this->log->message(array(
					'text' => 'W-BadmenuactionVariable, menuaction missing or corrupt: %1',
					'p1'   => Sanitizer::get_var('menuaction', 'string'),
					'line' => __LINE__,
					'file' => __FILE__
				));
			}

			if ((!isset($Object->public_functions)
					|| !is_array($Object->public_functions)
					|| !isset($Object->public_functions[$this->method])
					|| !$Object->public_functions[$this->method])
				&& $this->method
			)
			{
				$this->log->message(array(
					'text' => 'W-BadmenuactionVariable, attempted to access private method: %1 from %2',
					'p1'   => $this->method,
					'p2' => Sanitizer::get_ip_address(),
					'line' => __LINE__,
					'file' => __FILE__
				));
			}
			$this->log->commit();

			// phpgw::redirect_link('/home/');
		}

		//   $phpgwapi_common->phpgw_footer();

		register_shutdown_function(array($phpgwapi_common, 'phpgw_final'));

		$response_str = json_encode(['message' => 'Welcome to Portico API']);
		$response->getBody()->write($response_str);
		return $response->withHeader('Content-Type', 'application/json');
	}

	private function validate_object_method()
	{
		$this->invalid_data = false;
		if (isset($_GET['menuaction']) || isset($_POST['menuaction']))
		{
			if (isset($_GET['menuaction']))
			{
				list($this->app, $this->class, $this->method) = explode('.', $_GET['menuaction']);
			}
			else
			{
				list($this->app, $this->class, $this->method) = explode('.', $_POST['menuaction']);
			}
			if (!$this->app || !$this->class || !$this->method)
			{
				$this->invalid_data = true;
			}
		}
		else
		{

			$this->app = 'home';
			$this->invalid_data = true;
		}
	}

	public function bookingfrontend(Request $request, Response $response)
	{
	//	_debug_array($response);

		// Make sure we're always logged in
		$sessions = \App\modules\phpgwapi\security\Sessions::getInstance();

		$phpgwapi_common = new \phpgwapi_common();

		$redirect_input = Sanitizer::get_var('redirect', 'raw', 'COOKIE');
		$redirect = $redirect_input ? json_decode(Sanitizer::get_var('redirect', 'raw', 'COOKIE'), true) : null;

		if (is_array($redirect) && count($redirect))
		{
			$redirect_data = array();
			foreach ($redirect as $key => $value)
			{
				$redirect_data[$key] = Sanitizer::clean_value($value);
			}

			$redirect_data['second_redirect'] = true;

			$sessid = Sanitizer::get_var('sessionid', 'string', 'GET');
			if ($sessid)
			{
				$redirect_data['sessionid']	 = $sessid;
				$redirect_data['kp3']		 = Sanitizer::get_var('kp3', 'string', 'GET');
			}

			$sessions->phpgw_setcookie('redirect', false, 0);
			\phpgw::redirect_link('/bookingfrontend/index.php', $redirect_data);
			unset($redirect);
			unset($redirect_data);
			unset($sessid);
		}

		$flags = Settings::getInstance()->get('flags');
		$flags['currentapp'] = 'bookingfrontend';
		Settings::getInstance()->set('flags', $flags);

		$selected_lang = Sanitizer::get_var('selected_lang', 'string', 'COOKIE');

		if (Sanitizer::get_var('lang', 'bool', 'GET'))
		{
			$selected_lang = Sanitizer::get_var('lang', 'string', 'GET');
			$sessions->phpgw_setcookie('selected_lang', $selected_lang, (time() + (60 * 60 * 24 * 14)));
		}

		$userSettings = Settings::getInstance()->get('user');
		$userlang = $selected_lang ? $selected_lang : $userSettings['preferences']['common']['lang'];

		\App\modules\phpgwapi\services\Translation::getInstance()->set_userlang($userlang, true);

		$template_set = Sanitizer::get_var('template_set', 'string', 'COOKIE');

		/**
		 *  converted menuactions
		 */
/* 		$availableMenuActions = (object) [
			'bookingfrontend.uiapplication.add' => true,
			'bookingfrontend.uisearch.index' => true,
			'bookingfrontend.uiapplication.add_contact' => true,
			'bookingfrontend.uiresource.show' => true,
			'bookingfrontend.uibuilding.show' => true,
			'bookingfrontend.uiorganization.show' => true,
		];
 */
		/**
		 * we want the "bookingfrontend" for now
		 */
		switch ($template_set)
		{
			case 'bookingfrontend':
			case 'bookingfrontend_2':
				$userSettings['preferences']['common']['template_set'] = $template_set;
				break;
			default: // respect the global setting
				break;
		}

		Settings::getInstance()->set('user', $userSettings);

		$this->validate_object_method();
		$this->loadConfig();

		if (!$this->app || !$this->class || !$this->method)
		{
			$this->app = 'bookingfrontend';
			$this->class = 'uisearch';
			$this->method = 'index';
			$this->invalid_data = false;
		}

//		$phpgwapi_common->get_tpl_dir($this->app);

		if ($this->app != 'bookingfrontend')
		{
			$this->invalid_data = true;
			$phpgwapi_common->phpgw_header(true);
			$this->log->write(array(
				'text'	 => 'W-Permissions, Attempted to access %1 from %2',
				'p1'	 => $this->app,
				'p2'	 => Sanitizer::get_ip_address()
			));

			$lang_denied = lang('Access not permitted');
			echo <<<HTML
				<div class="error">$lang_denied</div>

HTML;
			$phpgwapi_common->phpgw_exit(True);
		}



		$Object = CreateObject("{$this->app}.{$this->class}");

		if (
			!$this->invalid_data
			&& is_object($Object)
			&& isset($Object->public_functions)
			&& is_array($Object->public_functions)
			&& isset($Object->public_functions[$this->method])
			&& $Object->public_functions[$this->method]
		)

		{
			if (
				Sanitizer::get_var('X-Requested-With', 'string', 'SERVER') == 'XMLHttpRequest'
				// deprecated
				|| Sanitizer::get_var('phpgw_return_as', 'string', 'GET') == 'json'
			)
			{
				$return_data = $Object->{$this->method}();
				$response_str = json_encode($return_data);
				$response->getBody()->write($response_str);
				return $response->withHeader('Content-Type', 'application/json');

				//                $flags['nofooter'] = true;
				//               Settings::getInstance()->set('flags', $flags);    
				//           $phpgwapi_common->phpgw_exit();
			}
			else
			{
				if (Sanitizer::get_var('phpgw_return_as', 'string', 'GET') == 'noframes')
				{
					$flags['noframework'] = true;
					$flags['headonly'] = true;
					Settings::getInstance()->set('flags', $flags);
				}
				$Object->{$this->method}();

				if (!empty(Settings::getInstance()->get('flags')['xslt_app']))
				{
					$return_data =  \phpgwapi_xslttemplates::getInstance()->parse();
				}
				else
				{
					$return_data = '';
				}

				register_shutdown_function(array($phpgwapi_common, 'phpgw_final'));

				$response->getBody()->write($return_data);
				return $response->withHeader('Content-Type', 'text/html');
			}
			unset($this->app);
			unset($this->class);
			unset($this->method);
			unset($this->invalid_data);
			unset($this->api_requested);
		}
		else
		{
			//FIXME make this handle invalid data better
			if (!$this->app || !$this->class || !$this->method)
			{
				$this->log->message(array(
					'text' => 'W-BadmenuactionVariable, menuaction missing or corrupt: %1',
					'p1'   => Sanitizer::get_var('menuaction', 'string'),
					'line' => __LINE__,
					'file' => __FILE__
				));
			}

			if ((!isset($Object->public_functions)
					|| !is_array($Object->public_functions)
					|| !isset($Object->public_functions[$this->method])
					|| !$Object->public_functions[$this->method])
				&& $this->method
			)
			{
				$this->log->message(array(
					'text' => 'W-BadmenuactionVariable, attempted to access private method: %1 from %2',
					'p1'   => $this->method,
					'p2' => Sanitizer::get_ip_address(),
					'line' => __LINE__,
					'file' => __FILE__
				));
			}
			$this->log->commit();

			// phpgw::redirect_link('/home/');
		}
		//   $phpgwapi_common->phpgw_footer();

		register_shutdown_function(array($phpgwapi_common, 'phpgw_final'));

		$response_str = json_encode(['message' => 'Welcome to Portico API']);
		$response->getBody()->write($response_str);
		return $response->withHeader('Content-Type', 'application/json');
	}
}
