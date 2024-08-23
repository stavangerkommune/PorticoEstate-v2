<?php

use App\modules\phpgwapi\controllers\StartPoint;
use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\eventplannerfrontend\helpers\LoginHelper;
use App\modules\eventplannerfrontend\helpers\LogoutHelper;
use App\modules\eventplannerfrontend\helpers\HomeHelper;

$settings = [
	'session_name' => [
		'bookingfrontend' => 'bookingfrontendsession',
		'eventplannerfrontend' => 'eventplannerfrontendsession',
		'activitycalendarfrontend' => 'activitycalendarfrontendsession'
	]
	// Add more settings as needed
];
$app->get('/eventplannerfrontend/', StartPoint::class . ':eventplannerfrontend')->add(new SessionsMiddleware($app->getContainer(), $settings));
$app->post('/eventplannerfrontend/', StartPoint::class . ':eventplannerfrontend')->add(new SessionsMiddleware($app->getContainer(), $settings));
$app->get('/eventplannerfrontend/login/', LoginHelper::class . ':post_login')->add(new SessionsMiddleware($app->getContainer(), $settings));
$app->get('/eventplannerfrontend/logout[/{params:.*}]', LogoutHelper::class . ':process')->add(new SessionsMiddleware($app->getContainer(), $settings));
$app->get('/eventplannerfrontend/home[/{params:.*}]', HomeHelper::class . ':processHome')->add(new SessionsMiddleware($app->getContainer()));
