<?php
// clean up from previous install
use App\Database\Db;
use App\modules\phpgwapi\controllers\Locations;

$db = Db::getInstance();
$location_obj = new Locations();

$db->query("SELECT app_id FROM phpgw_applications WHERE app_name = 'manual'");
$db->next_record();
$app_id = $db->f('app_id');

$db->query("DELETE FROM phpgw_locations WHERE app_id = {$app_id} AND name != 'run'");

$location_obj->add('.documents', 'Documents', 'manual');
