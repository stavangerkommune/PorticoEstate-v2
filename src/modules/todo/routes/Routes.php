<?php

use App\modules\todo\helpers\RedirectHelper;
use App\modules\phpgwapi\security\AccessVerifier;
use App\modules\phpgwapi\middleware\SessionsMiddleware;


$app->get('/todo[/{params:.*}]', RedirectHelper::class . ':process')
	->addMiddleware(new AccessVerifier($container))
	->addMiddleware(new SessionsMiddleware($container));
