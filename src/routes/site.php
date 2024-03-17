<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


$app->get('/', function (Request $request, Response $response) {
    $response_str = json_encode(['message' => 'Welcome to Portico API']);
    $response->getBody()->write($response_str);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/swagger[/{params:.*}]', function (Request $request, Response $response) {
    $json_file = __DIR__ . '/../../swagger.json';
    $json = file_get_contents($json_file);
    $response = $response->withHeader('Content-Type', 'application/json');
    $response->getBody()->write($json);
    return $response;
});