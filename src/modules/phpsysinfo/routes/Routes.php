<?php

use App\modules\phpsysinfo\helpers\RunHelper;
use App\modules\phpgwapi\security\AccessVerifier;
use App\modules\phpgwapi\middleware\SessionsMiddleware;


$app->get('/phpsysinfo', RunHelper::class . ':process')
	->addMiddleware(new AccessVerifier($container))
	->addMiddleware(new SessionsMiddleware($container));

//return  phpsysinfo/gfx/images/
$app->get('/phpsysinfo/gfx/images/{filename}', RunHelper::class . ':gfxImages');