<?php

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\controllers\Locations;

$location_obj = new Locations();
$location_id = $location_obj->get_id('sms', 'run');
$config = CreateObject('admin.soconfig', $location_id);

Settings::getInstance()->set('sms_config', $config->config_data);
$reserved_codes = array(
	"PV", "BC", "GET", "PUT", "INFO", "SAVE", "DEL", "LIST",
	"RETR", "POP3", "SMTP", "BROWSE", "NEW", "SET", "POLL", "VOTE", "REGISTER", "REG",
	"DO", "USE", "EXECUTE", "EXEC", "RUN", "ACK"
);

Settings::getInstance()->update('sms_config', ['reserved_codes' => $reserved_codes]);
