<?php

namespace App\modules\phpsysinfo\helpers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\modules\phpgwapi\services\Settings;
use Slim\Psr7\Stream;


class RunHelper
{
	private $phpgwapi_common;

	public function __construct()
	{
		$flags = Settings::getInstance()->get('flags');
		$flags['currentapp']           = 'admin';

		Settings::getInstance()->set('flags', $flags);
		require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';
		define('PSI_APP_ROOT', SRC_ROOT_PATH . '/modules/phpsysinfo');

		$this->phpgwapi_common = new \phpgwapi_common();
	}

	public function process(Request $request, Response $response, array $args)
	{
		$this->phpgwapi_common->phpgw_header();
		echo parse_navbar();

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

		ob_start();
		$webpage = new \WebpageXSLT();
		$webpage->run();

		$output = ob_get_clean();
		//Read css file
		$css = file_get_contents(PSI_APP_ROOT . '/templates/phpsysinfo.css');

		//Add css to the output
		$output = str_replace(array('@import url("templates/phpsysinfo.css");', 'gfx/images'), array($css, 'phpsysinfo/gfx/images'), $output);

		echo '<iframe id="contentFrame" srcdoc="' . htmlspecialchars($output, ENT_QUOTES, 'UTF-8') . '" style="width:100%; height:500px;"></iframe>';
		echo 
		'<script>
			function resizeIframe()
			{
				var iframe = document.getElementById("contentFrame");
				iframe.style.height = iframe.contentWindow.document.body.scrollHeight + "px";
			}
			document.getElementById("contentFrame").onload = resizeIframe;
		</script>';
		$this->phpgwapi_common->phpgw_footer();
		$response = $response->withHeader('Content-Type', 'text/plain');
		return $response;
	}

	public function gfxImages(Request $request, Response $response, array $args)
	{
		$filename = $args['filename'];
		$filename = str_replace('..', '', $filename);
		$filename = str_replace('/', '', $filename);
		$filename = str_replace('\\', '', $filename);

		$filename = PSI_APP_ROOT . '/gfx/images/' . $filename;

		if (file_exists($filename))
		{
			$size = getimagesize($filename);
			$file = fopen($filename, 'rb');
			$stream = new Stream($file);


			$response = $response->withHeader('Content-Type', $size['mime']);
			$response = $response->withHeader('Content-Length', filesize($filename));
			$response = $response->withBody($stream);
			return $response;
		}
		else
		{
			$response = $response->withStatus(404);
			$response->getBody()->write('File not found');
			return $response;
		}
	}
}
