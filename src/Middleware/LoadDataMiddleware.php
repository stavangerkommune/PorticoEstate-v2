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


class LoadDataMiddleware implements MiddlewareInterface
{
    protected $container;
    private $db;

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

		$sql = "SELECT account_id FROM phpgw_accounts"
		 ." WHERE account_lid = :UserName";
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':UserName', $UserName);
		$stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $account_id = !empty($result['account_id']) ? $result['account_id'] : 0;

        $flags = [
            'currentapp' => $currentApp
        ];

		ServerSettings::getInstance()->set('flags', $flags);

		Settings::getInstance()->set('account_id', $account_id);

         // Add data to the request as an attribute
 
        // Continue with the next middleware
        return $handler->handle($request);
    }
}