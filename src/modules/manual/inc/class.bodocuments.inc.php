<?php

/**
 * phpGroupWare - manual
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2012 Free Software Foundation, Inc. http://www.fsf.org/
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
 * @subpackage admin
 * @version $Id$
 */

use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\services\Settings;

/**
 * Description
 * @package manual
 */
class manual_bodocuments
{

	var $start;
	var $query;
	var $filter;
	var $sort;
	var $order;
	var $cat_id;
	var $location_info = array();
	var $appname;
	var $allrows;
	public $acl_location = '.documents';
	var $public_functions = array(
		'addfiles' => true
	);

	function __construct()
	{
	}

	public function addfiles()
	{
		Settings::getInstance()->update('flags', ['xslt_app' => false, 'noframework' => true, 'nofooter' => true]);

		$acl = Acl::getInstance();
		$acl_add = $acl->check($this->acl_location, ACL_ADD, 'manual');
		$acl_edit = $acl->check($this->acl_location, ACL_EDIT, 'manual');
		$cat_id = Sanitizer::get_var('id', 'int');
		$check = Sanitizer::get_var('check', 'bool');
		$fileuploader = CreateObject('property.fileuploader');

		if (!$acl_add && !$acl_edit)
		{
			phpgw::no_access();
		}

		if (!$cat_id)
		{
			phpgw::no_access();
		}

		$test = false;

		if ($test)
		{

			if (!empty($_FILES))
			{
				$serverSettings = Settings::getInstance()->get('server');

				$tempFile = $_FILES['Filedata']['tmp_name'];
				$targetPath = "{$serverSettings['temp_dir']}/";
				$targetFile = str_replace('//', '/', $targetPath) . $_FILES['Filedata']['name'];
				move_uploaded_file($tempFile, $targetFile);
				echo str_replace($serverSettings['temp_dir'], '', $targetFile);
			}
			$phpgwapi_common = new \phpgwapi_common();
			$phpgwapi_common->phpgw_exit();
		}

		if ($check)
		{
			$fileuploader->check($cat_id, '/manual');
		}
		else
		{
			$fileuploader->upload($cat_id, '/manual');
		}
	}
}
