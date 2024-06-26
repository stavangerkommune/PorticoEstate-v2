<?php

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\services\Cache;
use App\helpers\Template;
use App\modules\phpgwapi\services\Hooks;

function parse_navbar($force = False)
{
	$phpgwapi_common = new phpgwapi_common();
	$hooks = new Hooks();

	$hooks->process('after_navbar');

	if (Sanitizer::get_var('phpgw_return_as') != 'json' && $receipt = Cache::session_get('phpgwapi', 'phpgw_messages'))
	{
		Cache::session_clear('phpgwapi', 'phpgw_messages');
		$msgbox_data = $phpgwapi_common->msgbox_data($receipt);
		$msgbox_data = $phpgwapi_common->msgbox($msgbox_data);
		foreach ($msgbox_data as &$message)
		{
			echo "<div class='alert {$message['msgbox_class']}' role='alert'>";
			echo "<p class='msgbox_text'>" . $message['msgbox_text'] . "</p>";
			echo '</div>';
		}
	}

	register_shutdown_function('parse_footer_end');
}

function parse_footer_end()
{
	// Stop the register_shutdown_function causing the footer to be included twice - skwashd dec07
	static $footer_included = false;
	if ($footer_included)
	{
		return true;
	}
	$serverSettings = Settings::getInstance()->get('server');
	$template = new Template(PHPGW_TEMPLATE_DIR);

	$template->set_root(PHPGW_TEMPLATE_DIR);
	$template->set_file('footer', 'footer.tpl');

	$config_frontend = CreateObject('phpgwapi.config', 'bookingfrontend')->read();

	$footer_info		 = Cache::session_get('phpgwapi', 'footer_info');
	$footer_privacy_link = "https://www.aktiv-kommune.no/hva-er-aktivkommune/";
	if (!empty($config_frontend['footer_privacy_link']))
	{
		$footer_privacy_link = $config_frontend['footer_privacy_link'];
	}

	$cache_refresh_token = '';
	if (!empty($serverSettings['cache_refresh_token']))
	{
		$cache_refresh_token = "?n={$serverSettings['cache_refresh_token']}";
	}
	$phpgwapi_common = new phpgwapi_common();

	$var = array(
		'cart_complete_application'	 => lang('Complete applications'),
		'cart_add_application'		 => lang('new application'),
		'cart_confirm_delete'		 => lang('Do you want to delete application?'),
		'cart_header'				 => lang('Application basket'),
		'footer_about'				 => lang('About the service'),
		'footer_info'				 => $footer_info, //'Bergen kommune | R&aring;dhusgt 10 | Postboks 7700 | 5020 Bergen',
		'footer_privacy_link'		 => $footer_privacy_link,
		'footer_privacy_title'		 => lang('Privacy statement'),
		'powered_by'				 => lang('Powered by PorticoEstate version %1', $serverSettings['versions']['phpgwapi']),
		'javascript_end'			 => $phpgwapi_common->get_javascript_end($cache_refresh_token)
	);

	$template->set_var($var);

	$template->pfp('out', 'footer');

	$footer_included = true;
}
