<?php

use App\Database\Db;


// Sane defaults for the API
$values = array(
	'usecookies'			=> 'True'
);
$db = Db::getInstance();
foreach ($values as $name => $val)
{
	$sql = "INSERT INTO phpgw_config VALUES('activitycalendarfrontend', '{$name}', '{$val}')";
	$db->query($sql, __LINE__, __FILE__);
}
