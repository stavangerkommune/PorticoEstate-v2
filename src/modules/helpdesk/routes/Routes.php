<?php

use App\modules\helpdesk\helpers\RedirectHelper;
use App\modules\phpgwapi\security\AccessVerifier;
use App\modules\phpgwapi\middleware\SessionsMiddleware;


$app->get('/helpdesk[/{params:.*}]', RedirectHelper::class . ':process')
	->addMiddleware(new AccessVerifier($container))
	->addMiddleware(new SessionsMiddleware($container));
