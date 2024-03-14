<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as Response;
use Psr\Container\ContainerInterface;
use PDO;
use App\Helpers\Auth;

class ApiKeyVerifier
{
	protected $db;

	public function __construct(ContainerInterface $container)
	{
		$this->db = $container->get('db');
	}

	public function __invoke(Request $request, RequestHandler $handler): Response
	{
		

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
		$newResponse = $response->withStatus(401);
		return $newResponse;
	}
}