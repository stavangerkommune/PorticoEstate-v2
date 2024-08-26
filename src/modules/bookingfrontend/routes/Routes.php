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

$settings = [
	'session_name' => [
		'bookingfrontend' => 'bookingfrontendsession',
		'eventplannerfrontend' => 'eventplannerfrontendsession',
		'activitycalendarfrontend' => 'activitycalendarfrontendsession'
	]
	// Add more settings as needed
];
$app->get('/bookingfrontend/', StartPoint::class . ':bookingfrontend')->add(new SessionsMiddleware($app->getContainer(), $settings));
$app->post('/bookingfrontend/', StartPoint::class . ':bookingfrontend')->add(new SessionsMiddleware($app->getContainer(), $settings));

$app->get('/bookingfrontend/lang', LangHelper::class . ':process')->addMiddleware(new SessionsMiddleware($container));
$app->get('/bookingfrontend/login/', LoginHelper::class . ':organization')->add(new SessionsMiddleware($app->getContainer(), $settings));
$app->get('/bookingfrontend/logout[/{params:.*}]', LogoutHelper::class . ':process')->add(new SessionsMiddleware($app->getContainer(), $settings));
