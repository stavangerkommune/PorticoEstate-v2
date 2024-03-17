<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\BookingFrontend\DataStore;

$app->group('/bookingfrontend', function (RouteCollectorProxy $group) {
    $group->get('/searchdataall[/{params:.*}]', DataStore::class . ':SearchDataAll');
});
