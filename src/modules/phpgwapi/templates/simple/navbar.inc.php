<?php

/**
 * Template navigation bar
 * @copyright Copyright (C) 2003-2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package phpgwapi
 * @subpackage gui
 * @version $Id$
 */

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\services\Cache;
use App\helpers\Template;
use App\modules\phpgwapi\services\Hooks;
use App\modules\phpgwapi\security\Sessions;


/**
 * Parse navigation bar
 *
 * @param boolean $force
 */
function parse_navbar($force = False)
{
	$phpgwapi_common = new phpgwapi_common();
	$hooks = new Hooks();
	$flags = Settings::getInstance()->get('flags');
	$apps = Settings::getInstance()->get('apps');
	$userSettings = Settings::getInstance()->get('user');


	// we hack the template root here as this is the template set of last resort
	$tpl = CreateObject('phpgwapi.template', dirname(__FILE__), "remove");

	$tpl->set_file('navbar', 'navbar.tpl');
	$tpl->set_block('navbar', 'app', 'apps');

	$navbar = execMethod('phpgwapi.menu.get', 'navbar');
	prepare_navbar($navbar);
	foreach ($navbar as $app => $app_data)
	{
		if ($app == 'logout') // insert manual before logout
		{
			if (isset($userSettings['apps']['manual']))
			{
				$tpl->set_var(array(
					'url' => "javascript:openwindow('"
						. phpgw::link('/index.php', array(
							'menuaction' => 'manual.uimanual.help',
							'app' => $flags['currentapp'],
							'section' => isset($apps['manual']['section']) ? $apps['manual']['section'] : '',
							'referer' => Sanitizer::get_var('menuaction')
						)) . "','700','600')",

					'text' => lang('help'),
					'icon' => $phpgwapi_common->image('manual', 'navbar')
				));
			}
			$tpl->parse('apps', 'app', true);
		}

		$tpl->set_var(array(
			'url'	=> $app_data['url'],
			'text'	=> $app_data['text'],
			'icon'	=> $phpgwapi_common->image($app_data['image'][0], $app_data['image'][1])
		));
		$tpl->parse('apps', 'app', true);
	}

	// Maybe we should create a common function in the phpgw_accounts_shared.inc.php file
	// to get rid of duplicate code.
	if (
		!isset($userSettings['lastpasswd_change'])
		|| $userSettings['lastpasswd_change'] == 0
	)
	{
		$api_messages = lang('You are required to change your password during your first login')
			. '<br> Click this image on the navbar: <img src="'
			. $phpgwapi_common->image('preferences', 'navbar') . '">';
	}
	else if ($userSettings['lastpasswd_change'] < time() - (86400 * 30))
	{
		$api_messages = lang('it has been more then %1 days since you changed your password', 30);
	}

	// This is gonna change
	if (isset($cd))
	{
		$var['messages'] = "<div class=\"warn\">$api_messages<br>\n" . $phpgwapi_common->check_code($cd) . "</div>\n";
	}

	if (isset($flags['app_header']))
	{
		$var['current_app_header'] = $flags['app_header'];
	}
	else
	{
		$tpl->set_block('navbar', 'app_header', 'app_header');
		$var['app_header'] = '';
	}

	$tpl->set_var($var);
	$tpl->pfp('out', 'navbar');

	// If the application has a header include, we now include it
	if ((!isset($flags['noappheader'])
			|| !$flags['noappheader'])
		&& isset($_GET['menuaction'])
	)
	{
		list($app, $class, $method) = explode('.', $_GET['menuaction']);

		if($app && $class)
		{
			$Object = CreateObject("{$app}.{$class}");

			if (
				is_object($Object)
				&& isset($Object->public_functions)
				&& is_array($Object->public_functions)
				&& isset($Object->public_functions['header'])
				&& $Object->public_functions['header']
			)
			{

				$Object->header();

			}
		}

	}
	$tpl->set_root(PHPGW_APP_TPL);

	$hooks->process('after_navbar');

}


/**
 * Parse navigation bar end
 */
function parse_navbar_end()
{
	$phpgwapi_common = new phpgwapi_common();
	$navbar = Settings::getInstance()->get('navbar');
	$userSettings = Settings::getInstance()->get('user');
	$serverSettings = Settings::getInstance()->get('server');



	// we hack the template root here as this is the template set of last resort
	$tpl = CreateObject('phpgwapi.template', dirname(__FILE__), "remove");

	$tpl->set_file('footer', 'footer.tpl');

	$var = array(
		'powered_by' => lang('Powered by phpGroupWare version %1', $serverSettings['versions']['phpgwapi'])
	);

	if (
		isset($navbar['admin'])
		&& isset($userSettings['preferences']['common']['show_currentusers'])
		&& $userSettings['preferences']['common']['show_currentusers']
	)
	{
		$var['current_users'] = '<a href="' . phpgw::link('/index.php', array('menuaction' => 'admin.uicurrentsessions.list_sessions'))
			. '">&nbsp;' . lang('Current users') . ': ' . Sessions::getInstance()->total() . '</a>';
	}
	$now = time();
	$var['user_info'] = $phpgwapi_common->display_fullname() . ' - '
		. lang($phpgwapi_common->show_date($now, 'l')) . ' '
		. $phpgwapi_common->show_date($now, $userSettings['preferences']['common']['dateformat']);
	$tpl->set_var($var);

	$hooks = new Hooks();
	$hooks->process('navbar_end');
	$tpl->pfp('out', 'footer');
}

/**
 * Callback for usort($navbar)
 *
 * @param array $item1 the first item to compare
 * @param array $item2 the second item to compare
 * @return int result of comparision
 */
function sort_navbar($item1, $item2)
{
	$a = &$item1['order'];
	$b = &$item2['order'];

	if ($a == $b)
	{
		return strcmp($item1['text'], $item2['text']);
	}
	return ($a < $b) ? -1 : 1;
}

/**
 * Organise the navbar properly
 *
 * @param array $navbar the navbar items
 * @return array the organised navbar
 */
function prepare_navbar(&$navbar)
{
	uasort($navbar, 'sort_navbar');
}
