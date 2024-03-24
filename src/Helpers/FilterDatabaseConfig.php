<?php


$_phpgw_domains = array_keys($phpgw_domain);
$default_domain = $_phpgw_domains[0];

if (isset($_POST['login']))	// on login
{
	$login = $_POST['login'];
	$_logindomain = \Sanitizer::get_var('logindomain', 'string', 'POST', $default_domain);
	if (strstr($login, '#') === False) {
		$login .= '#' . $_logindomain;
	}
	list(, $user_domain) = explode('#', $login);
} else if (\Sanitizer::get_var('domain', 'string', 'REQUEST', false)) {
	// on "normal" pageview
	if (!$user_domain = \Sanitizer::get_var('domain', 'string', 'GET', false)) {
		if (!$user_domain = \Sanitizer::get_var('domain', 'string', 'POST', false)) {
			$user_domain = \Sanitizer::get_var('domain', 'string', 'COOKIE', false);
		}
	}
} else {
	$user_domain = \Sanitizer::get_var('last_domain', 'string', 'COOKIE', false);
}
$db_server = [];
if (isset($phpgw_domain[$user_domain])) {
	$db_server['db_host']			= $phpgw_domain[$user_domain]['db_host'];
	$db_server['db_port']			= $phpgw_domain[$user_domain]['db_port'];
	$db_server['db_name']			= $phpgw_domain[$user_domain]['db_name'];
	$db_server['db_user']			= $phpgw_domain[$user_domain]['db_user'];
	$db_server['db_pass']			= $phpgw_domain[$user_domain]['db_pass'];
  $db_server['domain']      = $user_domain;
} else {
	$db_server['db_host']			= $phpgw_domain[$default_domain]['db_host'];
	$db_server['db_port']			= $phpgw_domain[$default_domain]['db_port'];
	$db_server['db_name']			= $phpgw_domain[$default_domain]['db_name'];
	$db_server['db_user']			= $phpgw_domain[$default_domain]['db_user'];
	$db_server['db_pass']			= $phpgw_domain[$default_domain]['db_pass'];
  $db_server['domain']      = $default_domain;
}

return $db_server;