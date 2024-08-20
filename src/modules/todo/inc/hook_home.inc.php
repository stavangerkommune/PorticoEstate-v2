<?php

/**
 * Todo - admin hook
 *
 * @copyright Copyright (C) 2002,2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package todo
 * @subpackage hooks
 * @version $Id$
 */

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\controllers\Applications;


$userSettings = Settings::getInstance()->get('user');

if (
	isset($userSettings['preferences']['todo']['mainscreen_showevents'])
	&& $userSettings['preferences']['todo']['mainscreen_showevents'] == True
)
{
	$todo = CreateObject('todo.ui');
	$todo->bo->start = 0;
	$todo->bo->limit = 5;
	$todo->start = 0;
	$todo->limit = 5;
	$extra_data = '<td>' . "\n" . $todo->show_list_body(False) . '</td>' . "\n";

	$applications = new Applications();
	$app_id = $applications->name2id('todo');
	$GLOBALS['portal_order'][] = $app_id;

	$portalbox = CreateObject('phpgwapi.portalbox');
	$portalbox->set_params(array(
		'app_id'	=> $app_id,
		'title'	=> lang('todo')
	));
	$portalbox->draw($extra_data);
}
