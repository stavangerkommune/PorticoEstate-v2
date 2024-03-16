<?php
namespace App\Security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Container\ContainerInterface;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use Slim\Psr7\Response;
//ForbiddenException





class AccessVerifier  implements MiddlewareInterface
{
	protected $acl;

	public function __construct(ContainerInterface $container)
	{
		$this->acl = $container->get('acl');
	}

	//public function process

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Perform access verification here

        // If access is granted, proceed to the next middleware
        // Otherwise, return a response indicating that access is denied
		$routeContext = RouteContext::fromRequest($request);
		$route = $routeContext->getRoute();

		// If there is no route, return 404
		if (empty($route)) {
			throw new NotFoundException($request, $response);
		}

		$routeName = $route->getName();

		// Check if the user has permission to access the route
	//	if (!$this->acl->hasPermission($routeName))
	{
    //        throw new HttpForbiddenException($request, "You do not have permission to access this route.");

	//		return $this->sendErrorResponse(['msg' => 'You do not have permission to access this route.']);
		}

		return $handler->handle($request);

    }


	private function sendErrorResponse($error)
	{
		$response = new Response();
		$response->getBody()->write(json_encode($error));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
	}
}