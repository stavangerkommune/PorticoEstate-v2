<?php

namespace App\modules\phpgwapi\middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use App\modules\phpgwapi\security\Sessions;
use App\modules\phpgwapi\services\Settings;
use Psr\Http\Server\MiddlewareInterface;

class SessionsMiddleware implements MiddlewareInterface
{
	protected $container;
	private $routePath;
	private $settings;

	public function __construct($container,  $settings = [])
	{
		$this->settings = $settings;
	}

	public function process(Request $request, RequestHandler $handler): Response
	{
		//	print_r(__CLASS__);

		$routeContext = RouteContext::fromRequest($request);
		$route = $routeContext->getRoute();


		// If there is no route, return 404
		if (empty($route))
		{
			return $this->sendErrorResponse(['msg' => 'route not found'], 404);
		}

		//get the route path

		if (isset($_GET['menuaction']) || isset($_POST['menuaction']))
		{
			if (isset($_GET['menuaction']))
			{
				list($currentApp, $class, $method) = explode('.', $_GET['menuaction']);
			}
			else
			{
				list($currentApp, $class, $method) = explode('.', $_POST['menuaction']);
			}
		}
		else
		{
			$this->routePath = $route->getPattern();
			$routePath_arr = explode('/', $this->routePath);
			$currentApp = $routePath_arr[1];
		}

		$this->read_initial_settings($currentApp);
		$sessions = Sessions::getInstance();
		if ($currentApp == 'login' && isset($_POST['login']) && isset($_POST['passwd']))
		{
			$login = $request->getParsedBody()['login'];
			$passwd = $request->getParsedBody()['passwd'];
			$sessionid = $sessions->create($login, $passwd);
			if (empty($sessionid))
			{
				return $this->sendErrorResponse(['msg' => 'A valid session could not be created'], 401);
			}

			$response = new Response();
			$response->getBody()->write(json_encode(['session_id' => $sessionid]));
			return $response->withHeader('Content-Type', 'application/json');
		}
		else if (!$sessions->verify())
		{
			if($currentApp == 'bookingfrontend')
			{
				return $handler->handle($request);
			}
			else
			{
				return $this->sendErrorResponse(['msg' => 'A valid session could not be found'], 401);
			}
		}



		// Continue with the next middleware
		return $handler->handle($request);
	}


	/**
	 * Read the initial settings
	 *
	 * @param string $currentApp
	 *
	 * @return void
	 */

	private function read_initial_settings($currentApp = '')
	{

		$flags = [
			'currentapp' => $currentApp
		];

		if (isset($this->settings['session_name'][$currentApp]))
		{
			$flags['session_name'] = $this->settings['session_name'][$currentApp];
		}

		Settings::getInstance()->set('flags', $flags);
	}

	/**
	 * Get the ip address of current users
	 *
	 * @return string ip address
	 */
	protected function _get_user_ip()
	{
		return \Sanitizer::get_var(
			'HTTP_X_FORWARDED_FOR',
			'ip',
			'SERVER',
			\Sanitizer::get_var('REMOTE_ADDR', 'ip', 'SERVER')
		);
	}


	/**
	 * Send an error response
	 *
	 * @param array $error
	 * @param int   $statusCode
	 *
	 * @return Response
	 */
	private function sendErrorResponse($error, $statusCode = 401): Response
	{
		$response = new Response();
		$response->getBody()->write(json_encode($error));
		return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
	}
}
