<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return [
	'/' => [
		'get' => function (Request $request, Response $response) {
			$response_str = json_encode(['message' => 'Welcome to Portico API']);
			$response->getBody()->write($response_str);
			return $response->withHeader('Content-Type', 'application/json');
		}
	],
	'/swagger' => [
		'get' => function (Request $request, Response $response) {
		//	$swaggerUi = \Swagger\UI::scan('path/to/swagger.yaml');
			$swaggerUi = \OpenApi\Generator::scan([__DIR__ . '/../../swagger.yaml']);
	//		$response = $response->withHeader('Content-Type', 'text/html');
	//		$response->getBody()->write($swaggerUi);
			return $response;
		}
	],
];
