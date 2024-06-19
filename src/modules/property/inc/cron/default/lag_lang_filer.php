<?php

/**
 * phpGroupWare - property: a Facilities Management System.
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2003,2004,2005,2006,2007 Free Software Foundation, Inc. http://www.fsf.org/
 * This file is part of phpGroupWare.
 *
 * phpGroupWare is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * phpGroupWare is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with phpGroupWare; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
 * @package property
 * @subpackage custom
 * @version $Id$
 */

use App\modules\phpgwapi\services\Cache;
use App\modules\phpgwapi\services\Settings;
use App\Database\Db;

/**
 * Description
 * @package property
 */
class lag_lang_filer
{

	var $function_name = 'lag_lang_filer';
	var $bocommon, $db, $receipt, $phpgwapi_common;

	function __construct()
	{
		$this->bocommon	 = CreateObject('property.bocommon');
		$this->db		 = Db::getInstance();
		$this->phpgwapi_common = new \phpgwapi_common();

	}

	function pre_run($data = '')
	{
		if ($data['enabled'] == 1)
		{
			$confirm = true;
			$cron	 = true;
		}
		else
		{
			$confirm = Sanitizer::get_var('confirm', 'bool', 'POST');
			$execute = Sanitizer::get_var('execute', 'bool', 'GET');
		}
		if ($confirm)
		{
			$this->execute($cron);
		}
		else
		{
			$this->confirm($execute = false);
		}
	}

	function confirm($execute = '')
	{
		$link_data = array(
			'menuaction' => 'property.custom_functions.index',
			'function'	 => $this->function_name,
			'execute'	 => $execute,
		);

		if (!$execute)
		{
			$lang_confirm_msg = lang('do you want to perform this action');
		}
		$lang_yes	 = lang('yes');
		phpgwapi_xslttemplates::getInstance()->add_file(array('confirm_custom'));
		$msgbox_data = $this->bocommon->msgbox_data($this->receipt);
		$data		 = array(
			'msgbox_data'			 => $this->phpgwapi_common->msgbox($msgbox_data),
			'done_action'			 => phpgw::link('/admin/index.php'),
			'run_action'			 => phpgw::link('/index.php', $link_data),
			'message'				 => $this->receipt['message'],
			'lang_confirm_msg'		 => $lang_confirm_msg,
			'lang_yes'				 => $lang_yes,
			'lang_yes_statustext'	 => 'lag_lang_filer fra database',
			'lang_no_statustext'	 => 'tilbake',
			'lang_no'				 => lang('no'),
			'lang_done'				 => 'Avbryt',
			'lang_done_statustext'	 => 'tilbake'
		);

		$appname										 = lang('location');
		$function_msg									 = 'lag_lang_filer';
		Settings::getInstance()->update('flags', ['app_header' => lang('property') . ' - ' . $appname . ': ' . $function_msg]);
		phpgwapi_xslttemplates::getInstance()->set_var('phpgw', array('confirm' => $data));
		phpgwapi_xslttemplates::getInstance()->pp();
	}

	function execute($cron = '')
	{

		$sql = "SELECT * from phpgw_lang WHERE app_name = 'property' AND lang='no' ORDER BY message_id ASC";

		$this->db->query($sql, __LINE__, __FILE__);

		$str = '';
		$i = 0;
		while ($this->db->next_record())
		{
			$str .= $this->db->f('message_id') . "\t";
			$str .= $this->db->f('app_name') . "\t";
			$str .= $this->db->f('lang') . "\t";
			$str .= $this->db->f('content') . "\n";
			$i++;
		}

		_debug_array($str);
		/* 			   $filename= 'phpgw_no_lang';

			  $size=strlen($str);

			  $browser = CreateObject('phpgwapi.browser');
			  $browser->content_header($filename,'application/txt',$size);

			  echo $str;
			 */

		$this->receipt['message'][] = array('msg' => $i . ' tekster lagt til');

		if (!$cron)
		{
			$this->confirm($execute = false);
		}
	}
}
