<?php

/**
 * phpGroupWare - controller: a part of a Facilities Management System.
 *
 * @author Erink Holm-Larsen <erik.holm-larsen@bouvet.no>
 * @author Torstein Vadla <torstein.vadla@bouvet.no>
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2011,2012 Free Software Foundation, Inc. http://www.fsf.org/
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
 * @internal Development of this application was funded by http://www.bergen.kommune.no/
 * @package property
 * @subpackage controller
 * @version $Id$
 */

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\services\Cache;
use App\modules\phpgwapi\services\Translation;

/**
 * Import the jQuery class
 */
phpgw::import_class('phpgwapi.jquery');

phpgw::import_class('phpgwapi.uicommon_jquery');

class controller_uicontrol_register_to_component extends phpgwapi_uicommon_jquery
{

	var $cat_id;
	var $start;
	var $query;
	var $sort;
	var $order;
	var $filter;
	var $type_id;
	var $location_code, $part_of_town_id, $status, $district_id, $allrows, $lookup, $_category_acl;
	private $bo;
	private $bocommon;
	private $so_control;
	var $public_functions = array(
		'index' => true,
		'query' => true,
		'edit_component' => true,
		'get_location_category' => true,
		'get_category_by_entity' => true,
		'get_entity_table_def' => true,
	);

	function __construct()
	{
		parent::__construct();

		$this->bo = CreateObject('property.bolocation', true);
		$this->bocommon = &$this->bo->bocommon;
		$this->so_control = CreateObject('controller.socontrol');

		$this->type_id = $this->bo->type_id;

		$this->start = $this->bo->start;
		$this->query = $this->bo->query;
		$this->sort = $this->bo->sort;
		$this->order = $this->bo->order;
		$this->filter = $this->bo->filter;
		$this->cat_id = $this->bo->cat_id;
		$this->part_of_town_id = $this->bo->part_of_town_id;
		$this->district_id = $this->bo->district_id;
		$this->status = $this->bo->status;
		$this->allrows = $this->bo->allrows;
		$this->lookup = $this->bo->lookup;
		$this->location_code = $this->bo->location_code;

		self::set_active_menu('controller::control::location_for_check_list');
		//			phpgwapi_css::getInstance()->add_external_file('controller/templates/base/css/base.css');
	}

	function index()
	{
		self::set_active_menu('controller::control::component_for_check_list');
		Settings::getInstance()->update('flags', ['xslt_app' => true]);
		$receipt = array();

		if (Sanitizer::get_var('phpgw_return_as') == 'json')
		{
			return $this->query();
		}

		$msgbox_data = array();
		if (Sanitizer::get_var('phpgw_return_as') != 'json' && $receipt = Cache::session_get('phpgwapi', 'phpgw_messages'))
		{
			Cache::session_clear('phpgwapi', 'phpgw_messages');
			$msgbox_data = $this->phpgwapi_common->msgbox_data($receipt);
			$msgbox_data = $this->phpgwapi_common->msgbox($msgbox_data);
		}

		$translation = Translation::getInstance();
		$translation->add_app('property');
		$entity = CreateObject('property.soadmin_entity');
		$entity_list = $entity->read(array('allrows' => true));

		$district_list = $this->bocommon->select_district_list('filter', $this->district_id);

		$part_of_town_list = execMethod('property.bogeneric.get_list', array(
			'type' => 'part_of_town',
			'selected' => $part_of_town_id
		));
		$location_type_list = execMethod('property.soadmin_location.select_location_type');

		array_unshift($entity_list, array('id' => '', 'name' => lang('select')));
		array_unshift($district_list, array('id' => '', 'name' => lang('select')));
		array_unshift($part_of_town_list, array('id' => '', 'name' => lang('select')));
		array_unshift($location_type_list, array('id' => '', 'name' => lang('select')));

		$cats = CreateObject('phpgwapi.categories', -1, 'controller', '.control');
		$cats->supress_info = true;

		$control_area = $cats->formatted_xslt_list(array(
			'format' => 'filter', 'globals' => true,
			'use_acl' => $this->_category_acl
		));


		$control_area_list = array();
		foreach ($control_area['cat_list'] as $cat_list)
		{
			$control_area_list[] = array(
				'id' => $cat_list['cat_id'],
				'name' => $cat_list['name'],
			);
		}

		array_unshift($control_area_list, array('id' => '', 'name' => lang('select')));



		$data = array(
			'msgbox_data' => $msgbox_data,
			'control_area_list' => array('options' => $control_area_list),
			'filter_form' => array(
				'control_area_list' => array('options' => $control_area_list),
				'entity_list' => array('options' => $entity_list),
				'district_list' => array('options' => $district_list),
				'part_of_town_list' => array('options' => $part_of_town_list),
				'location_type_list' => array('options' => $location_type_list),
			),
			'update_action' => self::link(array('menuaction' => 'controller.uicontrol_register_to_component.edit_component'))
		);

		self::add_javascript('controller', 'base', 'ajax_control_to_component.js');
		self::render_template_xsl(array('control_location/register_control_to_component'), $data);
	}
	/*
		 * Return categories based on chosen location
		 */

	public function get_location_category()
	{
		$type_id = Sanitizer::get_var('type_id');
		$category_types = $this->bocommon->select_category_list(array(
			'format' => 'filter',
			'selected' => 0,
			'type' => 'location',
			'type_id' => $type_id,
			'order' => 'descr'
		));
		$default_value = array('id' => '', 'name' => lang('no category selected'));
		array_unshift($category_types, $default_value);
		return json_encode($category_types);
	}
	/*

		 * Return parts of town based on chosen district
		 */

	public function get_category_by_entity()
	{
		$entity_id = Sanitizer::get_var('entity_id');
		$entity = CreateObject('property.soadmin_entity');

		$category_list = $entity->read_category(array('allrows' => true, 'entity_id' => $entity_id));

		return $category_list;
	}

	public function get_entity_table_def()
	{
		$entity_id = Sanitizer::get_var('entity_id', 'int');
		$cat_id = Sanitizer::get_var('cat_id', 'int');
		$boentity = CreateObject('property.boentity', false, 'entity');
		$boentity->read(array('dry_run' => true));
		$uicols = $boentity->uicols;
		$columndef = array();
		$columndef[] = array(
			'data' => 'select',
			'title' => lang('select'),
			'orderable' => false,
			'formatter' => false,
			'visible' => true,
			'class' => ''
		);

		$columndef[] = array(
			'data' => 'delete',
			'title' => lang('delete'),
			'orderable' => false,
			'formatter' => false,
			'visible' => true,
			'class' => ''
		);

		$count_fields = count($uicols['name']);

		for ($i = 0; $i < $count_fields; $i++)
		{
			if ($uicols['name'][$i])
			{
				if ($uicols['input_type'][$i] == 'hidden')
				{
					continue;
				}
				$columndef[] = array(
					'data' => $uicols['name'][$i],
					'title' => $uicols['descr'][$i],
					'orderable' => $uicols['sortable'][$i],
					'formatter' => $uicols['formatter'][$i],
					'class' => $uicols['classname'][$i],
				);
			}
		}
		foreach ($columndef as &$entry)
		{
			if ($entry['formatter'])
			{
				$render = <<<JS
					function (dummy1, dummy2, oData) {
							try {
								var ret = {$entry['formatter']}("{$entry['data']}", oData);
							}
							catch(err) {
								return err.message;
							}
							return ret;
                         }
JS;
				//						 $entry['render'] = $render;
			}
			unset($entry['formatter']);
		}
		return $columndef;
	}

	public function query()
	{
		$entity_id = Sanitizer::get_var('entity_id', 'int');
		$cat_id = Sanitizer::get_var('cat_id', 'int');
		$control_id = Sanitizer::get_var('control_id', 'int');
		$search = Sanitizer::get_var('search');
		$order = Sanitizer::get_var('order');
		$draw = Sanitizer::get_var('draw', 'int');
		$columns = Sanitizer::get_var('columns');



		$params = array(
			'start' => Sanitizer::get_var('start', 'int', 'REQUEST', 0),
			'results' => Sanitizer::get_var('length', 'int', 'REQUEST', 0),
			'query' => $search['value'],
			'order' => $columns[$order[0]['column']]['data'],
			'sort' => $order[0]['dir'],
			'allrows' => Sanitizer::get_var('length', 'int') == -1,
			'control_registered' => Sanitizer::get_var('control_registered', 'bool'),
			'control_id' => $control_id
		);

		if (!$entity_id && !$cat_id)
		{
			$values = array();
		}
		else
		{
			$location_id = $this->locations->get_id('property', ".entity.{$entity_id}.{$cat_id}");
			$boentity = CreateObject('property.boentity', false, 'entity');
			$boentity->district_id = Sanitizer::get_var('district_id', 'int');
			$boentity->part_of_town_id = Sanitizer::get_var('part_of_town_id', 'int');
			$boentity->location_code = Sanitizer::get_var('location_code');

			$values = $boentity->read($params);
		}

		foreach ($values as &$entry)
		{
			$entry['select'] = '';
			$entry['delete'] = '';
			if ($control_id)
			{
				$checked = '';
				if ($this->so_control->check_control_component($control_id, $location_id, $entry['id']))
				{
					$checked = 'checked = "checked" disabled = "disabled"';
					$entry['delete'] = "<input class =\"mychecks_delete\" type =\"checkbox\" name=\"values[delete][]\" value=\"{$control_id}_{$location_id}_{$entry['id']}\">";
				}
				$entry['select'] = "<input class =\"mychecks_add\" type =\"checkbox\" $checked name=\"values[register_component][]\" value=\"{$control_id}_{$location_id}_{$entry['id']}\">";
			}
		}

		$result_data = array(
			'results' => $values,
			'total_records' => $boentity->total_records,
			'draw' => $draw
		);

		return $this->jquery_results($result_data);
	}

	public function edit_component()
	{
		if ($values = Sanitizer::get_var('values'))
		{
			if (!$this->acl->check('.admin', ACL_EDIT, 'property'))
			{
				$receipt['error'][] = true;
				Cache::message_set(lang('you are not approved for this task'), 'error');
			}
			if (!$receipt['error'])
			{

				if ($this->so_control->register_control_to_component($values))
				{
					$result = array(
						'status' => 'updated'
					);
				}
				else
				{
					$result = array(
						'status' => 'error'
					);
				}
			}
		}

		if (Sanitizer::get_var('phpgw_return_as') == 'json')
		{
			if ($receipt = Cache::session_get('phpgwapi', 'phpgw_messages'))
			{
				Cache::session_clear('phpgwapi', 'phpgw_messages');
				$result['receipt'] = $receipt;
			}
			else
			{
				$result['receipt'] = array();
			}
			return $result;
		}
		else
		{
			phpgw::redirect_link('/index.php', array('menuaction' => 'controller.uicontrol_register_to_component.index'));
		}
	}
}
