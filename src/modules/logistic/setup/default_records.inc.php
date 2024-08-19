<?php

use App\modules\phpgwapi\controllers\Locations;

$location_obj = new Locations();

//Create groups, users, add users to groups and set preferences
$location_obj->add('.', 'Topp', 'logistic');
$location_obj->add('.project', 'Prosjekt', 'logistic');
$location_obj->add('.activity', 'Aktivitet', 'logistic');
