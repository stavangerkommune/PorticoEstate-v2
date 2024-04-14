<?php
namespace App\modules\phpgwapi\middleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use App\modules\phpgwapi\security\Auth\Auth;
use App\modules\phpgwapi\security\Sessions;
use PDO;
use App\modules\phpgwapi\services\Settings;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\NotFoundException;
Use App\modules\phpgwapi\services\Preferences;
use App\modules\phpgwapi\services\Cache;
use App\modules\phpgwapi\controllers\Accounts\phpgwapi_account;
use App\modules\phpgwapi\controllers\Accounts\Accounts;
use App\modules\phpgwapi\services\Crypto;
use App\modules\phpgwapi\services\Log;



class SessionsMiddleware implements MiddlewareInterface
{
    protected $container;
    private $db;
	private $_account_lid;
	private $_account_domain;
	private $_sessionid;
	private $_use_cookies;
	private $_cookie_domain;
	private $config;
	private $routePath;
    private $_session_flags;
    private $_data;
    private $_account_id;
    private $_key;
    private $_iv;
    private $_verified;
    private $cd_reason;
	private $reason;
	private $_login;
	private $_passwd;
	private $serverSetting;
	private $Auth;
	private $Crypto;
	private $Log;
		
	


    public function __construct($container)
    {
        $this->container = $container;
		$this->db = \App\Database\Db::getInstance();
		
		$this->serverSetting = Settings::getInstance()->get('server');

//		\App\helpers\DebugArray::debug($this->serverSetting);

    }



	public function process(Request $request, RequestHandler $handler): Response
    {
	//	print_r(__CLASS__);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();


		// If there is no route, return 404
		if (empty($route)) {
			return $this->sendErrorResponse(['msg' => 'route not found'], 404);
		}

        //get the route path
        $this->routePath = $route->getPattern();
        $routePath_arr = explode('/', $this->routePath);
        $currentApp = $routePath_arr[1];       
		$this->read_initial_settings( $currentApp);
		$sessions = Sessions::getInstance();
		if ($currentApp == 'login' && isset($_POST['login']) && isset($_POST['passwd'])){
			$login = $request->getParsedBody()['login'];
			$passwd = $request->getParsedBody()['passwd'];
			$sessionid = $sessions->create($login, $passwd);
			if (empty($sessionid)) {
				return $this->sendErrorResponse(['msg' => 'A valid session could not be created'], 401);
			}
			
			$response = new Response();
			$response->getBody()->write(json_encode(['session_id' => $sessionid]));
			return $response->withHeader('Content-Type', 'application/json');
		}
		else if(!$sessions->verify()){
			return $this->sendErrorResponse(['msg' => 'A valid session could not be found'], 401);
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

	private function read_initial_settings( $currentApp = '')
	{

		$flags = [
			'currentapp' => $currentApp
		];

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