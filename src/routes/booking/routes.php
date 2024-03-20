<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\Booking\UserController;
use App\Security\AccessVerifier;
use App\Security\ApiKeyVerifier;
use App\Middleware\LoadDataMiddleware;


$app->group('/booking/users', function (RouteCollectorProxy $group) {
	$group->get('', UserController::class . ':index');
	$group->post('', UserController::class . ':store');
	$group->get('/{id}', UserController::class . ':show');
	$group->put('/{id}', UserController::class . ':update');
	$group->delete('/{id}', UserController::class . ':destroy');
})
->addMiddleware(new AccessVerifier($container))
->addMiddleware(new LoadDataMiddleware($container))
->addMiddleware(new ApiKeyVerifier($container))
->addMiddleware((new Middlewares\PhpSession())->name('portico_php_session')); // Add PhpSession middleware to this route group
;