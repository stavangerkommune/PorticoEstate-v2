<?php
namespace App\Middleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpForbiddenException;
use App\Security\Auth\Auth;
use App\Security\Sessions;
use PDO;
use App\Services\Settings;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\NotFoundException;
use App\Services\DatabaseObject;
Use App\Services\Preferences;
use App\Services\Cache;
use App\Controllers\Api\Accounts\phpgwapi_account;
use App\Controllers\Api\Accounts\Accounts;
use App\Services\Crypto;
use App\Services\Log;



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
		$this->db = DatabaseObject::getInstance()->get('db');
		
		$this->serverSetting = Settings::getInstance()->get('server');

//		\App\Helpers\DebugArray::debug($this->serverSetting);

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

        $UserName = $request->getHeaderLine('X-API-User');
		$Password = $request->getHeaderLine('X-API-Key');
		$this->_account_lid = $UserName;
		$this->_account_domain = 'default';

		$sql = "SELECT account_id FROM phpgw_accounts"
		 ." WHERE account_lid = :UserName";
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':UserName', $UserName);
		$stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $account_id = !empty($result['account_id']) ? $result['account_id'] : 0;


		$this->read_initial_settings( $currentApp);


		$sessions = new Sessions();
//		die();
		$sessionid = $sessions->create($UserName, $Password);

		// If there is no route, return 404
		if (empty($sessionid)) {
	//		return $this->sendErrorResponse(['msg' => 'A valid session could not be created'], 401);
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