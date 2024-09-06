<?php

use App\modules\phpsysinfo\helpers\RunHelper;
use App\modules\phpgwapi\security\AccessVerifier;
use App\modules\phpgwapi\middleware\SessionsMiddleware;


$app->get('/phpsysinfo[/{params:.*}]', RunHelper::class . ':process')
	->addMiddleware(new AccessVerifier($container))
	->addMiddleware(new SessionsMiddleware($container));
