<?php
//_debug_array($values);
//_debug_array($values_attribute);
//_debug_array($action);
// this routine will only work with the exact configuration of Bergen Bolig og Byfornyelse - but can serve as an example

use App\Database\Db;
use App\modules\phpgwapi\controllers\Locations;

$db = Db::getInstance();
$location_obj = new Locations();

$location_id = $location_obj->get_id('property', '.entity.2.11');

$sql = "SELECT id, json_representation->>'innflyttet' as innflyttet FROM fm_bim_item"
	. " WHERE location_id = {$location_id}"
	. " AND location_code='{$values['location_code']}'"
	. " ORDER BY id DESC";
$db->query($sql, __LINE__, __FILE__);
$db->next_record();
$innflyttetdato_old	 = $db->f('innflyttet');

$sql = "SELECT innflyttetdato, tenant_id FROM fm_location4 WHERE location_code ='" . $values['location_code'] . "'";
$db->query($sql, __LINE__, __FILE__);
$db->next_record();
$innflyttetdato	 = $db->f('innflyttetdato');
$tenant_id		 = $db->f('tenant_id');

if ($tenant_id == $values['extra']['tenant_id'] && !$innflyttetdato_old)
{
	$db->transaction_begin();
	$sql = "UPDATE fm_bim_item SET json_representation=jsonb_set(json_representation, '{innflyttet}', '\"{$innflyttetdato}\"', true)"
		. " WHERE location_id = {$location_id}"
		. " AND id=" . (int)$receipt['id'];

	$db->query($sql, __LINE__, __FILE__);
	$db->transaction_commit();
}
