<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\BookingFrontend;

$app->group('/BookingFrontend', function (RouteCollectorProxy $group) {
    $group->get('/SearchDataAll', BookingFrontend::class . ':SearchDataAll');
});
