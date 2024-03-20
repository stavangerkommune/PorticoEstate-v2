<?php
namespace App\Middleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Response as Psr7Response;
use Slim\Routing\RouteContext;
//use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use App\Helpers\Auth;
use PDO;
use App\Services\Settings;
use App\Services\ServerSettings;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\NotFoundException;
use App\Services\DatabaseObject;
Use App\Services\Preferences;
use App\Services\Cache;


class LoadDataMiddleware implements MiddlewareInterface
{
    protected $container;
    private $db;
	private $_account_lid;
	private $_account_domain;


    public function __construct($container)
    {
        $this->container = $container;
        $this->db = $container->get('db');
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
	//	print_r(__CLASS__);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        // If there is no route, return 404
        if (empty($route)) {
            throw new NotFoundException($request, $response);
        }

		DatabaseObject::getInstance($this->db);
        //get the route path
        $routePath = $route->getPattern();
        $routePath_arr = explode('/', $routePath);
        $currentApp = $routePath_arr[1];       

        $UserName = $request->getHeaderLine('X-API-User');
		$this->_account_lid = $UserName;
		$this->_account_domain = 'default';

		$sql = "SELECT account_id FROM phpgw_accounts"
		 ." WHERE account_lid = :UserName";
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':UserName', $UserName);
		$stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $account_id = !empty($result['account_id']) ? $result['account_id'] : 0;

		$this->read_repositories($account_id, $currentApp);

//		echo lang('date');
 
        // Continue with the next middleware
        return $handler->handle($request);
    }

	private function read_repositories($account_id = 0, $currentApp = '', $write_cache = false)
	{
		if ($write_cache) {
			$data = Cache::session_get('phpgwapi', 'phpgw_info');
		echo json_encode($data);die();
		}

		$flags = [
			'currentapp' => $currentApp
		];

		$data = [];
		ServerSettings::getInstance()->set('flags', $flags);
		$preferences = Preferences::getInstance($account_id)->get('preferences');
		Settings::getInstance()->setAccountId($account_id);

//		$acl = (new \App\Security\Acl())->set_account_id($account_id);
		$apps = (new \App\Controllers\Api\Applications($account_id))->read();

		Settings::getInstance()->set('server', ServerSettings::getInstance()->get('server'));
		Settings::getInstance()->set('apps', $apps);
		Settings::getInstance()->set('flags', $flags);
		
		$accounts = (new \App\Controllers\Api\Accounts\Accounts($account_id))->getObject();

		$data               	= $accounts->read()->toArray();
		$data['fullname']		= $accounts->read()->__toString();
		$data['preferences']	= $preferences;
		$data['apps']       	= $apps;

		//$phpgw_info['user' => [], 'server' => [], 'apps' =>[], 'flags' =>[]];
		$data['session_ip']  = $this->_get_user_ip();
		$data['session_lid'] = $this->_account_lid . '#' . $this->_account_domain;
		$data['account_id']  = $account_id;
		
		Settings::getInstance()->set('user', $data);
		header('Content-Type: application/json');
//		echo json_encode($data);
//		echo json_encode(Settings::getInstance()->get('flags'));
//		die();
		if ($write_cache) {
			Cache::session_set('phpgwapi', 'phpgw_info', $data);
		}
		
	}

	/**
	 * Get the ip address of current users
	 *
	 * @return string ip address
	 */
	protected function _get_user_ip()
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		//sanitize the ip address
		$ip = filter_var($ip, FILTER_VALIDATE_IP);
		return $ip;
	}



}