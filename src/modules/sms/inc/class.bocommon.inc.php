<?php

/**
 * phpGroupWare - SMS
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2003-2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
 * @package sms
 * @subpackage core
 * @version $Id$
 */

use App\modules\phpgwapi\services\Settings;

/**
 * Description
 * @package sms
 */
class sms_bocommon
{

	var $start;
	var $query;
	var $filter;
	var $sort;
	var $order;
	var $cat_id;

	function __construct()
	{
	}

	function check_perms($rights, $required)
	{
		return ($rights & $required);
	}

	function select_list($selected = '', $input_list = '')
	{
		if (isset($input_list) and is_array($input_list))
		{
			foreach ($input_list as &$entry)
			{
				if ($entry['id'] == $selected)
				{
					$entry['selected'] = 1;
				}
			}
		}
		return $input_list;
	}

	function no_access($message = '')
	{
		phpgwapi_xslttemplates::getInstance()->add_file(array('no_access'));

		$receipt['error'][] = array('msg' => lang('NO ACCESS'));
		if ($message)
		{
			$receipt['error'][] = array('msg' => $message);
		}
		$phpgwapi_common = new \phpgwapi_common();

		$msgbox_data = $phpgwapi_common->msgbox_data($receipt);

		$data = array(
			'msgbox_data' => $phpgwapi_common->msgbox($msgbox_data),
			'message' => $message,
		);

		$appname = lang('No access');

		Settings::getInstance()->update('flags', ['app_header' => lang('sms') . ' - ' . $appname]);

		phpgwapi_xslttemplates::getInstance()->set_var('phpgw', array('no_access' => $data));
	}
}
