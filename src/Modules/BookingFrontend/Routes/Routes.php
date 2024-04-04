<?php

use Slim\Routing\RouteCollectorProxy;
use App\Modules\BookingFrontend\Controllers\DataStore;

$app->group('/bookingfrontend', function (RouteCollectorProxy $group) {
    $group->get('/searchdataall[/{params:.*}]', DataStore::class . ':SearchDataAll');
});
