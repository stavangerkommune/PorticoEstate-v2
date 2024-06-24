<?php

use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\phpgwapi\security\AccessVerifier;
use App\modules\bim\helpers\RedirectHelper;


$app->get('/bim[/{params:.*}]', RedirectHelper::class . ':process')
->addMiddleware(new AccessVerifier($container))
->addMiddleware(new SessionsMiddleware($container));
