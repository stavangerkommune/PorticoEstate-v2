<?php

use Slim\Routing\RouteCollectorProxy;
use App\modules\bookingfrontend\controllers\DataStore;

use App\modules\phpgwapi\controllers\StartPoint;
use App\modules\phpgwapi\middleware\SessionsMiddleware;


$app->group('/bookingfrontend', function (RouteCollectorProxy $group) {
    $group->get('/searchdataall[/{params:.*}]', DataStore::class . ':SearchDataAll');
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
