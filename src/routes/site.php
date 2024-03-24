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

$app->get('/login[/{params:.*}]', function (Request $request, Response $response) {
    $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        </head>
        <body>
            <div class="container">
                <form method="POST" action="/login">
                <div class="mb-3">
                    <label for="login" class="form-label">Login:</label>
                        <input type="text" class="form-control" id="login" name="login">
                    </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password:</label>
                        <input type="password" class="form-control" id="password" name="passwd">
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </body>
        </html>
    ';

    $response = $response->withHeader('Content-Type', 'text/html');
    $response->getBody()->write($html);
    return $response;
});

$app->post('/login', function (Request $request, Response $response) {
    // Get the session ID
    $session_id = session_id();

    // Prepare the response
    $json = json_encode(['session_id' => $session_id]);
    $response = $response->withHeader('Content-Type', 'application/json');
    $response->getBody()->write($json);
    return $response;
})->addMiddleware(new App\Middleware\SessionsMiddleware($container));