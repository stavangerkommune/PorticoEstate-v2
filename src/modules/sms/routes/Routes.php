<?php

use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\phpgwapi\security\AccessVerifier;
use App\modules\sms\helpers\RedirectHelper;


$app->get('/sms[/{params:.*}]', RedirectHelper::class . ':process')
->addMiddleware(new AccessVerifier($container))
->addMiddleware(new SessionsMiddleware($container));
