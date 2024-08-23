<?php

namespace App\modules\eventplannerfrontend\helpers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\services\Hooks;


class HomeHelper
{
	private $hooks;
	private $phpgwapi_common;

	public function __construct()
	{
		Settings::getInstance()->update('flags', ['currentapp' => 'eventplannerfrontend']);

		$userSettings = Settings::getInstance()->get('user');
		$userSettings['preferences']['common']['template_set'] = 'frontend';
		Settings::getInstance()->set('user', $userSettings);

		require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';

		$this->hooks = new Hooks();
		$this->phpgwapi_common = new \phpgwapi_common();
	}

	public function processHome(Request $request, Response $response, array $args)
	{
		$this->hooks->single('home', 'eventplannerfrontend');
		$this->phpgwapi_common->phpgw_footer();
		$response = $response->withHeader('Content-Type', 'text/plain');
		return $response;
	}
}
