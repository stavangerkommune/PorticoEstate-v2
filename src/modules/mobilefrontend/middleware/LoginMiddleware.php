<?php

namespace App\modules\mobilefrontend\middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\modules\phpgwapi\services\Settings;

class LoginMiddleware implements MiddlewareInterface
{
	public function __construct()
	{
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		Settings::getInstance()->update('flags', ['custom_frontend' => 'mobilefrontend']);
		return $handler->handle($request);
	}

}