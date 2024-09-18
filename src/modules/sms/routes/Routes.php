<?php

use App\modules\phpgwapi\middleware\SessionsMiddleware;
use App\modules\phpgwapi\security\AccessVerifier;
use App\modules\sms\helpers\RedirectHelper;
use App\modules\sms\controllers\pswinController;

$app->get('/sms/inc/plugin/gateway/pswin/soap.php', pswinController::class . ':process');
$app->post('/sms/inc/plugin/gateway/pswin/soap.php', pswinController::class . ':process');

$app->get('/sms[/{params:.*}]', RedirectHelper::class . ':process')
->addMiddleware(new AccessVerifier($container))
->addMiddleware(new SessionsMiddleware($container));


