<?php

use Slim\Routing\RouteCollectorProxy;
use App\modules\bookingfrontend\controllers\DataStore;
use App\modules\phpgwapi\controllers\StartPoint;
use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\bookingfrontend\helpers\LangHelper;
use App\modules\bookingfrontend\helpers\LoginHelper;
use App\modules\bookingfrontend\helpers\LogoutHelper;


$app->get('/registration/', StartPoint::class . ':registration')->add(new SessionsMiddleware($app->getContainer()));
$app->post('/registration/', StartPoint::class . ':registration')->add(new SessionsMiddleware($app->getContainer()));
