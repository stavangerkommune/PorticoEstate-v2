<?php

	namespace App\modules\phpgwapi\helpers;

	require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';

	use App\modules\phpgwapi\helpers\LoginUi;
	use Psr\Http\Message\ServerRequestInterface as Request;
	use Psr\Http\Message\ResponseInterface as Response;
	use App\modules\phpgwapi\security\Login;
	use App\modules\phpgwapi\services\Settings;
//use App\modules\phpgwapi\services\Hooks;
//use App\modules\phpgwapi\services\Cache;
	use App\modules\phpgwapi\services\Translation;
	use App\modules\phpgwapi\services\Preferences;
//use App\modules\phpgwapi\controllers\Applications;
	use App\helpers\Template;
	use App\modules\phpgwapi\security\Acl;
	use App\modules\phpgwapi\security\Sessions;
	use Sanitizer;
	use phpgw;

	class LoginHelper
	{

		private $serverSettings;
		private $userSettings;
		private $translations;
		//private $hooks;
		private $phpgwapi_common;
		private $apps;
		var $tmpl	 = null;
		var $msg_only = false;

		public function __construct( $msg_only = false )
		{
			$this->serverSettings = Settings::getInstance()->get('server');
			$this->userSettings	  = Settings::getInstance()->get('user');
			$this->translations	  = Translation::getInstance();

			$this->serverSettings['template_set'] = Settings::getInstance()->get('login_template_set');
			$this->serverSettings['template_dir'] = PHPGW_SERVER_ROOT
				. "/phpgwapi/templates/{$this->serverSettings['template_set']}";

			Settings::getInstance()->set('server', $this->serverSettings);

			$tmpl = new Template($this->serverSettings['template_dir']);

			// This is used for system downtime, to prevent new logins.
			if (
				isset($this->serverSettings['deny_all_logins']) && $this->serverSettings['deny_all_logins']
			)
			{
				$tmpl->set_file(
					array(
						'login_form' => 'login_denylogin.tpl'
					)
				);
				$tmpl->pfp('loginout', 'login_form');
				exit;
			}
			$this->tmpl		= $tmpl;
			$this->msg_only = $msg_only;
		}

		public function processLogin( Request $request, Response $response, array $args )
		{

			$LoginUi   = new LoginUi($this->msg_only);
			$variables = array();

			if (!Sanitizer::get_var('hide_lightbox', 'bool'))
			{
				$partial_url	   = '/login_ui';
				$phpgw_url_for_sso = '/phpgwapi/inc/sso/login_server.php';

				$variables['lang_login']  = lang('login');
				$variables['partial_url'] = $partial_url;
				//		$variables['lang_frontend']	= $frontend ? lang($frontend) : '';
				if (isset($this->serverSettings['half_remote_user']) && $this->serverSettings['half_remote_user'] == 'remoteuser')
				{
					$variables['lang_additional_url'] = lang('use sso login');
					$variables['additional_url']	  = phpgw::link('/' . $phpgw_url_for_sso);
				}
			}

			if ($this->serverSettings['auth_type'] == 'remoteuser')
			{
				$this->msg_only = true;
			}

			if (empty($_POST))
			{
				$LoginUi->phpgw_display_login($variables, Sanitizer::get_var('cd', 'int', 'GET', 0));
			}
			else
			{

				$Login = new Login();
				if (Sanitizer::get_var('create_account', 'bool'))
				{
					$Login->create_account();
				}
				else
				{

					$sessionid = $Login->login();
					if ($sessionid)
					{
						phpgw::redirect_link('/home/');
					}
					else
					{
						$LoginUi->phpgw_display_login($variables, $Login->get_cd());
					}
				}
			}

			$response = $response->withHeader('Content-Type', 'text/html');
			return $response;
		}
	}