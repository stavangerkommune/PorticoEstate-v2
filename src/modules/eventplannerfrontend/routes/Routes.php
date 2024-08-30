<?php

use App\modules\phpgwapi\controllers\StartPoint;
use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\eventplannerfrontend\helpers\LoginHelper;
use App\modules\eventplannerfrontend\helpers\LogoutHelper;
use App\modules\eventplannerfrontend\helpers\HomeHelper;
use App\modules\eventplannerfrontend\helpers\RedirectHelper;
use App\modules\phpgwapi\security\AccessVerifier;


$app->get('/eventplannerfrontend', RedirectHelper::class . ':process')
	->addMiddleware(new AccessVerifier($container))
	->addMiddleware(new SessionsMiddleware($container));

$app->get('/eventplannerfrontend/', StartPoint::class . ':eventplannerfrontend')->add(new SessionsMiddleware($app->getContainer()));
$app->post('/eventplannerfrontend/', StartPoint::class . ':eventplannerfrontend')->add(new SessionsMiddleware($app->getContainer()));
$app->get('/eventplannerfrontend/login/', LoginHelper::class . ':post_login')->add(new SessionsMiddleware($app->getContainer()));
$app->get('/eventplannerfrontend/logout[/{params:.*}]', LogoutHelper::class . ':process')->add(new SessionsMiddleware($app->getContainer()));
$app->get('/eventplannerfrontend/home[/{params:.*}]', HomeHelper::class . ':processHome')->add(new SessionsMiddleware($app->getContainer()));

