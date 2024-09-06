<?php

namespace App\modules\phpsysinfo\helpers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\services\Hooks;
use App\modules\phpgwapi\services\Cache;
use App\modules\phpgwapi\services\Translation;
use App\modules\phpgwapi\services\Preferences;
use App\modules\phpgwapi\controllers\Applications;

class RunHelper
{
	private $serverSettings;
	private $userSettings;
	private $hooks;
	private $phpgwapi_common;
	private $apps;

	public function __construct()
	{
		$flags = Settings::getInstance()->get('flags');
		$flags['noheader']             = true;
		$flags['nonavbar']             = false;
		$flags['currentapp']           = 'admin';

		Settings::getInstance()->set('flags', $flags);
		require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';

		$this->serverSettings = Settings::getInstance()->get('server');
		$this->userSettings = Settings::getInstance()->get('user');
		$this->apps = Settings::getInstance()->get('apps');
		$this->hooks = new Hooks();
		$this->phpgwapi_common = new \phpgwapi_common();
	}

	public function process(Request $request, Response $response, array $args)
	{
		$this->phpgwapi_common->phpgw_header();
		echo parse_navbar();

		define('PSI_APP_ROOT', SRC_ROOT_PATH . '/modules/phpsysinfo');

		if (!extension_loaded("pcre"))
		{
			die("phpSysInfo requires the pcre extension to php in order to work properly.");
		}

		require_once PSI_APP_ROOT . '/includes/autoloader.inc.php';

		// Load configuration
		require_once PSI_APP_ROOT . '/read_config.php';

		if (!defined('PSI_CONFIG_FILE') || !defined('PSI_DEBUG'))
		{
			$tpl = new \Template("/templates/html/error_config.html");
			echo $tpl->fetch();
			die();
		}

		// redirect to page with and without javascript
		$display = strtolower(isset($_GET['disp']) ? $_GET['disp'] : PSI_DEFAULT_DISPLAY_MODE);
		$display = 'static';
		switch ($display)
		{
			case "static":
				$webpage = new \WebpageXSLT();
				$webpage->run();
				break;
			case "dynamic":
				$webpage = new \Webpage();
				$webpage->run();
				break;
			case "xml":
				$webpage = new \WebpageXML("complete");
				$webpage->run();
				break;
			case "json":
				$webpage = new \WebpageXML("complete");
				$json = $webpage->getJsonString();
				header('Cache-Control: no-cache, must-revalidate');
				header('Content-Type: application/json');
				echo $json;
				break;
			case "bootstrap":
				/*
    $tpl = new Template("/templates/html/index_bootstrap.html");
    echo $tpl->fetch();
*/
				$webpage = new \Webpage("bootstrap");
				$webpage->run();
				break;
			case "auto":
				$tpl = new \Template("/templates/html/index_all.html");
				echo $tpl->fetch();
				break;
			default:
				$defaultdisplay = strtolower(PSI_DEFAULT_DISPLAY_MODE);
				switch ($defaultdisplay)
				{
					case "static":
						$webpage = new \WebpageXSLT();
						$webpage->run();
						break;
					case "dynamic":
						$webpage = new \Webpage();
						$webpage->run();
						break;
					case "bootstrap":
						$webpage = new \Webpage("bootstrap");
						$webpage->run();
						break;
					default:
						$tpl = new \Template("/templates/html/index_all.html");
						echo $tpl->fetch();
				}
		}
		$this->phpgwapi_common->phpgw_footer();
		$response = $response->withHeader('Content-Type', 'text/plain');
		return $response;
	}
}
