<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Modules\Setup\Controllers\SetupController;


$app->get('/setup', function (Request $request, Response $response) use ($phpgw_domain) {

	$last_domain = \Sanitizer::get_var('last_domain', 'string', 'COOKIE', false);
	$domainOptions = '';
	foreach (array_keys($phpgw_domain) as $domain) {
		$selected = ($domain === $last_domain) ? 'selected' : '';
		$domainOptions .= "<option value=\"$domain\" $selected>$domain</option>";
	}

	$html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Setup</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        </head>
        <body>
            <div class="container">
                <h1>Setup</h1>
                <form method="POST" action="/setup">
                    <div class="mb-3">
                        <label for="logindomain">Domain:</label>
                        <select class="form-select" id="logindomain" name="logindomain">
                            ' . $domainOptions . '
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password:</label>
                        <input type="password" class="form-control" id="password" name="FormPW">
                    </div>
					 <input type="hidden" name="ConfigLogin" value="Login">
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


$app->post('/setup', SetupController::class . ':index');

$app->get('/setup/logout', SetupController::class . ':logout');

$app->get('/setup/applications', SetupController::class . ':Applications');
$app->post('/setup/applications', SetupController::class . ':Applications');
$app->get('/setup/sqltoarray', SetupController::class . ':sqltoarray');

