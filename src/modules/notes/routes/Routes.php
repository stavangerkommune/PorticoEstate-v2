<?php

use App\modules\notes\helpers\RedirectHelper;
use App\modules\phpgwapi\security\AccessVerifier;
use App\modules\phpgwapi\middleware\SessionsMiddleware;


$app->get('/notes[/{params:.*}]', RedirectHelper::class . ':process')
	->addMiddleware(new AccessVerifier($container))
	->addMiddleware(new SessionsMiddleware($container));
