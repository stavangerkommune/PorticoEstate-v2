<?php
/*	 * ************************************************************************\
	 * phpGroupWare - Setup                                                     *
	 * http://www.phpgroupware.org                                              *
	 * --------------------------------------------                             *
	 *  This program is free software; you can redistribute it and/or modify it *
	 *  under the terms of the GNU General Public License as published by the   *
	 *  Free Software Foundation; either version 2 of the License, or (at your  *
	 *  option) any later version.                                              *
	  \************************************************************************* */

use App\Database\Db;

$db = Db::getInstance();

$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('bday','Birthday','birthday','','Y',1)");
$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('email','E-Mail','email','','Y',2)");
$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('n_given','First Name','first_name','','Y',3)");
$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('n_family','Last Name','last_name','','Y',4)");
$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('adr_one_street','Address','address','','Y',5)");
$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('adr_one_locality','City','city','','Y',6)");
$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('adr_one_region','State','state','','Y',7)");
$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('adr_one_postalcode','ZIP/Postal','zip','','Y',8)");
$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('adr_one_countryname','Country','country','','Y',9)");
$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('tel_work','Phone','phone','','N',10)");
$db->query("INSERT INTO phpgw_reg_fields (field_name, field_text, field_type, field_values, field_required, field_order) VALUES ('gender','Gender','gender','','N',11)");

$db->query("DELETE FROM phpgw_config WHERE config_app='registration'");
$db->query("INSERT INTO phpgw_config (config_app, config_name, config_value) VALUES ('registration','display_tos','True')");
$db->query("INSERT INTO phpgw_config (config_app, config_name, config_value) VALUES ('registration','activate_account','email')");
$db->query("INSERT INTO phpgw_config (config_app, config_name, config_value) VALUES ('registration','username_is','choice')");
$db->query("INSERT INTO phpgw_config (config_app, config_name, config_value) VALUES ('registration','password_is','choice')");

$asyncservice = CreateObject('phpgwapi.asyncservice');
$asyncservice->set_timer(
	array('hour' => "*/2"),
	'registration_clear_reg_accounts',
	'registration.hook_helper.clear_reg_accounts',
	null
);
