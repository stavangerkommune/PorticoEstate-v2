<?php

use Slim\Routing\RouteCollectorProxy;
use App\modules\bookingfrontend\controllers\DataStore;
use App\modules\bookingfrontend\controllers\BuildingController;
use App\modules\phpgwapi\controllers\StartPoint;
use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\bookingfrontend\helpers\LangHelper;
use App\modules\bookingfrontend\helpers\LoginHelper;
use App\modules\bookingfrontend\helpers\LogoutHelper;

$app->group('/bookingfrontend', function (RouteCollectorProxy $group) {
    $group->get('/searchdataall[/{params:.*}]', DataStore::class . ':SearchDataAll');
    $group->group('/buildings', function (RouteCollectorProxy $group) {
        $group->get('', BuildingController::class . ':index');
        $group->get('/{id}', BuildingController::class . ':show');
    });
});


$app->get('/bookingfrontend/', StartPoint::class . ':bookingfrontend')->add(new SessionsMiddleware($app->getContainer()));
$app->post('/bookingfrontend/', StartPoint::class . ':bookingfrontend')->add(new SessionsMiddleware($app->getContainer()));

$app->get('/bookingfrontend/lang[/{lang}]', LangHelper::class . ':process');
$app->get('/bookingfrontend/login/', LoginHelper::class . ':organization')->add(new SessionsMiddleware($app->getContainer()));
$app->get('/bookingfrontend/logout[/{params:.*}]', LogoutHelper::class . ':process')->add(new SessionsMiddleware($app->getContainer()));
$app->get('/bookingfrontend/client[/{params:.*}]', function ($request, $response) {
    $response = $response->withHeader('Location', '/bookingfrontend/client/');
    return $response;
});