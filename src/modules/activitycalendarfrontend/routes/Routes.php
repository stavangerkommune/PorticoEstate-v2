<?php

use App\modules\phpgwapi\controllers\StartPoint;
use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\activitycalendarfrontend\helpers\RedirectHelper;
use App\modules\phpgwapi\security\AccessVerifier;

$settings = [
	'session_name' => [
		'bookingfrontend' => 'bookingfrontendsession',
		'eventplannerfrontend' => 'eventplannerfrontendsession',
		'activitycalendarfrontend' => 'activitycalendarfrontendsession'
	]
	// Add more settings as needed
];
$app->get('/activitycalendarfrontend', RedirectHelper::class . ':process')
	->addMiddleware(new AccessVerifier($container))
	->addMiddleware(new SessionsMiddleware($container));

$app->get('/activitycalendarfrontend/', StartPoint::class . ':activitycalendarfrontend')->add(new SessionsMiddleware($app->getContainer(), $settings));
$app->post('/activitycalendarfrontend/', StartPoint::class . ':activitycalendarfrontend')->add(new SessionsMiddleware($app->getContainer(), $settings));

