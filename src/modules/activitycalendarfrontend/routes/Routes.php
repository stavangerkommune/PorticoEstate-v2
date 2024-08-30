<?php

use App\modules\phpgwapi\controllers\StartPoint;
use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\activitycalendarfrontend\helpers\RedirectHelper;
use App\modules\phpgwapi\security\AccessVerifier;

$app->get('/activitycalendarfrontend', RedirectHelper::class . ':process')
	->addMiddleware(new AccessVerifier($container))
	->addMiddleware(new SessionsMiddleware($container));

$app->get('/activitycalendarfrontend/', StartPoint::class . ':activitycalendarfrontend')->add(new SessionsMiddleware($app->getContainer()));
$app->post('/activitycalendarfrontend/', StartPoint::class . ':activitycalendarfrontend')->add(new SessionsMiddleware($app->getContainer()));

