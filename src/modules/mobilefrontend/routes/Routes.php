<?php

use Slim\Routing\RouteCollectorProxy;
use App\modules\phpgwapi\controllers\StartPoint;
use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\mobilefrontend\helpers\HomeHelper;

use App\modules\mobilefrontend\helpers\LoginHelper;
use App\modules\mobilefrontend\helpers\LogoutHelper;


$settings = [
	'session_name' => [
		'mobilefrontend' => 'mobilefrontendsession',
		'bookingfrontend' => 'bookingfrontendsession',
		'eventplannerfrontend' => 'eventplannerfrontendsession',
		'activitycalendarfrontend' => 'activitycalendarfrontendsession'
	]
	// Add more settings as needed
];
$app->get('/mobilefrontend/', StartPoint::class . ':mobilefrontend')->add(new SessionsMiddleware($app->getContainer(), $settings));
$app->post('/mobilefrontend/', StartPoint::class . ':mobilefrontend')->add(new SessionsMiddleware($app->getContainer(), $settings));

$app->get('/mobilefrontend/home/', HomeHelper::class . ':processHome')->add(new SessionsMiddleware($app->getContainer()));


//$app->get('/mobilefrontend/login/', LoginHelper::class . ':organization')->add(new SessionsMiddleware($app->getContainer(), $settings));
//$app->get('/mobilefrontend/logout[/{params:.*}]', LogoutHelper::class . ':process')->add(new SessionsMiddleware($app->getContainer(), $settings));
