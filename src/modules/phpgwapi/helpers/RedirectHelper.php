<?php

namespace App\modules\phpgwapi\helpers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\modules\phpgwapi\security\Sessions;


class RedirectHelper
{
	public function processRedirect(Request $request, Response $response)
	{
		if (!Sessions::getInstance()->verify())
		{
			//Echo "No access" to the user
			$response->getBody()->write('No access');
			return $response;
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
			$htmlContent = '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invalid Link</title>
    </head>
    <body>
        <h1>Invalid Link</h1>
        <p>The link you followed is invalid.</p>
        <a href="/">Return to Main Page</a>
    </body>
    </html>';

			$response->getBody()->write($htmlContent);
			return $response;
		}
	}
}
