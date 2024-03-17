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
use App\Services\Translation;
use App\Security\Acl;



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
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        // If there is no route, return 404
        if (empty($route)) {
            throw new NotFoundException($request, $response);
        }


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

        $ServerSettings =  new ServerSettings($this->db);

        $flags = [
            'currentapp' => $currentApp
        ];
        $ServerSettings->set('flags', $flags);


        $userSettings = new Settings($this->db, $account_id);

        $userSettings->set('account_id', $account_id);

        //TODO: check if the user has permission to access the route
        // If access is granted, proceed to the next middleware
        // Otherwise, return a response indicating that access is denied
        // If the user does not have permission to access the route, return 403
        //throw new HttpForbiddenException($request, "You do not have permission to access this route.");
       // $acl = new Acl($this->db, $account_id);


        

        // Add data to the request as an attribute
 
        // Continue with the next middleware
        return $handler->handle($request);
    }
}