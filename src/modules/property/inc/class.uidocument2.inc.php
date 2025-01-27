<?php

/**
 * phpGroupWare - property: a part of a Facilities Management System.
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
 * @internal Development of this application was funded by http://www.bergen.kommune.no/
 * @package property
 * @subpackage logistic
 * @version $Id: class.uidocument2.inc.php 11942 2014-05-04 19:43:36Z sigurdne $
 */

use App\modules\phpgwapi\services\Cache;
use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\controllers\Accounts\Accounts;


phpgw::import_class('phpgwapi.uicommon_jquery');
phpgw::import_class('phpgwapi.jquery');

class property_uidocument2 extends phpgwapi_uicommon_jquery
{

	private $bo;
	private $receipt		 = array();
	var $currentapp, $nextmatchs, $account, $bocommon, $config,
		$acl, $acl_location, $acl_read, $acl_add, $acl_edit, $acl_delete, $acl_manage, $_category_acl;

	public $public_functions = array(
		'query'						 => true,
		'index'						 => true,
		'view'						 => true,
		'add'						 => true,
		'edit'						 => true,
		'save'						 => true,
		'delete'					 => true,
		'delete_imported_records'	 => true,
		'get_vendors'				 => true,
		'get_users'					 => true,
		'edit_survey_title'			 => true,
		'get_files'					 => true,
		'get_request'				 => true,
		'get_summation'				 => true,
		'view_file'					 => true,
		'import'					 => true,
		'download'					 => true,
		'summation'					 => true
	);

	public function __construct()
	{
		parent::__construct();

		$this->bo			 = CreateObject('property.bodocument');
		$this->bocommon		 = &$this->bo->bocommon;
		$this->acl_location	 = $this->bo->acl_location;
		$this->acl_read		 = $this->acl->check($this->acl_location, ACL_READ, 'property');
		$this->acl_add		 = $this->acl->check($this->acl_location, ACL_ADD, 'property');
		$this->acl_edit		 = $this->acl->check($this->acl_location, ACL_EDIT, 'property');
		$this->acl_delete	 = $this->acl->check($this->acl_location, ACL_DELETE, 'property');
		$this->acl_manage	 = $this->acl->check($this->acl_location, 16, 'property');

		$this->flags['menu_selection'] = "property::documentation";
		Settings::getInstance()->update('flags', ['menu_selection' => $this->flags['menu_selection']]);
	}

	public function download()
	{
		if (!$this->acl_read)
		{
			$this->bocommon->no_access();
			return;
		}

		$values = $this->query();

		$descr		 = array();
		$columns	 = array();
		$columns[]	 = 'id';
		$columns[]	 = 'title';
		$columns[]	 = 'descr';
		$columns[]	 = 'address';
		$columns[]	 = 'cnt';

		foreach ($columns as $_column)
		{
			$descr[] = lang(str_replace('_', ' ', $_column));
		}

		$this->bocommon->download($values, $columns, $descr);
	}

	private function _get_columns()
	{

		$columns = array();

		$columns[]	 = array(
			'key'		 => 'loc1',
			'label'		 => lang('property'),
			'sortable'	 => true,
		);
		$columns[]	 = array(
			'key'		 => 'loc2',
			'label'		 => lang('building'),
			'sortable'	 => true
		);
		$columns[]	 = array(
			'key'		 => 'loc1_name',
			'label'		 => 'loc1_name',
			'sortable'	 => true
		);
		$columns[]	 = array(
			'key'		 => 'loc2_name',
			'label'		 => 'loc2_name',
			'sortable'	 => true
		);
		$columns[]	 = array(
			'key'		 => 'address',
			'label'		 => lang('address'),
			'sortable'	 => true,
		);
		$columns[]	 = array(
			'key'		 => 'link',
			'label'		 => 'dummy',
			'hidden'	 => true,
			'sortable'	 => false,
		);



		return $columns;
	}

	public function index()
	{
		if (!$this->acl_read)
		{
			$this->bocommon->no_access();
			return;
		}

		if (Sanitizer::get_var('phpgw_return_as') == 'json')
		{
			return $this->query();
		}

		$categories	 = $this->_get_categories();
		$columns	 = $this->_get_columns();


		$data = array(
			'datatable_name' => lang('documents'),
			'form'			 => array(
				'toolbar' => array(
					'item' => array(
						array(
							'type'	 => 'filter',
							'name'	 => 'cat_id',
							'text'	 => lang('category') . ':',
							'list'	 => $categories,
						),
						/* 							array(
						  'type' => 'link',
						  'value' => lang('download'),
						  'href' => 'javascript:window.open("'. self::link(array('menuaction' => 'property.uidocument2.download', 'export' => true, 'allrows' => true)) . '","window")',
						  'class' => 'new_item'
						  ) */
					),
				),
			),
			'datatable'		 => array(
				'source'		 => self::link(array(
					'menuaction'		 => 'property.uidocument2.index',
					'phpgw_return_as'	 => 'json'
				)),
				'editor_action'	 => 'property.uidocument2.edit_survey_title',
				'field'			 => $columns,
				'new_item'		 => self::link(array('menuaction' => 'property.uidocument.edit')),
				'allrows'		 => true,
				//	'left_click_action'	=> 'console.log',
				//	'dbl_click_action'	=> 'alert', // Only one of them (or none..)
				'download'		 => self::link(array(
					'menuaction' => 'property.uidocument2.download',
					'export'	 => true, 'allrows'	 => true
				)),
			),
		);

		$parameters = array(
			'parameter' => array(
				array(
					'name'	 => 'id',
					'source' => 'id'
				),
				array(
					'name'	 => 'loc1',
					'source' => 'loc1'
				),
			)
		);

		$data['datatable']['actions'][] = array(
			'my_name'	 => 'view_survey',
			'text'		 => lang('view'),
			'action'	 => phpgw::link('/index.php', array(
				'menuaction' => 'property.uidocument2.view'
			)),
			'parameters' => json_encode($parameters)
		);

		$data['datatable']['actions'][] = array(
			'my_name'	 => 'edit_survey',
			'text'		 => lang('edit'),
			'action'	 => phpgw::link('/index.php', array(
				'menuaction' => 'property.uidocument2.edit'
			)),
			'parameters' => json_encode($parameters)
		);

		$data['datatable']['actions'][] = array(
			'my_name'	 => 'import_survey',
			'text'		 => lang('import'),
			'action'	 => phpgw::link('/index.php', array(
				'menuaction' => 'property.uidocument2.import'
			)),
			'parameters' => json_encode($parameters)
		);


		if ($this->acl->check('.admin', ACL_DELETE, 'property'))
		{
			$data['datatable']['actions'][] = array(
				'my_name'		 => 'delete_imported_records',
				'text'			 => lang('delete imported records'),
				'confirm_msg'	 => lang('do you really want to delete this entry') . '?',
				'action'		 => phpgw::link('/index.php', array(
					'menuaction' => 'property.uidocument2.delete_imported_records'
				)),
				'parameters'	 => json_encode($parameters)
			);
		}

		if ($this->acl->check('.admin', ACL_DELETE, 'property'))
		{
			$data['datatable']['actions'][] = array(
				'my_name'		 => 'delete_survey',
				'text'			 => lang('delete'),
				'confirm_msg'	 => lang('do you really want to delete this entry') . '?',
				'action'		 => phpgw::link('/index.php', array(
					'menuaction' => 'property.uidocument2.delete'
				)),
				'parameters'	 => json_encode($parameters)
			);
		}

		self::render_template_xsl('datatable2', $data);
	}

	public function query()
	{
		$search	 = Sanitizer::get_var('search');
		$order	 = Sanitizer::get_var('order');
		$draw	 = Sanitizer::get_var('draw', 'int');

		$columns = $this->_get_columns();
		$export	 = Sanitizer::get_var('export', 'bool');


		$params = array(
			'start'		 => Sanitizer::get_var('start', 'int', 'REQUEST', 0),
			'results'	 => Sanitizer::get_var('length', 'int', 'REQUEST', 0),
			'query'		 => $search['value'],
			'order'		 => $order,
			'cat_id'	 => Sanitizer::get_var('cat_id', 'int', 'REQUEST', 0),
			'allrows'	 => Sanitizer::get_var('length', 'int') == -1 || $export,
		);

		$values = $this->bo->read2(array('columns' => $columns, 'params' => $params));

		if ($export)
		{
			return $values;
		}

		$result_data					 = array('results' => $values);
		$result_data['total_records']	 = $this->bo->total_records;
		$result_data['draw']			 = $draw;

		array_walk($result_data['results'], array($this, '_add_links'), "property.uidocument2.view");

		return $this->jquery_results($result_data);
	}

	public function view()
	{
		if (!$this->acl_read)
		{
			$this->bocommon->no_access();
			return;
		}
		$this->edit(null, $mode = 'view');
	}

	public function add()
	{
		$this->edit();
	}

	/**
	 * Prepare data for view and edit - depending on mode
	 *
	 * @param array  $values  populated object in case of retry
	 * @param string $mode    edit or view
	 * @param int    $id      entity id - no id means 'new'
	 *
	 * @return void
	 */
	public function edit($values = array(), $mode = 'edit')
	{
		$id = isset($values['id']) && $values['id'] ? $values['id'] : Sanitizer::get_var('id', 'int');

		if (!$this->acl_add && !$this->acl_edit)
		{
			phpgw::redirect_link('/index.php', array(
				'menuaction' => 'property.uidocument2.view',
				'id'		 => $id
			));
		}

		if ($mode == 'view')
		{
			if (!$this->acl_read)
			{
				$this->bocommon->no_access();
				return;
			}
		}
		else
		{
			if (!$this->acl_add && !$this->acl_edit)
			{
				$this->bocommon->no_access();
				return;
			}
		}

		Cache::session_clear('property.request', 'session_data');

		$tabs				 = array();
		$tabs['generic']	 = array('label' => lang('generic'), 'link' => '#generic');
		$active_tab			 = 'generic';
		$tabs['documents']	 = array('label' => lang('documents'), 'link' => null);
		$tabs['request']	 = array('label' => lang('request'), 'link' => null);
		$tabs['summation']	 = array('label' => lang('summation'), 'link' => null);
		$tabs['import']		 = array('label' => lang('import'), 'link' => null);

		if ($id)
		{
			if ($mode == 'edit')
			{
				$tabs['import']['link'] = '#import';
			}
			$tabs['documents']['link']	 = '#documents';
			$tabs['request']['link']	 = '#request';
			$tabs['summation']['link']	 = '#summation';

			if (!$values)
			{
				$values = $this->bo->read_single(array('id' => $id, 'view' => $mode == 'view'));
			}
		}

		if (isset($values['location_code']) && $values['location_code'])
		{
			$values['location_data'] = execMethod('property.solocation.read_single', $values['location_code']);
		}

		$categories = $this->_get_categories($values['cat_id']);

		$bolocation		 = CreateObject('property.bolocation');
		$location_data	 = $bolocation->initiate_ui_location(array(
			'values'		 => $values['location_data'],
			'type_id'		 => 2,
			'required_level' => 1,
			'no_link'		 => $_no_link, // disable lookup links for location type less than type_id
			'lookup_type'	 => $mode == 'edit' ? 'form2' : 'view2',
			'tenant'		 => false,
			'lookup_entity'	 => array(),
			'entity_data'	 => isset($values['p']) ? $values['p'] : ''
		));

		$msgbox_data = $this->bocommon->msgbox_data($this->receipt);

		$file_def = array(
			array(
				'key'		 => 'file_name', 'label'		 => lang('Filename'), 'sortable'	 => false,
				'resizeable' => true
			),
			array(
				'key'		 => 'delete_file', 'label'		 => lang('Delete file'), 'sortable'	 => false,
				'resizeable' => true
			),
		);


		$datatable_def	 = array();
		$datatable_def[] = array(
			'container'	 => 'datatable-container_0',
			'requestUrl' => json_encode(self::link(array(
				'menuaction'		 => 'property.uidocument2.get_files',
				'id'				 => $id, 'phpgw_return_as'	 => 'json'
			))),
			'ColumnDefs' => $file_def,
		);

		$related_def = array(
			array('key' => 'url', 'label' => lang('id'), 'sortable' => true, 'resizeable' => true),
			array(
				'key'		 => 'title', 'label'		 => lang('title'), 'sortable'	 => false, 'resizeable' => true,
				'width'		 => '100'
			), //width not working...
			array('key' => 'status', 'label' => lang('status'), 'sortable' => true, 'resizeable' => true),
			//				array('key' => 'category','label'=>lang('category'),'sortable'=>false,'resizeable'=>true),
			array(
				'key'		 => 'condition_degree', 'label'		 => lang('condition degree'), 'sortable'	 => false,
				'resizeable' => true
			),
			array('key' => 'score', 'label' => lang('score'), 'sortable' => true, 'resizeable' => true),
			array(
				'key'		 => 'amount_investment', 'label'		 => lang('investment'), 'sortable'	 => true,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'amount_operation', 'label'		 => lang('operation'), 'sortable'	 => true,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'amount_potential_grants', 'label'		 => lang('potential grants'),
				'sortable'	 => true, 'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			//				array('key' => 'planned_budget','label'=>lang('planned budget'),'sortable'=>true,'resizeable'=>true,'formatter'=>'YAHOO.portico.FormatterAmount0'),
			array(
				'key'		 => 'recommended_year', 'label'		 => lang('recommended year'), 'sortable'	 => true,
				'resizeable' => true
			),
			array(
				'key'		 => 'planned_year', 'label'		 => lang('planned year'), 'sortable'	 => true,
				'resizeable' => true
			),
			array('key' => 'related', 'label' => lang('related'), 'sortable' => false, 'resizeable' => true),
		);

		$datatable_def[] = array(
			'container'	 => 'datatable-container_1',
			'requestUrl' => json_encode(self::link(array(
				'menuaction'		 => 'property.uidocument2.get_request',
				'id'				 => $id, 'phpgw_return_as'	 => 'json'
			))),
			'ColumnDefs' => $related_def
		);

		$summation_def = array(
			array(
				'key'		 => 'building_part', 'label'		 => lang('building part'), 'sortable'	 => false,
				'resizeable' => true
			),
			array('key' => 'category', 'label' => lang('category'), 'sortable' => true, 'resizeable' => true),
			array(
				'key'		 => 'period_1', 'label'		 => lang('year') . ':: < 1', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'period_2', 'label'		 => lang('year') . ':: 1 - 5', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'period_3', 'label'		 => lang('year') . ':: 6 - 10', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'period_4', 'label'		 => lang('year') . ':: 11 - 15', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'period_5', 'label'		 => lang('year') . ':: 16 - 20', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'period_6', 'label'		 => lang('year') . ':: 21 +', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'sum', 'label'		 => lang('sum'), 'sortable'	 => false, 'resizeable' => true,
				'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
		);

		$datatable_def[] = array(
			'container'	 => 'datatable-container_2',
			'requestUrl' => json_encode(self::link(array(
				'menuaction'		 => 'property.uidocument2.get_summation',
				'id'				 => $id, 'phpgw_return_as'	 => 'json'
			))),
			'ColumnDefs' => $summation_def
		);

		$this->config = CreateObject('phpgwapi.config', 'property');
		$this->config->read();

		$data = array(
			'datatable_def'		 => $datatable_def,
			'msgbox_data'		 => $this->phpgwapi_common->msgbox($msgbox_data),
			'survey'			 => $values,
			'location_data2'	 => $location_data,
			'lang_coordinator'	 => isset($this->config->config_data['lang_request_coordinator']) && $this->config->config_data['lang_request_coordinator'] ? $this->config->config_data['lang_request_coordinator'] : lang('coordinator'),
			'categories'		 => array('options' => $categories),
			'status_list'		 => array('options' => execMethod('property.bogeneric.get_list', array(
				'type'		 => 'condition_survey_status', 'selected'	 => $values['status_id'],
				'add_empty'	 => true
			))),
			'editable'			 => $mode == 'edit',
			'tabs'				 => phpgwapi_jquery::tabview_generate($tabs, $active_tab),
			'multiple_uploader'	 => $mode == 'edit' ? true : '',
		);

		$this->flags['app_header'] = lang('property') . '::' . lang('condition survey');
		Settings::getInstance()->update('flags', ['app_header' => $this->flags['app_header']]);

		if ($mode == 'edit')
		{
			$jqcal = CreateObject('phpgwapi.jqcal');
			$jqcal->add_listener('report_date');
			phpgwapi_jquery::load_widget('core');
			self::add_javascript('property', 'base', 'condition_survey_edit.js', false, array('combine' => true));
			self::add_javascript('phpgwapi', 'yui3', 'yui/yui-min.js');
			self::add_javascript('phpgwapi', 'yui3-gallery', 'gallery-formvalidator/gallery-formvalidator-min.js');
			phpgwapi_css::getInstance()->add_external_file('phpgwapi/js/yui3-gallery/gallery-formvalidator/validatorCss.css');
		}

		self::add_javascript('property', 'base', 'condition_survey.js', false, array('combine' => true));

		self::add_javascript('phpgwapi', 'tinybox2', 'packed.js', false, array('combine' => true));
		phpgwapi_css::getInstance()->add_external_file('phpgwapi/js/tinybox2/style.css');

		self::render_template_xsl(array('condition_survey'), $data);
	}

	/**
	 * Saves an entry to the database for new/edit - redirects to view
	 *
	 * @param int  $id  entity id - no id means 'new'
	 *
	 * @return void
	 */
	public function save()
	{
		if (!$_POST)
		{
			return $this->edit();
		}

		$id = (int)Sanitizer::get_var('id');

		if ($id)
		{
			$values = $this->bo->read_single(array('id' => $id, 'view' => true));
		}
		else
		{
			$values = array();
		}

		/*
			 * Overrides with incoming data from POST
			 */
		$values = $this->_populate($values);

		if ($this->receipt['error'])
		{
			$this->edit($values);
		}
		else
		{

			try
			{
				$id = $this->bo->save($values);
			}
			catch (Exception $e)
			{
				if ($e)
				{
					Cache::message_set($e->getMessage(), 'error');
					$this->edit($values);
					return;
				}
			}

			$this->_handle_files($id);
			if ($_FILES['import_file']['tmp_name'])
			{
				$this->_handle_import($id);
			}
			else
			{
				Cache::message_set('ok!', 'message');
				phpgw::redirect_link('/index.php', array(
					'menuaction' => 'property.uidocument2.edit',
					'id'		 => $id
				));
			}
		}
	}

	/**
	 * Fetch a list of files to be displayed in view/edit
	 *
	 * @param int  $id  entity id
	 *
	 * @return array $ResultSet json resultset
	 */
	public function get_files()
	{
		$id = Sanitizer::get_var('id', 'int', 'REQUEST');

		if (!$this->acl_read)
		{
			return;
		}

		$link_file_data = array(
			'menuaction' => 'property.uidocument2.view_file',
			'id'		 => $id
		);


		$link_view_file = self::link($link_file_data);

		$vfs				 = CreateObject('phpgwapi.vfs');
		$vfs->override_acl	 = 1;

		$files = $vfs->ls(array(
			'string'	 => "/property/condition_survey/{$id}",
			'checksubdirs'	=> false,
			'relatives'	 => array(RELATIVE_NONE)
		));

		$vfs->override_acl = 0;


		//------ Start pagination

		$start			 = Sanitizer::get_var('startIndex', 'int', 'REQUEST', 0);
		$total_records	 = count($files);

		$num_rows = isset($this->userSettings['preferences']['common']['maxmatchs']) && $this->userSettings['preferences']['common']['maxmatchs'] ? (int)$this->userSettings['preferences']['common']['maxmatchs'] : 15;

		if ($allrows)
		{
			$out = $files;
		}
		else
		{
			//	$page = ceil( ( $start / $total_records ) * ($total_records/ $num_rows) );
			$page		 = ceil(($start / $num_rows));
			$files_part	 = array_chunk($files, $num_rows);
			$out		 = $files_part[$page];
		}

		//------ End pagination


		$lang_view	 = lang('click to view file');
		$lang_delete = lang('click to delete file');

		$values = array();
		foreach ($out as $_entry)
		{
			$values[] = array(
				'file_name'		 => "<a href='{$link_view_file}&amp;file_name={$_entry['name']}' target='_blank' title='{$lang_view}'>{$_entry['name']}</a>",
				'delete_file'	 => "<input type='checkbox' name='file_action[]' value='{$_entry['name']}' title='$lang_delete'>",
			);
		}

		$data = array(
			'ResultSet' => array(
				'totalResultsAvailable'	 => $total_records,
				'startIndex'			 => $start,
				'sortKey'				 => 'type',
				'sortDir'				 => "ASC",
				'Result'				 => $values,
				'pageSize'				 => $num_rows,
				'activePage'			 => floor($start / $num_rows) + 1
			)
		);
		return $data;
	}

	function get_summation()
	{
		$id		 = Sanitizer::get_var('id', 'int', 'REQUEST');
		$year	 = Sanitizer::get_var('year', 'int', 'REQUEST');

		if (!$this->acl_read)
		{
			return;
		}

		$values = $this->bo->get_summation($id, $year);

		$total_records = count($values);

		$num_rows	 = isset($this->userSettings['preferences']['common']['maxmatchs']) && $this->userSettings['preferences']['common']['maxmatchs'] ? (int)$this->userSettings['preferences']['common']['maxmatchs'] : 15;
		$start		 = Sanitizer::get_var('startIndex', 'int', 'REQUEST', 0);

		$allrows	 = true;
		$num_rows	 = $total_records;

		if ($allrows)
		{
			$out = $values;
		}
		else
		{
			$page		 = ceil(($start / $total_records) * ($total_records / $num_rows));
			$values_part = array_chunk($values, $num_rows);
			$out		 = $values_part[$page];
		}


		$data = array(
			'ResultSet' => array(
				'totalResultsAvailable'	 => $total_records,
				'startIndex'			 => $start,
				'sortKey'				 => 'building_part',
				'sortDir'				 => "ASC",
				'Result'				 => $out,
				'pageSize'				 => $num_rows,
				'activePage'			 => floor($start / $num_rows) + 1
			)
		);
		return $data;
	}

	function get_request()
	{
		$id = Sanitizer::get_var('id', 'int', 'REQUEST');

		if (!$this->acl_read)
		{
			return;
		}

		$borequest	 = CreateObject('property.borequest');
		$start		 = Sanitizer::get_var('startIndex', 'int', 'REQUEST', 0);
		$sortKey	 = Sanitizer::get_var('sort', 'string', 'REQUEST', 'request_id');
		$sortDir	 = Sanitizer::get_var('dir', 'string', 'REQUEST', 'ASC');

		$criteria = array(
			'condition_survey_id'	 => $id,
			'start'					 => $start,
			'order'					 => $sortKey,
			'sort'					 => $sortDir
		);

		$values			 = $borequest->read_survey_data($criteria);
		$total_records	 = $borequest->total_records;

		$base_url = self::link(array('menuaction' => 'property.uirequest.edit'));
		foreach ($values as &$_entry)
		{
			$_entry['url'] = "<a href=\"{$base_url}&id={$_entry['id']}\" >{$_entry['id']}</a>";
		}

		$num_rows	 = isset($this->userSettings['preferences']['common']['maxmatchs']) && $this->userSettings['preferences']['common']['maxmatchs'] ? (int)$this->userSettings['preferences']['common']['maxmatchs'] : 15;
		$data		 = array(
			'ResultSet' => array(
				'totalResultsAvailable'	 => $total_records,
				'startIndex'			 => $start,
				'sortKey'				 => $sortKey,
				'sortDir'				 => $sortDir,
				'Result'				 => $values,
				'pageSize'				 => $num_rows,
				'activePage'			 => floor($start / $num_rows) + 1
			)
		);
		return $data;
	}

	/**
	 * Dowloads a single file to the browser
	 *
	 * @param int  $id  entity id
	 *
	 * @return file
	 */
	function view_file()
	{
		if (!$this->acl_read)
		{
			return lang('no access');
		}

		$bofiles = CreateObject('property.bofiles');
		$bofiles->view_file('condition_survey');
	}

	/**
	 * Store and / or delete files related to an entity
	 *
	 * @param int  $id  entity id
	 *
	 * @return void
	 */
	private function _handle_files($id)
	{
		$id = (int)$id;
		if (!$id)
		{
			throw new Exception('uidocument2::_handle_files() - missing id');
		}
		$bofiles = CreateObject('property.bofiles');

		if (isset($_POST['file_action']) && is_array($_POST['file_action']))
		{
			$bofiles->delete_file("/condition_survey/{$id}/", array('file_action' => $_POST['file_action']));
		}
		$file_name = str_replace(' ', '_', $_FILES['file']['name']);

		if ($file_name)
		{
			if (!is_file($_FILES['file']['tmp_name']))
			{
				Cache::message_set(lang('Failed to upload file !'), 'error');
				return;
			}

			$to_file = $bofiles->fakebase . '/condition_survey/' . $id . '/' . $file_name;
			if ($bofiles->vfs->file_exists(array(
				'string'	 => $to_file,
				'relatives'	 => array(RELATIVE_NONE)
			)))
			{
				Cache::message_set(lang('This file already exists !'), 'error');
			}
			else
			{
				$bofiles->create_document_dir("condition_survey/{$id}");
				$bofiles->vfs->override_acl = 1;

				if (!$bofiles->vfs->cp(array(
					'from'		 => $_FILES['file']['tmp_name'],
					'to'		 => $to_file,
					'relatives'	 => array(RELATIVE_NONE | VFS_REAL, RELATIVE_ALL)
				)))
				{
					Cache::message_set(lang('Failed to upload file !'), 'error');
				}
				$bofiles->vfs->override_acl = 0;
			}
		}
	}

	public function import()
	{
		$id = Sanitizer::get_var('id', 'int', 'REQUEST');
		$this->_handle_import($id);
	}

	/**
	 * Import deviations found in the survey to the database from a spreadsheet
	 *
	 * @param int  $id  entity id
	 *
	 * @return void
	 */
	private function _handle_import($id)
	{
		$id = (int)$id;
		if (!$id)
		{
			throw new Exception('uidocument2::_handle_import() - missing id');
		}

		$step		 = Sanitizer::get_var('step', 'int', 'REQUEST');
		$sheet_id	 = Sanitizer::get_var('sheet_id', 'int', 'REQUEST');

		$sheet_id = $sheet_id ? $sheet_id : Sanitizer::get_var('selected_sheet_id', 'int', 'REQUEST');

		if (!$step)
		{
			if ($cached_file = Cache::session_get('property', 'condition_survey_import_file'))
			{
				Cache::session_clear('property', 'condition_survey_import_file');
				unlink($cached_file);
				unset($cached_file);
			}
		}

		if ($start_line = Sanitizer::get_var('start_line', 'int', 'REQUEST'))
		{
			Cache::system_set('property', 'import_sheet_start_line', $start_line);
		}
		else
		{
			$start_line	 = Cache::system_get('property', 'import_sheet_start_line');
			$start_line	 = $start_line ? $start_line : 1;
		}


		if ($columns = Sanitizer::get_var('columns'))
		{
			Cache::system_set('property', 'import_sheet_columns', $columns);
		}
		else
		{
			$columns = Cache::system_get('property', 'import_sheet_columns');
			$columns = $columns && is_array($columns) ? $columns : array();
		}


		if ($step > 1)
		{
			$cached_file = Cache::session_get('property', 'condition_survey_import_file');
		}

		if ($step == 1 || isset($_FILES['import_file']['tmp_name']))
		{
			$file		 = $_FILES['import_file']['tmp_name'];
			$cached_file = "{$file}_temporary_import_file";
			// save a copy to survive multiple steps
			file_put_contents($cached_file, file_get_contents($file));
			Cache::session_set('property', 'condition_survey_import_file', $cached_file);
			$step		 = 1;

			// Add the file to documents
			$bofiles = CreateObject('property.bofiles');
			$to_file = "{$bofiles->fakebase}/condition_survey/{$id}/" . str_replace(' ', '_', $_FILES['import_file']['name']);

			$bofiles->vfs->rm(
				array(
					'string'	 => $to_file,
					'relatives'	 => array(
						RELATIVE_NONE
					)
				)
			);

			$bofiles->create_document_dir("condition_survey/{$id}");
			$bofiles->vfs->override_acl = 1;

			$bofiles->vfs->cp(array(
				'from'		 => $_FILES['import_file']['tmp_name'],
				'to'		 => $to_file,
				'relatives'	 => array(RELATIVE_NONE | VFS_REAL, RELATIVE_ALL)
			));
			$bofiles->vfs->override_acl = 0;
			unset($bofiles);
		}

		$tabs = array();

		switch ($step)
		{
			case 0:
				$active_tab		 = 'step_1';
				$lang_submit	 = lang('continue');
				$tabs['step_1']	 = array('label' => lang('choose file'), 'link' => '#step_1');
				$tabs['step_2']	 = array('label' => lang('choose sheet'), 'link' => null);
				$tabs['step_3']	 = array('label' => lang('choose start line'), 'link' => null);
				$tabs['step_4']	 = array('label' => lang('choose columns'), 'link' => null);
				break;
			case 1:
				$active_tab		 = 'step_2';
				$lang_submit	 = lang('continue');
				$tabs['step_1']	 = array('label'	 => lang('choose file'), 'link'	 => self::link(array(
					'menuaction' => 'property.uidocument2.import', 'id'		 => $id, 'step'		 => 0,
					'sheet_id'	 => $sheet_id,
					'start_line' => $start_line
				)));
				$tabs['step_2']	 = array('label' => lang('choose sheet'), 'link' => '#step_2');
				$tabs['step_3']	 = array('label' => lang('choose start line'), 'link' => null);
				$tabs['step_4']	 = array('label' => lang('choose columns'), 'link' => null);
				break;
			case 2:
				$active_tab		 = 'step_3';
				$lang_submit	 = lang('continue');
				$tabs['step_1']	 = array('label'	 => lang('choose file'), 'link'	 => self::link(array(
					'menuaction' => 'property.uidocument2.import', 'id'		 => $id, 'step'		 => 0,
					'sheet_id'	 => $sheet_id,
					'start_line' => $start_line
				)));
				$tabs['step_2']	 = array('label'	 => lang('choose sheet'), 'link'	 => self::link(array(
					'menuaction' => 'property.uidocument2.import', 'id'		 => $id, 'step'		 => 1,
					'sheet_id'	 => $sheet_id,
					'start_line' => $start_line
				)));
				$tabs['step_3']	 = array('label' => lang('choose start line'), 'link' => '#step_3');
				$tabs['step_4']	 = array('label' => lang('choose columns'), 'link' => null);
				break;
			case 3:
				$active_tab		 = 'step_4';
				$lang_submit	 = lang('import');
				$tabs['step_1']	 = array('label'	 => lang('choose file'), 'link'	 => self::link(array(
					'menuaction' => 'property.uidocument2.import', 'id'		 => $id, 'step'		 => 0,
					'sheet_id'	 => $sheet_id,
					'start_line' => $start_line
				)));
				$tabs['step_2']	 = array('label'	 => lang('choose sheet'), 'link'	 => self::link(array(
					'menuaction' => 'property.uidocument2.import', 'id'		 => $id, 'step'		 => 1,
					'sheet_id'	 => $sheet_id,
					'start_line' => $start_line
				)));
				$tabs['step_3']	 = array('label'	 => lang('choose start line'), 'link'	 => self::link(array(
					'menuaction' => 'property.uidocument2.import', 'id'		 => $id, 'step'		 => 2,
					'sheet_id'	 => $sheet_id,
					'start_line' => $start_line
				)));
				$tabs['step_4']	 = array('label' => lang('choose columns'), 'link' => '#step_4');
				break;
				/*
				  case 4://temporary
				  Cache::session_clear('property', 'condition_survey_import_file');
				  unlink($cached_file);
				  phpgw::redirect_link('/index.php',array('menuaction' => 'property.uidocument2.import', 'id' =>$id, 'step' => 0));
				  break;
				 */
		}

		//-----------

		if (!$step)
		{
			Cache::session_clear('property', 'condition_survey_import_file');
			unlink($cached_file);
		}
		else if ($cached_file)
		{

			try
			{
				$inputFileType	 = \PhpOffice\PhpSpreadsheet\IOFactory::identify($cached_file);
				$reader			 = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
				$reader->setReadDataOnly(true);
				$spreadsheet	 = $reader->load($cached_file);
				$AllSheets		 = $spreadsheet->getSheetNames();

				$sheets = array();
				if ($AllSheets)
				{
					foreach ($AllSheets as $key => $sheet)
						$sheets[] = array(
							'id'		 => $key,
							'name'		 => $sheet,
							'selected'	 => $sheet_id == $key
						);
				}

				$spreadsheet->setActiveSheetIndex((int)$sheet_id);
			}
			catch (Exception $e)
			{
				if ($e)
				{
					Cache::message_set($e->getMessage(), 'error');
					Cache::session_clear('property', 'condition_survey_import_file');
					unlink($cached_file);
				}
			}
		}

		$survey = $this->bo->read_single(array('id' => $id, 'view' => $mode == 'view'));

		$rows				 = $spreadsheet->getActiveSheet()->getHighestRow();
		$highestColumn		 = $spreadsheet->getActiveSheet()->getHighestColumn();
		$highestColumnIndex	 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

		$i			 = 0;
		$html_table	 = '<table border="1">';
		if ($rows > 1 && $step == 2)
		{

			$cols = array();
			for ($j = 1; $j <= $highestColumnIndex; $j++)
			{
				$cols[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j);
			}

			$html_table .= "<tr><th align = 'center'>" . lang('start') . "</th><th align='center'>" . implode("</th><th align='center'>", $cols) . '</th></tr>';
			foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row)
			{
				if ($i > 20)
				{
					break;
				}
				$i++;

				$row_key	 = $i;
				$_checked	 = '';
				if ($start_line == $row_key)
				{
					$_checked = 'checked="checked"';
				}

				$_radio = "[{$row_key}]<input id=\"start_line\" type =\"radio\" {$_checked} name=\"start_line\" value=\"{$row_key}\">";

				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells(false);

				$row_values = array();
				foreach ($cellIterator as $cell)
				{
					if (!is_null($cell))
					{
						$row_values[] = $cell->getCalculatedValue();
					}
				}
				$html_table .= "<tr><td><pre>{$_radio}</pre></td><td>" . implode('</td><td>', $row_values) . '</td></tr>';
			}
			echo '</table>';
		}
		else if ($rows > 1 && $step == 3)
		{
			$_options = array(
				'_skip_import_'				 => 'Utelates fra import/implisitt',
				'import_type'				 => 'import type',
				'building_part'				 => 'bygningsdels kode',
				'descr'						 => 'Tilstandbeskrivelse',
				'title'						 => 'Tiltak/overskrift',
				'condition_degree'			 => 'Tilstandsgrad',
				'condition_type'			 => 'Konsekvenstype',
				'consequence'				 => 'Konsekvensgrad',
				'probability'				 => 'Sannsynlighet',
				'due_year'					 => 'År (innen)',
				'amount_investment'			 => 'Beløp investering',
				'amount_operation'			 => 'Beløp drift',
				'amount_potential_grants'	 => 'Potensial for offentlig støtte',
			);

			$custom		 = createObject('phpgwapi.custom_fields');
			$attributes	 = $custom->find('property', '.project.request', 0, '', '', '', true, true);

			foreach ($attributes as $attribute)
			{
				$_options["custom_attribute_{$attribute['id']}"] = $attribute['input_text'];
			}

			phpgw::import_class('phpgwapi.sbox');

			for ($j = 0; $j < $highestColumnIndex; $j++)
			{
				$_column	 = $this->getexcelcolumnname($j);
				$_value		 = $spreadsheet->getActiveSheet()->getCell([$j + 1, $start_line])->getCalculatedValue();
				$selected	 = isset($columns[$_column]) && $columns[$_column] ? $columns[$_column] : '';

				$_listbox	 = phpgwapi_sbox::getArrayItem("columns[{$_column}]", $selected, $_options, true);
				$html_table	 .= "<tr><td>[{$_column}] {$_value}</td><td>{$_listbox}</td><tr>";
			}
		}
		else if ($rows > 1 && $step == 4)
		{

			$rows	 = $spreadsheet->getActiveSheet()->getHighestDataRow();
			$rows	 = $rows ? $rows : 1;

			$import_data = array();

			for ($i = $start_line; $i <= $rows; $i++)
			{
				$_result = array();

				foreach ($columns as $_row_key => $_value_key)
				{
					if ($_value_key != '_skip_import_')
					{
						$_result[$_value_key] = $spreadsheet->getActiveSheet()->getCell("{$_row_key}{$i}")->getCalculatedValue();
					}
				}
				$import_data[] = $_result;
			}
			if ($import_data)
			{
				try
				{
					$this->bo->import($survey, $import_data);
				}
				catch (Exception $e)
				{
					if ($e)
					{
						Cache::message_set($e->getMessage(), 'error');
					}
				}
			}

			//				$msg = "'{$cached_file}' contained " . count($import_data) . " lines";
			//				Cache::message_set($msg, 'message'); 
		}



		$html_table .= '</table>';




		if (isset($survey['location_code']) && $survey['location_code'])
		{
			$survey['location_data'] = execMethod('property.solocation.read_single', $survey['location_code']);
		}

		$bolocation		 = CreateObject('property.bolocation');
		$location_data	 = $bolocation->initiate_ui_location(array(
			'values'		 => $survey['location_data'],
			'type_id'		 => 2,
			'lookup_type'	 => 'view2',
			'tenant'		 => false,
			'lookup_entity'	 => array(),
			'entity_data'	 => isset($survey['p']) ? $survey['p'] : ''
		));

		$data = array(
			'lang_submit'	 => $lang_submit,
			'survey'		 => $survey,
			'location_data2' => $location_data,
			'step'			 => $step + 1,
			'sheet_id'		 => $sheet_id,
			'start_line'	 => $start_line,
			'html_table'	 => $html_table,
			'sheets'		 => array('options' => $sheets),
			'tabs'			 => $this->phpgwapi_common->create_tabs($tabs, $active_tab),
		);

		$this->flags['app_header'] = lang('property') . '::' . lang('condition survey import');
		Settings::getInstance()->update('flags', ['app_header' => $this->flags['app_header']]);

		self::render_template_xsl(array('condition_survey_import'), $data);
	}

	/**
	 * Get excel column name
	 * @param index : a column index we want to get the value in excel column format
	 * @return (string) : excel column format
	 */
	private function getexcelcolumnname($index)
	{
		return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
	}

	/**
	 * Gets user candidates to be used as coordinator - called as ajax from edit form
	 *
	 * @param string  $query
	 *
	 * @return array
	 */
	public function get_users()
	{
		if (!$this->acl_read)
		{
			return;
		}

		$query = Sanitizer::get_var('query');

		$accounts_obj = new Accounts();
		$accounts = $accounts_obj->get_list('accounts', $start=0, $sort='', $order='', $query, $offset=0);

		$values = array();
		foreach ($accounts as $account)
		{
			if ($account->enabled)
			{
				$values[] = array(
					'id'	 => $account->id,
					'name'	 => $account->__toString(),
				);
			}
		}
		return array('ResultSet' => array('Result' => $values));
	}

	/**
	 * Gets vendor canidated to be used as vendor - called as ajax from edit form
	 *
	 * @param string  $query
	 *
	 * @return array
	 */
	public function get_vendors()
	{
		if (!$this->acl_read)
		{
			return;
		}

		$query = Sanitizer::get_var('query');

		$sogeneric	 = CreateObject('property.sogeneric', 'vendor');
		$values		 = $sogeneric->read(array('query' => $query));
		foreach ($values as &$entry)
		{
			$entry['name'] = $entry['org_name'];
		}
		return array('ResultSet' => array('Result' => $values));
	}

	/**
	 * Edit title fo entity directly from table
	 *
	 * @param int  $id  id of entity
	 * @param string  $value new title of entity
	 *
	 * @return string text to appear in ui as receipt on action
	 */
	public function edit_survey_title()
	{
		$id = Sanitizer::get_var('id', 'int', 'GET');

		if (!$this->acl_edit)
		{
			return lang('no access');
		}

		if ($id)
		{
			$values			 = $this->bo->read_single(array('id' => $id, 'view' => true));
			$values['title'] = Sanitizer::get_var('value');

			try
			{
				$this->bo->edit_title($values);
			}
			catch (Exception $e)
			{
				if ($e)
				{
					return $e->getMessage();
				}
			}
			return 'OK';
		}
	}

	/**
	 * Delete survey and all related info
	 *
	 * @param int  $id  id of entity
	 *
	 * @return string text to appear in ui as receipt on action
	 */
	public function delete()
	{
		if (!$this->acl->check('.admin', ACL_DELETE, 'property'))
		{
			return 'No access';
		}
		$id = Sanitizer::get_var('id', 'int', 'GET');

		try
		{
			$this->bo->delete($id);
		}
		catch (Exception $e)
		{
			if ($e)
			{
				$result = array(
					'status'	 => 'deleted',
					'receipt'	 => $e->getMessage()
				);
				return $result;
			}
		}

		$result = array(
			'status'	 => 'deleted',
			'receipt'	 => lang('deleted') . " {$id}"
		);

		return $result;
	}

	/**
	 * Delete related requests only
	 *
	 * @param int  $id  id of entity
	 *
	 * @return string text to appear in ui as receipt on action
	 */
	public function delete_imported_records()
	{
		if (!$this->acl->check('.admin', ACL_DELETE, 'property'))
		{
			return 'No access';
		}
		$id = Sanitizer::get_var('id', 'int', 'GET');

		try
		{
			$this->bo->delete_imported_records($id);
		}
		catch (Exception $e)
		{
			if ($e)
			{
				return $e->getMessage();
			}
		}
		return 'Deleted';
	}

	/**
	 * Prepare data for summation - single survey or all
	 *
	 * @return void
	 */
	public function summation()
	{
		$this->flags['menu_selection'] = "property::project::condition_survey::summation";
		Settings::getInstance()->update('flags', ['menu_selection' => $this->flags['menu_selection']]);

		if (!$this->acl_read)
		{
			phpgw::redirect_link('/index.php', array('menuaction' => 'property.uidocument2.index'));
		}

		$params = array(
			'start'		 => 0,
			'sort'		 => 'id',
			'dir'		 => 'asc',
			'cat_id'	 => 0,
			'allrows'	 => true
		);

		$survey_list = $this->bo->read($params);

		$surveys	 = array();
		$surveys[]	 = array(
			'id'	 => 0,
			'name'	 => lang('select'),
		);
		$surveys[]	 = array(
			'id'	 => -1,
			'name'	 => lang('all'),
		);

		foreach ($survey_list as $survey)
		{
			$surveys[] = array(
				'id'			 => $survey['id'],
				'name'			 => $survey['title'],
				'description'	 => $survey['address'],
			);
		}



		$current_year = date('Y');

		$years = array();

		for ($i = 0; $i < 6; $i++)
		{
			$years[] = array(
				'id'	 => $current_year,
				'name'	 => $current_year
			);
			$current_year++;
		}

		$summation_def = array(
			array(
				'key'		 => 'building_part', 'label'		 => lang('building part'), 'sortable'	 => false,
				'resizeable' => true
			),
			array(
				'key'		 => 'category', 'label'		 => lang('category'), 'sortable'	 => false,
				'resizeable' => true
			),
			array(
				'key'		 => 'period_1', 'label'		 => lang('year') . ':: < 1', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'period_2', 'label'		 => lang('year') . ':: 1 - 5', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'period_3', 'label'		 => lang('year') . ':: 6 - 10', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'period_4', 'label'		 => lang('year') . ':: 11 - 15', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'period_5', 'label'		 => lang('year') . ':: 16 - 20', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'period_6', 'label'		 => lang('year') . ':: 21 +', 'sortable'	 => false,
				'resizeable' => true, 'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
			array(
				'key'		 => 'sum', 'label'		 => lang('sum'), 'sortable'	 => false, 'resizeable' => true,
				'formatter'	 => 'YAHOO.portico.FormatterAmount0'
			),
		);

		$datatable_def	 = array();
		$datatable_def[] = array(
			'container'	 => 'datatable-container_0',
			'requestUrl' => json_encode(self::link(array(
				'menuaction'		 => 'property.uidocument2.get_summation',
				'id'				 => $id, 'phpgw_return_as'	 => 'json'
			))),
			'ColumnDefs' => $summation_def
		);

		$data = array(
			'datatable_def'	 => $datatable_def,
			'surveys'		 => array('options' => $surveys),
			'years'			 => array('options' => $years),
		);

		$this->flags['app_header'] = lang('property') . '::' . lang('condition survey');
		Settings::getInstance()->update('flags', ['app_header' => $this->flags['app_header']]);


		self::render_template_xsl(array('condition_survey_summation'), $data);
	}
	/*
		 * Overrides with incoming data from POST
		 */

	private function _populate($data = array())
	{
		$insert_record = Cache::session_get('property', 'insert_record');

		$values = Sanitizer::get_var('values');

		$_fields = array(
			array(
				'name'		 => 'title',
				'type'		 => 'string',
				'required'	 => true
			),
			array(
				'name'		 => 'descr',
				'type'		 => 'string',
				'required'	 => true
			),
			array(
				'name'		 => 'cat_id',
				'type'		 => 'integer',
				'required'	 => true
			),
			array(
				'name'		 => 'report_date',
				'type'		 => 'string',
				'required'	 => true
			),
			array(
				'name'		 => 'status_id',
				'type'		 => 'integer',
				'required'	 => true
			),
			array(
				'name'		 => 'vendor_id',
				'type'		 => 'integer',
				'required'	 => false
			),
			array(
				'name'		 => 'vendor_name',
				'type'		 => 'string',
				'required'	 => false
			),
			array(
				'name'		 => 'coordinator_id',
				'type'		 => 'integer',
				'required'	 => false
			),
			array(
				'name'		 => 'coordinator_name',
				'type'		 => 'string',
				'required'	 => false
			),
			array(
				'name'		 => 'multiplier',
				'type'		 => 'float',
				'required'	 => false
			),
		);


		foreach ($_fields as $_field)
		{
			if ($data[$_field['name']] = $_POST['values'][$_field['name']])
			{
				$data[$_field['name']] = Sanitizer::clean_value($data[$_field['name']], $_field['type']);
			}
			if ($_field['required'] && !$data[$_field['name']])
			{
				$this->receipt['error'][] = array('msg' => lang('Please enter value for attribute %1', $_field['name']));
			}
		}

		//_debug_array($data);die();

		$values = $this->bocommon->collect_locationdata($data, $insert_record);

		if (!isset($values['location_code']) || !$values['location_code'])
		{
			$this->receipt['error'][] = array('msg' => lang('Please select a location !'));
		}

		/*
			 * Extra data from custom fields
			 */
		$values['attributes'] = Sanitizer::get_var('values_attribute');

		if (is_array($values['attributes']))
		{
			foreach ($values['attributes'] as $attribute)
			{
				if ($attribute['nullable'] != 1 && (!$attribute['value'] && !$values['extra'][$attribute['name']]))
				{
					$this->receipt['error'][] = array('msg' => lang('Please enter value for attribute %1', $attribute['input_text']));
				}
			}
		}

		if (!isset($values['cat_id']) || !$values['cat_id'])
		{
			$this->receipt['error'][] = array('msg' => lang('Please select a category !'));
		}

		if (!isset($values['title']) || !$values['title'])
		{
			$this->receipt['error'][] = array('msg' => lang('Please give a title !'));
		}

		if (!isset($values['report_date']) || !$values['report_date'])
		{
			$this->receipt['error'][] = array('msg' => lang('Please select a date!'));
		}

		return $values;
	}

	private function _get_categories($selected = 0)
	{
		$cats				 = CreateObject('phpgwapi.categories', -1, 'property', $this->acl_location);
		$cats->supress_info	 = true;
		$categories			 = $cats->formatted_xslt_list(array(
			'format'	 => 'filter', 'selected'	 => $selected,
			'globals'	 => true, 'use_acl'	 => $this->_category_acl
		));
		$default_value		 = array('cat_id' => '', 'name' => lang('no category'));
		array_unshift($categories['cat_list'], $default_value);

		foreach ($categories['cat_list'] as &$_category)
		{
			$_category['id'] = $_category['cat_id'];
		}

		return $categories['cat_list'];
	}
}
