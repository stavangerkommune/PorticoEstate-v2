<?php

namespace App\modules\phpgwapi\helpers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\modules\phpgwapi\security\Sessions;
use App\modules\phpgwapi\services\Settings;
use HTML5;

class RedirectHelper
{
	public function processRedirect(Request $request, Response $response)
	{
		if (!Sessions::getInstance()->verify())
		{
			//Echo "No access" to the user
			//		$response->getBody()->write('No access');
			//		return $response;
		}
		if (isset($_GET['go']))
		{
			$go = htmlspecialchars_decode(urldecode($_GET['go']));

			return $response
				->withHeader('Location', $go)
				->withStatus(302);
		}
		else
		{
			$serverSettings = Settings::getInstance()->get('server');

			$htmlContent = <<<HTML
			<!DOCTYPE html>
    <html lang="{$serverSettings['default_lang']}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$serverSettings['system_name']} / Invalid Link</title>
    </head>
    <body>
        <h1>Invalid Link</h1>
        <p>The link you followed is invalid.</p>
        <a href="home/">Return to Main Page</a>
    </body>
    </html>
HTML;

			$response->getBody()->write($htmlContent);
			return $response
				->withHeader('Content-Type', 'text/html');
		}
	}
}
