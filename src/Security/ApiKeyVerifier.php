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
use App\Helpers\Auth;
use PDO;
use Slim\Psr7\Response;


class ApiKeyVerifier  implements MiddlewareInterface
{
	protected $db;

	public function __construct(ContainerInterface $container)
	{
		$this->db = $container->get('db');
	}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
//		$response = $handler->handle($request);
//		return $response;
		$UserName = $request->getHeaderLine('X-API-User');
		$passwd = $request->getHeaderLine('X-API-Key');

		if (!$UserName || !$passwd) {
			return $this->sendErrorResponse(['msg' => 'Specify UserName and passwd for authentication']);
		}

		$sql = "SELECT account_pwd FROM phpgw_accounts"
		 ." WHERE account_status = 'A' AND account_lid = :UserName";
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':UserName', $UserName);
		$stmt->execute();

		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$result) {
			return $this->sendErrorResponse(['msg' => 'UserName does not exist']);
		}

		if (array_key_exists('account_pwd', $result)) {
			$hash = $result['account_pwd'];
		}
		else
		{
			return $this->sendErrorResponse(['msg' => 'UserName does not exist']);
		}

		if (!Auth::VerifyHash($passwd, $hash)) {
			return $this->sendErrorResponse(['msg' => 'Invalid passwd']);
		}

		$response = $handler->handle($request);
		return $response;
	}

	private function sendErrorResponse($error)
	{
		$response = new Response();
		$response->getBody()->write(json_encode($error));
		return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
	}
}