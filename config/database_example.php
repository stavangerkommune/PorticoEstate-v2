<?php

$phpgw_domain = [];
$phpgw_domain['default'] = array(
	'db_host'		 => 'your_host_here',
	'db_port'		 => (int) 'your_port_here',
	'db_name'		 => 'your_db_name',
	'db_user'		 => 'your_username_here',
	'db_pass'		 => 'your_password_here',
	'db_type'		 => 'pgsql',
	'config_passwd'	 => 'your_config_password_here'
);
/**
 * Add your domains here for miultiple database connections
 */
$phpgw_domain['your_domain_here'] = array(
	'db_host'		 => 'your_host_here',
	'db_port'		 => (int) 'your_port_here',
	'db_name'		 => 'your_db_name',
	'db_user'		 => 'your_username_here',
	'db_pass'		 => 'your_password_here',
	'db_type'		 => 'pgsql',
	'config_passwd'	 => 'your_config_password_here'
);

return $phpgw_domain;
