<?php

/**************************************************************************\
 * phpGroupWare - catalog_manager                                           *
 * http://www.phpgroupware.org                                              *
 * This program is part of the GNU project, see http://www.gnu.org/         *
 *                                                                          *
 * Copyright 2003,2008 Free Software Foundation, Inc.                       *
 *                                                                          *
 * Originally Written by Jonathan Alberto Rivera Gomez - jarg at co.com.mx  *
 * Current Maintained by Jonathan Alberto Rivera Gomez - jarg at co.com.mx  *
 * Written by Dave Hall <skwashd@phpgroupware.org>							 *
 * --------------------------------------------                             *
 * Development of this application was funded by http://www.sogrp.com       *
 * --------------------------------------------                             *
 *  This program is Free Software; you can redistribute it and/or modify it *
 *  under the terms of the GNU General Public License as published by the   *
 *  Free Software Foundation; either version 2 of the License, or (at your  *
 *  option) any later version.                                              *
  \**************************************************************************/

use App\helpers\Template;

phpgw::import_class('phpgwapi.uicommon');

class addressbook_uicatalog_contact_addr_type extends phpgwapi_uicommon
{
	var $template;
	var $public_functions = array(
		'view' => True,
		'save' => True,
		'delete' => True,
		'edit' => True
	);

	private $receipt = array();

	function __construct()
	{
		parent::__construct();

		$this->bo = CreateObject('addressbook.bocatalog_contact_addr_type');
		$this->template	= Template::getInstance();
		self::set_active_menu("admin::{$this->currentapp}::contact_addr_type");
	}

	function view()
	{
		if (Sanitizer::get_var('phpgw_return_as') == 'json')
		{
			return $this->query();
		}

		$tabs = array();
		$tabs['addr_type'] = array('label' => lang('Location Types'), 'link' => '#addr_type');

		$tabletools[] = array(
			'my_name' => 'delete',
			'text' => lang('delete'),
			'type' => 'custom',
			'custom_code' => "
					var oArgs = " . json_encode(array(
				'menuaction' => "{$this->currentapp}.uicatalog_contact_addr_type.delete",
				'phpgw_return_as' => 'json'
			)) . ";
					var parameters = " . json_encode(array('parameter' => array(array(
				'name' => 'addr_type_id',
				'source' => 'addr_type_id'
			)))) . ";
					deleteAddrType(oArgs, parameters);
				"
		);

		$tabletools[] = array(
			'my_name' => 'edit',
			'text' => lang('edit'),
			'type' => 'custom',
			'custom_code' => "
					var oArgs = " . json_encode(array(
				'menuaction' => "{$this->currentapp}.uicatalog_contact_addr_type.edit",
				'phpgw_return_as' => 'json'
			)) . ";
					var parameters = " . json_encode(array('parameter' => array(array(
				'name' => 'addr_type_id',
				'source' => 'addr_type_id'
			)))) . ";
					updateAddrType(oArgs, parameters);
				"
		);

		$datatable_def[] = array(
			'container' => 'datatable-container_0',
			'requestUrl' => json_encode(self::link(array(
				'menuaction' => "{$this->currentapp}.uicatalog_contact_addr_type.view",
				'phpgw_return_as' => 'json'
			))),
			'data' => json_encode(array()),
			'ColumnDefs' => array(
				array('key' => 'addr_type_id', 'label' => lang('Id'), 'className' => '', 'sortable' => false, 'hidden' => true),
				array('key' => 'addr_description', 'label' => lang('Location types'), 'className' => '', 'sortable' => false, 'hidden' => false)
			),
			'tabletools' => $tabletools,
			'config' => array(
				array('disableFilter' => true, 'singleSelect' => true)
			)
		);

		$data = array(
			'datatable_def' => $datatable_def,
			'tabs' => phpgwapi_jquery::tabview_generate($tabs, 0),
			'value_active_tab' => 0,
			'confirm_msg' => lang('do you really want to delete this entry'),
			'lang_name' => lang('Please enter a name'),
		);

		self::add_javascript('addressbook', 'base', 'catalog_addr_type.js');
		self::render_template_xsl(array('catalog_addr_type', 'datatable_inline'), array('view' => $data));
	}

	public function query($relaxe_acl = false)
	{
		$draw = Sanitizer::get_var('draw', 'int');
		$start = Sanitizer::get_var('start', 'int', 'REQUEST', 0);
		$num_rows = Sanitizer::get_var('length', 'int', 'REQUEST', 10);

		$entries = $this->bo->select_catalog();
		$values = array();

		foreach ($entries as $entry)
		{
			$values[] = array('addr_type_id' => $entry['addr_type_id'], 'addr_description' => $entry['addr_description']);
		}

		if ($num_rows == -1)
		{
			$out = $values;
		}
		else
		{
			$page = ceil(($start / $num_rows));
			$files_part = array_chunk($values, $num_rows);
			$out = $files_part[$page];
		}

		$result_data = array('results' => $out);
		$result_data['total_records'] = count($values);
		$result_data['draw'] = $draw;

		return $this->jquery_results($result_data);
	}

	private function _populate($data = array())
	{
		$values['id'] = Sanitizer::get_var('addr_type_id');
		$values['description'] = array('addr_description' => Sanitizer::get_var('addr_description'));

		if (!$values['description'])
		{
			$this->receipt['error'][] = array('msg' => lang('Please enter a name !'));
		}

		return $values;
	}

	public function save($ajax = false)
	{
		$values = $this->_populate();

		if ($this->receipt['error'])
		{
			return $this->receipt;
		}

		try
		{
			if ($values['id'])
			{
				$this->bo->update($values['id'], $values['description']);
			}
			else
			{
				$this->bo->insert($values['description']);
			}
		}
		catch (Exception $e)
		{
			if ($e)
			{
				$this->receipt['error'][] = array('msg' => $e->getMessage());
			}
		}

		$this->receipt['message'][] = array('msg' => lang('location type has been saved'));

		return $this->receipt;
	}

	function delete()
	{
		$ids = Sanitizer::get_var('addr_type_id');

		foreach ($ids as $id)
		{
			$this->bo->delete($id);
		}

		return true;
	}

	function edit()
	{
		$ids = Sanitizer::get_var('addr_type_id');
		$record = $this->bo->get_record($ids[0]);

		return array('id' => $record[0]['addr_type_id'], 'description' => $record[0]['addr_description']);
	}
}
