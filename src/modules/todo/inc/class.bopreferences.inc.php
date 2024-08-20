<?php

/**
 * Todo preferences
 *
 * @author Craig Knudsen <cknudsen@radix.net>
 * @author Mark Peters <skeeter@phpgroupware.org>
 * @copyright Copyright (C) Craig Knudsen <cknudsen@radix.net>
 * @copyright Copyright (C) 2002,2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package todo
 * @version $Id$
 * @internal Based on Webcalendar by Craig Knudsen http://www.radix.net/~cknudsen
 */

use App\modules\phpgwapi\services\Settings;

/**
 * Todo preferences
 *  
 * @package todo
 */
class todo_bopreferences
{
	var $public_functions = array(
		'preferences'  => True
	);

	var $prefs;
	var $debug = False;

	function __construct()
	{
		$userSettings = Settings::getInstance()->get('user');

		$this->prefs['todo']    = $userSettings['preferences']['todo'];
	}

	function preferences()
	{
		$submit = get_var('submit', array('POST'));
		if ($submit)
		{
			$preferences = createObject('phpgwapi.preferences');
			$prefs = get_var('prefs', array('POST'));
			if ($prefs['mainscreen_showevents'] == True)
			{
				$preferences->add('todo', 'mainscreen_showevents', $prefs['mainscreen_showevents']);
			}
			else
			{
				$preferences->delete('todo', 'mainscreen_showevents');
			}

			$preferences->save_repository(True);
			phpgw::redirect_link('/preferences/');
		}
	}
}
