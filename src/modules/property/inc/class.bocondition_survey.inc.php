<?php

/**
 * phpGroupWare - property: a Facilities Management System.
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
use App\modules\phpgwapi\services\Cache;
use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\controllers\Accounts\Accounts;
use App\modules\phpgwapi\controllers\Locations;

/**
 * Description
 * @package property
 */
class property_bocondition_survey
{

	var $start;
	var $query;
	var $filter;
	var $sort;
	var $order;
	var $cat_id;
	var $location_info	 = array();
	var $appname;
	var $allrows;
	var $so, $custom, $bocommon, $dateformat, $cats, $type, $type_id, $total_records, $use_session, $userSettings, $phpgwapi_common;
	public $acl_location	 = '.project.condition_survey';
	var $public_functions = array(
		'addfiles' => true
	);

	function __construct($session = false)
	{
		$this->userSettings = Settings::getInstance()->get('user');
		$this->phpgwapi_common = new \phpgwapi_common();

		$this->so			 = CreateObject('property.socondition_survey');
		$this->custom		 = &$this->so->custom;
		$this->bocommon		 = CreateObject('property.bocommon');
		$this->dateformat	 = $this->userSettings['preferences']['common']['dateformat'];

		$this->cats					 = CreateObject('phpgwapi.categories', -1, 'property', $this->acl_location);
		$this->cats->supress_info	 = true;

		$start	 = Sanitizer::get_var('start', 'int', 'REQUEST', 0);
		$query	 = Sanitizer::get_var('query');
		$sort	 = Sanitizer::get_var('sort');
		$order	 = Sanitizer::get_var('order');
		$filter	 = Sanitizer::get_var('filter', 'int');
		$cat_id	 = Sanitizer::get_var('cat_id', 'int');
		$allrows = Sanitizer::get_var('allrows', 'bool');
		$appname = Sanitizer::get_var('appname', 'string');

		if ($appname)
		{
			$this->appname		 = $appname;
			$this->so->appname	 = $appname;
		}

		$type			 = Sanitizer::get_var('type');
		$type_id		 = Sanitizer::get_var('type_id', 'int', 'REQUEST', 0);
		$this->type		 = $type;
		$this->type_id	 = $type_id;

		if ($session)
		{
			$this->read_sessiondata($type);
			$this->use_session = true;
		}

		$this->start	 = $start ? $start : 0;
		$this->query	 = isset($_REQUEST['query']) ? $query : $this->query;
		$this->sort		 = isset($_REQUEST['sort']) ? $sort : $this->sort;
		$this->order	 = isset($_REQUEST['order']) && $_REQUEST['order'] ? $order : $this->order;
		$this->filter	 = isset($_REQUEST['filter']) ? $filter : $this->filter;
		$this->cat_id	 = isset($_REQUEST['cat_id']) ? $cat_id : $this->cat_id;
		$this->allrows	 = isset($allrows) ? $allrows : false;
	}

	public function save_sessiondata($data)
	{
		if ($this->use_session)
		{
			Cache::session_set($this->acl_location, 'session_data', $data);
		}
	}

	function read_sessiondata($type)
	{
		$data = Cache::session_get($this->acl_location, 'session_data');

		$this->start	 = $data['start'];
		$this->query	 = $data['query'];
		$this->filter	 = $data['filter'];
		$this->sort		 = $data['sort'];
		$this->order	 = $data['order'];
		$this->cat_id	 = $data['cat_id'];
		$this->allrows	 = $data['allrows'];
	}

	function column_list($selected = '', $allrows = '')
	{
		$userSettings = Settings::getInstance()->get('user');
		if (!$selected)
		{
			$selected = $userSettings['preferences']['property']["columns_{$this->acl_location}"];
		}

		$filter		 = array('list' => ''); // translates to "list IS NULL"
		$columns	 = $this->custom->find('property', $this->acl_location, 0, '', '', '', true, false, $filter);
		$column_list = $this->bocommon->select_multi_list($selected, $columns);

		return $column_list;
	}

	public function addfiles()
	{
		$flags = Settings::getInstance()->get('flags');
		$flags['xslt_app']		 = false;
		$flags['noframework']	 = true;
		$flags['nofooter']		 = true;
		Settings::getInstance()->set('flags', $flags);

		$acl			 = Acl::getInstance();
		$acl_add		 = $acl->check($this->acl_location, ACL_ADD, 'property');
		$acl_edit		 = $acl->check($this->acl_location, ACL_EDIT, 'property');
		$id				 = Sanitizer::get_var('id', 'int');
		$check			 = Sanitizer::get_var('check', 'bool');
		$fileuploader	 = CreateObject('property.fileuploader');

		if (!$acl_add && !$acl_edit)
		{
			$this->phpgwapi_common->phpgw_exit();
		}

		if (!$id)
		{
			$this->phpgwapi_common->phpgw_exit();
		}

		$test = false;

		if ($test)
		{
			if (!empty($_FILES))
			{
				$tempFile	 = $_FILES['Filedata']['tmp_name'];
				$serverSettings = Settings::getInstance()->get('server');
				$targetPath	 = "{$serverSettings['temp_dir']}/";
				$targetFile	 = str_replace('//', '/', $targetPath) . $_FILES['Filedata']['name'];
				move_uploaded_file($tempFile, $targetFile);
				echo str_replace($serverSettings['temp_dir'], '', $targetFile);
			}
			$this->phpgwapi_common->phpgw_exit();
		}

		if ($check)
		{
			$fileuploader->check("condition_survey/{$id}");
		}
		else
		{
			$fileuploader->upload("condition_survey/{$id}");
		}
	}

	public function read($data = array())
	{
		$values = $this->so->read($data);
		foreach ($values as &$entry)
		{
			$entry['year'] = date('Y', $entry['entry_date']);
		}

		$this->total_records = $this->so->total_records;
		return $values;
	}

	public function read_single($data = array())
	{
		$custom_fields = false;
		$locations_obj = new Locations();
		if ($locations_obj->get_attrib_table('property', $this->acl_location))
		{
			$custom_fields		 = true;
			$data['attributes']	 = $this->custom->find('property', $this->acl_location, 0, '', 'ASC', 'attrib_sort', true, true);
		}

		$values = array();
		if (isset($data['id']) && $data['id'])
		{
			$values = $this->so->read_single($data);
		}
		if ($custom_fields)
		{
			$values = $this->custom->prepare($values, 'property', $this->acl_location, $data['view']);
		}

		$values['report_date'] = $this->phpgwapi_common->show_date($values['report_date'], $this->dateformat);

		if (isset($values['vendor_id']) && $values['vendor_id'] && !$values['vendor_name'])
		{
			$contacts = CreateObject('property.sogeneric');
			$contacts->get_location_info('vendor', false);

			$custom						 = createObject('property.custom_fields');
			$vendor_data['attributes']	 = $custom->find('property', '.vendor', 0, '', 'ASC', 'attrib_sort', true, true);

			$vendor_data = $contacts->read_single(array('id' => $values['vendor_id']), $vendor_data);
			if (is_array($vendor_data))
			{
				foreach ($vendor_data['attributes'] as $attribute)
				{
					if ($attribute['name'] == 'org_name')
					{
						$values['vendor_name'] = $attribute['value'];
						break;
					}
				}
			}
			unset($contacts);
		}

		if ($values['coordinator_id'])
		{
			$accounts_obj = new Accounts();
			$values['coordinator_name'] = $accounts_obj->get($values['coordinator_id'])->__toString();
		}
		return $values;
	}

	public function save($data = array())
	{
		if (isset($data['attributes']) && is_array($data['attributes']))
		{
			$data['attributes'] = $this->custom->convert_attribute_save($data['attributes']);
		}

		try
		{
			if (isset($data['id']) && $data['id'])
			{
				$id = $this->so->edit($data);
			}
			else
			{
				$id = $this->so->add($data);
			}
		}
		catch (Exception $e)
		{
			if ($e)
			{
				throw $e;
			}
		}

		return $id;
	}

	public function edit_title($data)
	{
		try
		{
			$this->so->edit_title($data);
		}
		catch (Exception $e)
		{
			if ($e)
			{
				throw $e;
			}
		}
	}

	public function edit_multiplier($data)
	{
		try
		{
			$this->so->edit_multiplier($data);
		}
		catch (Exception $e)
		{
			if ($e)
			{
				throw $e;
			}
		}
	}

	public function import($survey, $import_data)
	{
		try
		{
			$this->so->import($survey, $import_data);
		}
		catch (Exception $e)
		{
			if ($e)
			{
				throw $e;
			}
		}
	}

	public function get_summation($ids, $year = 0)
	{
		$year = $year ? (int)$year : date('Y');

		if ($ids)
		{
			$values = $this->so->read(array('allrows' => true, 'status_open' => true, 'ids' => $ids));
			$data = $this->so->get_summation($ids);
		}
		else
		{
			$data = array();
		}


		$values	 = array();
		$i		 = 0;
		foreach ($data as $entry)
		{

			//				if($entry['multiplier'] > 1)
			//				{
			//					if($entry['area_gross'] )
			//					{
			//						if($entry['amount'] && ($entry['amount'] / $entry['area_gross']) < 700) // filtrere ekstremverdier
			//						{
			//							$entry['amount'] = $entry['amount'] * $entry['multiplier'];
			//						}
			//					}
			//					else
			//					{
			//						$entry['amount'] = $entry['amount'] * $entry['multiplier'];
			//					}
			//				}

			$entry['amount'] = $entry['amount'] * $entry['multiplier'] * $entry['representative'];

			$i = "{$entry['building_part']}_{$entry['category']}";

			$values[$entry['condition_survey_id']][$i]['building_part']	 = $entry['building_part'];
			$values[$entry['condition_survey_id']][$i]['category']		 = $entry['category'];

			$diff = $entry['year'] - $year;
			if ($diff < 0)
			{
				$period = 1;
			}
			else
			{
				$period	 = ceil($diff / 5) + 1;
				$period	 = $period < 6 ? $period : 6;
			}

			for ($j = 1; $j < 7; $j++)
			{
				$values[$entry['condition_survey_id']][$i]["period_{$j}"]	 += 0;
				$values[$entry['condition_survey_id']][$i]['sum']			 += 0;
				if ($j == $period)
				{
					$values[$entry['condition_survey_id']][$i]["period_{$j}"]	 += $entry['amount'];
					$values[$entry['condition_survey_id']][$i]['sum']			 += $entry['amount'];
				}
			}
		}
		unset($entry);

		$ret = array();

		$_values = array();
		foreach ($values as $condition_survey_id => $entry)
		{
			foreach ($entry as $type => $_entry)
			{
				$_values[$type]['building_part'] = $_entry['building_part'];
				$_values[$type]['category']		 = $_entry['category'];
				$_values[$type]['period_1']		 += $_entry['period_1'];
				$_values[$type]['period_2']		 += $_entry['period_2'];
				$_values[$type]['period_3']		 += $_entry['period_3'];
				$_values[$type]['period_4']		 += $_entry['period_4'];
				$_values[$type]['period_5']		 += $_entry['period_5'];
				$_values[$type]['period_6']		 += $_entry['period_6'];
				$_values[$type]['sum']			 += $_entry['sum'];
			}
		}
		unset($_entry);
		unset($entry);

		foreach ($_values as $entry)
		{
			$ret[] = $entry;
		}

		foreach ($ret as $key => $row)
		{
			$building_part[$key] = $row['building_part'];
			$category[$key]		 = $row['category'];
		}

		// Sort the data with account_lastname ascending, account_firstname ascending
		// Add $data as the last parameter, to sort by the common key
		if ($ret)
		{
			array_multisort($building_part, SORT_ASC, $category, SORT_ASC, $ret);
		}

		return $ret;
	}

	function get_category_name($cat_id)
	{
		static $category_name = array();

		if (!isset($category_name[$cat_id]))
		{
			$category				 = $this->cats->return_single($cat_id);
			$category_name[$cat_id]	 = $category[0]['name'];
		}
		return $category_name[$cat_id];
	}

	public function delete($id)
	{
		try
		{
			$this->so->delete($id);
		}
		catch (Exception $e)
		{
			if ($e)
			{
				throw $e;
			}
		}
	}

	public function delete_imported_records($id)
	{
		try
		{
			$this->so->delete_imported_records($id);
		}
		catch (Exception $e)
		{
			if ($e)
			{
				throw $e;
			}
		}
	}

	function get_export_data($id)
	{
		$condition_survey	 = $this->read_single(array('id' => (int)$id));
		$values				 = $this->so->get_export_data($id);
		return array('condition_survey' => $condition_survey, 'values' => $values);
	}
}
