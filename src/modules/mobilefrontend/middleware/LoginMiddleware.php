<?php

namespace App\modules\mobilefrontend\middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\modules\phpgwapi\services\Settings;

class LoginMiddleware implements MiddlewareInterface
{
	private $settings;
	public function __construct($container)
	{
		$this->settings = $container->get('settings');
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$custom_frontend = 'mobilefrontend';
		Settings::getInstance()->update(
			'flags',
			[
				'custom_frontend' => $custom_frontend,
				'session_name' => $this->settings['session_name'][$custom_frontend]
			]
		);

		return $handler->handle($request);
	}
}
