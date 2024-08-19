<?php

use App\Database\Db;
use App\modules\phpgwapi\controllers\Locations;
use App\modules\phpgwapi\controllers\Accounts\Accounts;
use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\controllers\Accounts\phpgwapi_user;

$location_obj = new Locations();
$accounts_obj = new Accounts();

$location_obj->add('.', 'Tom', 'eventplannerfrontend');
$location_obj->add('.admin', 'admin', 'eventplannerfrontend');
$location_obj->add('.application', 'application', 'eventplannerfrontend', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.events', 'events', 'eventplannerfrontend', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.customer', 'customer', 'eventplannerfrontend', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.vendor', 'vendor', 'eventplannerfrontend', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.calendar', 'calendar', 'eventplannerfrontend', $allow_grant = true);
$location_obj->add('.booking', 'booking', 'eventplannerfrontend', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.vendor_report', 'vendor_report', 'eventplannerfrontend', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.customer_report', 'customer_report', 'eventplannerfrontend', $allow_grant = true, $custom_tbl = '', $c_function = true);


// Default user

$modules = array(
	'eventplannerfrontend',
);


if (!$accounts_obj->exists('eventplannerguest')) // no guest account already exists
{
	$phpgwapi_common = new \phpgwapi_common();
	$passwd = $phpgwapi_common->randomstring(6) . "ABab1!";

	Settings::getInstance()->update('server', ['password_level' => '8CHAR']);
	$account = new phpgwapi_user();
	$account->lid = 'eventplannerguest';
	$account->firstname = 'Eventplanner';
	$account->lastname = 'Guest';
	$account->passwd = $passwd;
	$account->enabled = true;
	$account->expires = -1;
	$eventplannerguest = $accounts_obj->create($account, array(), array(), $modules);

	$preferences = createObject('phpgwapi.preferences');
	$preferences->set_account_id($eventplannerguest);
	$preferences->add('common', 'template_set', 'frontend');
	$preferences->save_repository(true, 'user');
	$config = CreateObject('phpgwapi.config', 'eventplannerfrontend');
	$config->read();
	$config->value('anonymous_user', 'eventplannerguest');
	$config->value('anonymous_passwd', $passwd);
	$config->save_repository();
}

if (!$eventplannerguest)
{
	$eventplannerguest = $accounts_obj->name2id('eventplannerguest');
}

$aclobj = Acl::getInstance();
$aclobj->set_account_id($eventplannerguest, true);
$aclobj->add('phpgwapi', 'anonymous', 1);
$aclobj->add('eventplannerfrontend', 'run', 1);
$aclobj->add('eventplannerfrontend', '.application', 1);
$aclobj->add('eventplannerfrontend', '.resource', 1);
$aclobj->add('eventplannerfrontend', '.customer', 1 | 2 | 4);
$aclobj->add('eventplannerfrontend', '.vendor', 1 | 2 | 4);
$aclobj->add('eventplannerfrontend', '.booking', 1 | 2 | 4);
$aclobj->add('eventplannerfrontend', '.vendor_report', 1 | 2 | 4);
$aclobj->add('eventplannerfrontend', '.customer_report', 1 | 2 | 4);
$aclobj->save_repository();

// Sane defaults for the API
$values = array(
	'usecookies'			=> 'True'
);
$db = Db::getInstance();
foreach ($values as $name => $val)
{
	$sql = "INSERT INTO phpgw_config VALUES('eventplannerfrontend', '{$name}', '{$val}')";
	$db->query($sql, __LINE__, __FILE__);
}
