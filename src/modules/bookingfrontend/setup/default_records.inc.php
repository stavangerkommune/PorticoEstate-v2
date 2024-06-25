<?php
// Default user

use App\modules\phpgwapi\services\Settings;
use App\Database\Db;
use App\modules\phpgwapi\controllers\Locations;
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\controllers\Accounts\Accounts;
use App\modules\phpgwapi\controllers\Accounts\phpgwapi_user;


$db = Db::getInstance();
$location_obj = new Locations();
$serverSettings = Settings::getInstance()->get('server');


$modules = array(
	'bookingfrontend',
	//	'preferences'
);

$aclobj = Acl::getInstance();
$accounts_obj = new Accounts();

if (!$accounts_obj->exists('bookingguest')) // no guest account already exists
{
	$phpgwapi_common = new \phpgwapi_common();
	$passwd = $phpgwapi_common->randomstring(6) . "ABab1!";

	$serverSettings['password_level'] = '8CHAR';
	$account = new phpgwapi_user();
	$account->lid = 'bookingguest';
	$account->firstname = 'booking';
	$account->lastname = 'Guest';
	$account->passwd = $passwd;
	$account->enabled = true;
	$account->expires = -1;
	$bookingguest = $accounts_obj->create($account, array(), array(), $modules);

	$preferences = createObject('phpgwapi.preferences');
	$preferences->set_account_id($bookingguest);
	$preferences->add('common', 'template_set', 'bookingfrontend');
	$preferences->save_repository(true, 'user');

	$config = CreateObject('phpgwapi.config', 'bookingfrontend');
	$config->read();
	$config->value('anonymous_user', 'bookingguest');
	$config->value('anonymous_passwd', $passwd);
	$config->save_repository();
}

// Sane defaults for the API
$values = array(
	'usecookies'			=> 'True'
);

foreach ($values as $name => $val)
{
	$sql = "INSERT INTO phpgw_config VALUES('bookingfrontend', '{$name}', '{$val}')";
	$db->query($sql, __LINE__, __FILE__);
}
