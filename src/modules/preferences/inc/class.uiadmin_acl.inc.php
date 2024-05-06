<?php

/**
 * phpGroupWare - preferences - Advanced Access Control Lists Management User Interface
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2003-2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
 * @package preferences
 * @subpackage acl
 * @version $Id$
 */

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\security\Acl;
use App\modules\phpgwapi\controllers\Accounts\Accounts;

phpgw::import_class('phpgwapi.jquery');

/**
 * Advanced Access Control Lists Management User Interface
 * @package preferences
 * @subpackage acl
 */

class preferences_uiadmin_acl
{
	var $grants;
	var $cat_id;
	var $start;
	var $query;
	var $sort;
	var $order;
	var $filter;
	var $submodule_id;
	var $permission;
	var $sub;
	var $currentapp, $bo, $account, $acl_app, $location, $granting_group, $allrows;
	/**
	 * @var object $nextmatchss pager object
	 */
	protected $nextmatchs;

	var $public_functions = array(
		'list_acl'		=> True,
		'aclprefs'		=> True
	);

	private
		$flags,
		$userSettings,
		$acl,
		$phpgwapi_common,
		$accounts;


	/**
	 * @constructor
	 */
	function __construct()
	{
		$this->flags = Settings::getInstance()->get('flags');
		$this->userSettings = Settings::getInstance()->get('user');

		$this->flags['xslt_app'] = true;
		$this->flags['currentapp'] = 'preferences';
		$this->currentapp		= $this->flags['currentapp'];
		$this->nextmatchs		= CreateObject('phpgwapi.nextmatchs');
		$this->account			= $this->userSettings['account_id'];

		$this->bo				= createObject('preferences.boadmin_acl', true);

		$this->acl_app			= $this->bo->acl_app;
		$this->start			= $this->bo->start;
		$this->query			= $this->bo->query;
		$this->sort				= $this->bo->sort;
		$this->order			= $this->bo->order;
		$this->filter			= $this->bo->filter;
		$this->cat_id			= $this->bo->cat_id;
		$this->location			= $this->bo->location;
		$this->granting_group	= $this->bo->granting_group;
		$this->allrows			= $this->bo->allrows;
		$this->phpgwapi_common = new phpgwapi_common();
		$this->accounts = new Accounts();



		$this->flags['menu_selection'] = "admin::{$this->acl_app}::acl";
		Settings::getInstance()->set('flags', $this->flags);
		$this->acl = Acl::getInstance();


		$is_admin = $this->acl->check('run', Acl::READ, 'admin');
		$local_admin = false;
		if (!$is_admin)
		{
			if ($this->acl->check('admin', Acl::ADD, $this->acl_app))
			{
				$local_admin = true;
			}
		}

		if (!$is_admin && !$local_admin)
		{
			phpgw::no_access();
		}
	}

	function save_sessiondata()
	{
		$data = array(
			'start'			=> $this->start,
			'query'			=> $this->query,
			'sort'			=> $this->sort,
			'order'			=> $this->order,
			'filter'		=> $this->filter,
			'cat_id'		=> $this->cat_id,
			'location'		=> $this->location,
			'granting_group'	=> $this->granting_group,
			'allrows'		=> $this->allrows
		);

		$this->bo->save_sessiondata($data);
	}

	function aclprefs()
	{
		phpgwapi_xslttemplates::getInstance()->add_file(array(
			'admin_acl', 'nextmatchs',
			'search_field'
		));

		$values 	= Sanitizer::get_var('values', 'string', 'POST');
		$r_processed	= Sanitizer::get_var('processed', 'string', 'POST');
		$set_permission = Sanitizer::get_var('set_permission', 'string', 'POST');

		if ($set_permission)
		{
			$receipt	= $this->bo->set_permission($values, $r_processed, true);
		}

		$processed = array();
		if ($this->location)
		{
			if ($this->cat_id == 'accounts')
			{
				$user_list = $this->bo->get_user_list('accounts', true);
			}

			if (isset($user_list) && is_array($user_list))
			{
				//while (is_array($user_list) && list(,$user) = each($user_list))
				foreach ($user_list as $key => $user)
				{
					$processed[] = $user['account_id'];
					$users[] = array(
						'account_id'	=> $user['account_id'],
						'lid'			=> $user['account_lid'],
						'name'			=> $user['account_firstname'] . ' ' . $user['account_lastname'],
						'read_right'	=> isset($user['right'][ACL_READ]) ? $user['right'][ACL_READ] : false,
						'add_right'		=> isset($user['right'][ACL_ADD]) ? $user['right'][ACL_ADD] : false,
						'edit_right'	=> isset($user['right'][ACL_EDIT]) ? $user['right'][ACL_EDIT] : false,
						'delete_right'	=> isset($user['right'][ACL_DELETE]) ? $user['right'][ACL_DELETE] : false,
						'read_mask'		=> isset($user['mask'][ACL_READ]) ? $user['mask'][ACL_READ] : false,
						'add_mask'		=> isset($user['mask'][ACL_ADD]) ? $user['mask'][ACL_ADD] : false,
						'edit_mask'		=> isset($user['mask'][ACL_EDIT]) ? $user['mask'][ACL_EDIT] : false,
						'delete_mask'	=> isset($user['mask'][ACL_DELETE]) ? $user['mask'][ACL_DELETE] : false,
						'read_result'	=> isset($user['result'][ACL_READ]) ? $user['result'][ACL_READ] : false,
						'add_result'	=> isset($user['result'][ACL_ADD]) ? $user['result'][ACL_ADD] : false,
						'edit_result'	=> isset($user['result'][ACL_EDIT]) ? $user['result'][ACL_EDIT] : false,
						'delete_result'	=> isset($user['result'][ACL_DELETE]) ? $user['result'][ACL_DELETE] : false,
						'lang_right'	=> lang('right'),
						'lang_mask'		=> lang('mask'),
						'lang_result'	=> lang('result'),
						'lang_read'		=> lang('Read'), 				//1
						'lang_add'		=> lang('Add'), 				//2
						'lang_edit'		=> lang('Edit'),				//4
						'lang_delete'	=> lang('Delete'),				//8
						'type'			=> 'users'
					);
				}
			}

			if ($this->cat_id == 'groups')
			{
				$group_list = $this->bo->get_user_list('groups', true);
			}

			if (isset($group_list) && is_array($group_list))
			{
				//while (is_array($group_list) && list(,$group) = each($group_list))
				foreach ($group_list as $key => $group)
				{
					$processed[] = $group['account_id'];
					$groups[] = array(
						'account_id'	=> $group['account_id'],
						'lid'			=> $group['account_lid'],
						'name'			=> $group['account_firstname'],
						'read_right'	=> isset($group['right'][ACL_READ]) ? $group['right'][ACL_READ] : false,
						'add_right'		=> isset($group['right'][ACL_ADD]) ? $group['right'][ACL_ADD] : false,
						'edit_right'	=> isset($group['right'][ACL_EDIT]) ? $group['right'][ACL_EDIT] : false,
						'delete_right'	=> isset($group['right'][ACL_DELETE]) ? $group['right'][ACL_DELETE] : false,
						'read_mask'		=> isset($group['mask'][ACL_READ]) ? $group['mask'][ACL_READ] : false,
						'add_mask'		=> isset($group['mask'][ACL_ADD]) ? $group['mask'][ACL_ADD] : false,
						'edit_mask'		=> isset($group['mask'][ACL_EDIT]) ? $group['mask'][ACL_EDIT] : false,
						'delete_mask'	=> isset($group['mask'][ACL_DELETE]) ? $group['mask'][ACL_DELETE] : false,
						'read_result'	=> isset($group['result'][ACL_READ]) ? $group['result'][ACL_READ] : false,
						'add_result'	=> isset($group['result'][ACL_ADD]) ? $group['result'][ACL_ADD] : false,
						'edit_result'	=> isset($group['result'][ACL_EDIT]) ? $group['result'][ACL_EDIT] : false,
						'delete_result'	=> isset($group['result'][ACL_DELETE]) ? $group['result'][ACL_DELETE] : false,
						'lang_right'	=> lang('right'),
						'lang_mask'		=> lang('mask'),
						'lang_result'	=> lang('result'),
						'lang_read'		=> lang('Read'), 				//1
						'lang_add'		=> lang('Add'), 				//2
						'lang_edit'		=> lang('Edit'),				//4
						'lang_delete'	=> lang('Delete'),				//8
						'type'			=> 'groups'
					);
				}
			}
			//_debug_array($groups);
			$processed = implode("_", $processed);
		}


		$table_header[] = array(
			'lang_read'		=> lang('Read'), 				//1
			'lang_add'		=> lang('Add'), 				//2
			'lang_edit'		=> lang('Edit'),				//4
			'lang_delete'		=> lang('Delete'),				//8
			'lang_manager'		=> lang('Manager')				//16
		);


		$link_data = array(
			'menuaction'		=> $this->currentapp . '.uiadmin_acl.aclprefs',
			'sort'			=> $this->sort,
			'order'			=> $this->order,
			'cat_id'		=> $this->cat_id,
			'filter'		=> $this->filter,
			'query'			=> $this->query,
			'module'		=> $this->location,
			'granting_group'	=> $this->granting_group,
			'acl_app'		=> $this->acl_app
		);

		if (!$this->location)
		{
			$receipt['error'][] = array('msg' => lang('select a location!'));
		}

		$num_records = 0;
		if (isset($user_list) && is_array($user_list))
		{
			$num_records = count($user_list);
		}
		if (isset($group_list) && is_array($group_list))
		{
			$num_records = $num_records + count($group_list);
		}

		$msgbox_data = (isset($receipt) ? $this->phpgwapi_common->msgbox_data($receipt) : '');

		$nm = array(
			'link_data'		 => $link_data,
			'query'			 => $this->query,
			'allrows'		 => $this->allrows,
			'allow_allrows'	 => false,
			'start'			 => $this->start,
			'record_limit'	 => $this->userSettings['preferences']['common']['maxmatchs'],
			'num_records'	 => $num_records,
			'all_records'	 => $this->bo->total_records,
		);

		$data = array(
			'search_access' => true,
			'nm_data'						=> $this->nextmatchs->xslt_nm($nm),
			'search_data'					=> $this->nextmatchs->xslt_search(array('query' => $this->query, 'link_data' => $link_data)),
			'msgbox_data'					=> $this->phpgwapi_common->msgbox($msgbox_data),
			'form_action'					=> phpgw::link('/index.php', $link_data),
			'done_action'					=> phpgw::link('/preferences/index.php'),
			'lang_save'						=> lang('save'),
			'lang_done'						=> lang('done'),
			'processed'						=> (isset($processed) ? $processed : ''),
			'location'						=> $this->location,
			'link_url'						=> phpgw::link('/index.php', $link_data),
			'img_path'						=> $this->phpgwapi_common->get_image_path('phpgwapi', 'default'),

			'lang_groups'					=> lang('groups'),
			'lang_users'					=> lang('users'),
			'lang_no_cat'					=> lang('no category'),
			'lang_cat_statustext'			=> lang('Select the category the permissions belongs to. To do not use a category select NO CATEGORY'),
			'select_name'					=> 'cat_id',
			'cat_list'						=> $this->bo->select_category_list('filter', $this->cat_id),
			'select_action'					=> phpgw::link('/index.php', $link_data),
			'cat_id'						=> $this->cat_id,
			'permission'					=> False,
			'grant'							=> 1,

			'lang_searchfield_statustext'	=> lang('Enter the search string. To show all entries, empty this field and press the SUBMIT button again'),
			'lang_searchbutton_statustext'	=> lang('Submit the search string'),
			'lang_search'					=> lang('search'),
			'table_header_permission'		=> $table_header,
			'values_groups'					=> (isset($groups) ? $groups : ''),
			'values_users'					=> (isset($users) ? $users : ''),
			'lang_no_location'				=> lang('No location'),
			'lang_location_statustext'		=> lang('Select submodule'),
			'select_name_location'			=> 'module',
			'location_list'					=> $this->bo->select_location('filter', $this->location, True),

			'is_admin'						=> $this->userSettings['apps']['admin'],
			'lang_group_statustext'			=> lang('Select the granting group. To do not use a granting group select NO GRANTING GROUP'),
			'select_group_name'				=> 'granting_group',
			'lang_no_group'					=> lang('No granting group'),
			'group_list'					=> $this->bo->get_group_list('filter', $this->granting_group, $start = -1, $sort = 'ASC', $order = 'account_firstname', $query = '', $offset = -1),
			'lang_enable_inheritance'       => lang('enable inheritance'),
			'lang_enable_inheritance_statustext'        => lang('rights are inherited down the hierarchy')
		);

		$appname			= lang('preferences');
		$function_msg		= lang('set grants');
		$owner_name 		= $this->accounts->id2name($this->account);		// get owner name for title
		phpgwapi_jquery::load_widget('select2');

		$this->flags['app_header'] = lang('admin') . ' - ' . $this->acl_app . ': ' . $function_msg . ': ' . $owner_name;
		Settings::getInstance()->set('flags', $this->flags);

		phpgwapi_xslttemplates::getInstance()->set_var('phpgw', array('list_permission' => $data));
		$this->save_sessiondata();
	}

	function list_acl()
	{
		phpgwapi_xslttemplates::getInstance()->add_file(array('admin_acl', 'nextmatchs', 'search_field'));

		$values 		= Sanitizer::get_var( 'values', 'string', 'POST');
		$r_processed	= Sanitizer::get_var( 'processed', 'string', 'POST');

		$set_permission = Sanitizer::get_var( 'set_permission', 'string', 'POST');

		if ($set_permission)
		{
			$receipt = $this->bo->set_permission($values, $r_processed);
		}

		$processed = array();
		$user_list = array();
		$group_list = array();
		$users = array();
		$groups = array();
		if ($this->location)
		{
			if ($this->cat_id == 'accounts')
			{
				$user_list = $this->bo->get_user_list('accounts');
			}

			if (isset($user_list) && is_array($user_list))
			{
				foreach ($user_list as $user)
				{
					$processed[] = $user['account_id'];
					$users[] = array(
						'account_id'	=> $user['account_id'],
						'lid'			=> $user['account_lid'],
						'name'			=> $user['account_firstname'] . ' ' . $user['account_lastname'] . ' [' . $user['account_lid'] . ']',
						'read_right'	=> isset($user['right'][ACL_READ]) ? $user['right'][ACL_READ] : false,
						'add_right'		=> isset($user['right'][ACL_ADD]) ? $user['right'][ACL_ADD] : false,
						'edit_right'	=> isset($user['right'][ACL_EDIT]) ? $user['right'][ACL_EDIT] : false,
						'delete_right'	=> isset($user['right'][ACL_DELETE]) ? $user['right'][ACL_DELETE] : false,
						'manage_right'	=> isset($user['right'][ACL_PRIVATE]) ? $user['right'][ACL_PRIVATE] : false, //should be ACL_GROUP_MANAGERS
						'read_mask'		=> isset($user['mask'][ACL_READ]) ? $user['mask'][ACL_READ] : false,
						'add_mask'		=> isset($user['mask'][ACL_ADD]) ? $user['mask'][ACL_ADD] : false,
						'edit_mask'		=> isset($user['mask'][ACL_EDIT]) ? $user['mask'][ACL_EDIT] : false,
						'delete_mask'	=> isset($user['mask'][ACL_DELETE]) ? $user['mask'][ACL_DELETE] : false,
						'manage_mask'	=> isset($user['mask'][ACL_PRIVATE]) ? $user['mask'][ACL_PRIVATE] : false, //should be ACL_GROUP_MANAGERS
						'read_result'	=> isset($user['result'][ACL_READ]) ? $user['result'][ACL_READ] : false,
						'add_result'	=> isset($user['result'][ACL_ADD]) ? $user['result'][ACL_ADD] : false,
						'edit_result'	=> isset($user['result'][ACL_EDIT]) ? $user['result'][ACL_EDIT] : false,
						'delete_result'	=> isset($user['result'][ACL_DELETE]) ? $user['result'][ACL_DELETE] : false,
						'manage_result'	=> isset($user['result'][ACL_PRIVATE]) ? $user['result'][ACL_PRIVATE] : false, //should be ACL_GROUP_MANAGERS
						'lang_right'	=> lang('right'),
						'lang_mask'		=> lang('mask'),
						'lang_result'	=> lang('result'),
						'lang_read'		=> lang('Read'), 				//1
						'lang_add'		=> lang('Add'), 				//2
						'lang_edit'		=> lang('Edit'),				//4
						'lang_delete'	=> lang('Delete'),				//8
						'lang_manage'	=> lang('Manage'),				//16
						'type'			=> 'users'
					);
				}
			}

			if ($this->cat_id == 'groups')
			{
				$group_list = $this->bo->get_user_list('groups');
			}

			if (isset($group_list) && is_array($group_list))
			{
				foreach ($group_list as $group)
				{
					$processed[] = $group['account_id'];
					$groups[] = array(
						'account_id'	=> $group['account_id'],
						'lid'			=> $group['account_lid'],
						'name'			=> $group['account_firstname'],
						'read_right'	=> isset($group['right'][ACL_READ]) ? $group['right'][ACL_READ] : false,
						'add_right'		=> isset($group['right'][ACL_ADD]) ? $group['right'][ACL_ADD] : false,
						'edit_right'	=> isset($group['right'][ACL_EDIT]) ? $group['right'][ACL_EDIT] : false,
						'delete_right'	=> isset($group['right'][ACL_DELETE]) ? $group['right'][ACL_DELETE] : false,
						'manage_right'	=> isset($group['right'][ACL_PRIVATE]) ? $group['right'][ACL_PRIVATE] : false, //should be ACL_GROUP_MANAGERS
						'read_mask'		=> isset($group['mask'][ACL_READ]) ? $group['mask'][ACL_READ] : false,
						'add_mask'		=> isset($group['mask'][ACL_ADD]) ? $group['mask'][ACL_ADD] : false,
						'edit_mask'		=> isset($group['mask'][ACL_EDIT]) ? $group['mask'][ACL_EDIT] : false,
						'delete_mask'	=> isset($group['mask'][ACL_DELETE]) ? $group['mask'][ACL_DELETE] : false,
						'manage_mask'	=> isset($group['mask'][ACL_PRIVATE]) ? $group['mask'][ACL_PRIVATE] : false, //should be ACL_GROUP_MANAGERS
						'read_result'	=> isset($group['result'][ACL_READ]) ? $group['result'][ACL_READ] : false,
						'add_result'	=> isset($group['result'][ACL_ADD]) ? $group['result'][ACL_ADD] : false,
						'edit_result'	=> isset($group['result'][ACL_EDIT]) ? $group['result'][ACL_EDIT] : false,
						'delete_result'	=> isset($group['result'][ACL_DELETE]) ? $group['result'][ACL_DELETE] : false,
						'manage_result'	=> isset($group['result'][ACL_PRIVATE]) ? $group['result'][ACL_PRIVATE] : false, //should be ACL_GROUP_MANAGERS
						'lang_right'	=> lang('right'),
						'lang_mask'		=> lang('mask'),
						'lang_result'	=> lang('result'),
						'lang_read'		=> lang('Read'), 				//1
						'lang_add'		=> lang('Add'), 				//2
						'lang_edit'		=> lang('Edit'),				//4
						'lang_delete'	=> lang('Delete'),				//8
						'lang_manage'	=> lang('Manage'),				//16
						'type'			=> 'groups'
					);
				}
			}
			$processed = implode('_', $processed);
		}

		$table_header[] = array(
			'sort_lid'	=> $this->nextmatchs->show_sort_order(array(
				'sort'	=> $this->sort,
				'var'	=>	'account_lid',
				'order'	=>	$this->order,
				'extra'	=> array(
					'menuaction'	=> $this->currentapp . '.uiadmin_acl.list_acl',
					'acl_app' 	=> $this->acl_app,
					'cat_id'	=> $this->cat_id,
					'query'		=> $this->query,
					'module'	=> $this->location,
					'submodule_id'	=> $this->submodule_id
				)
			)),
			'sort_lastname'	=> $this->nextmatchs->show_sort_order(array(
				'sort'	=>	$this->sort,
				'var'	=>	'account_lastname',
				'order'	=>	$this->order,
				'extra'	=>	array(
					'menuaction'	=> $this->currentapp . '.uiadmin_acl.list_acl',
					'acl_app' 	=> $this->acl_app,
					'cat_id'	=> $this->cat_id,
					'query'		=> $this->query,
					'module'	=> $this->location,
					'submodule_id'	=> $this->submodule_id
				)
			)),
			'sort_firstname'	=> $this->nextmatchs->show_sort_order(array(
				'sort'	=>	$this->sort,
				'var'	=>	'account_firstname',
				'order'	=>	$this->order,
				'extra'	=>	array(
					'menuaction'	=> $this->currentapp . '.uiadmin_acl.list_acl',
					'acl_app' 	=> $this->acl_app,
					'cat_id'	=> $this->cat_id,
					'query'		=> $this->query,
					'module'	=> $this->location,
					'submodule_id'	=> $this->submodule_id
				)
			)),


			'lang_values'				=> lang('values'),
			'lang_read'				=> lang('Read'), 				//1
			'lang_add'				=> lang('Add'), 				//2
			'lang_edit'				=> lang('Edit'),				//4
			'lang_delete'				=> lang('Delete'),				//8
			'lang_manager'				=> lang('Manager'),				//16
		);

		$link_data = array(
			'menuaction'	=> 'preferences.uiadmin_acl.list_acl',
			'acl_app' 		=> $this->acl_app,
			'sort'			=> $this->sort,
			'order'			=> $this->order,
			'cat_id'		=> $this->cat_id,
			'filter'		=> $this->filter,
			'query'			=> $this->query,
			'module'		=> $this->location

		);

		if (!$this->location)
		{
			$receipt['error'][] = array('msg' => lang('select a location!'));
		}

		if (!$this->allrows)
		{
			$record_limit	= $this->userSettings['preferences']['common']['maxmatchs'];
		}
		else
		{
			$record_limit	= $this->bo->total_records;
		}

		$num_records = count($user_list) + count($group_list);

		$msgbox_data = (isset($receipt) ? $this->phpgwapi_common->msgbox_data($receipt) : '');

		$nm = array(
			'link_data'		 => $link_data,
			'query'			 => $this->query,
			'allrows'		 => $this->allrows,
			'allow_allrows'	 => true,
			'start'			 => $this->start,
			'record_limit'	 => $record_limit,
			'num_records'	 => $num_records,
			'all_records'	 => $this->bo->total_records,
		);

		$data = array(
			'search_access' => true,
			'nm_data'						=> $this->nextmatchs->xslt_nm($nm),
			'search_data'					=> $this->nextmatchs->xslt_search(array('query' => $this->query, 'link_data' => $link_data)),

			'msgbox_data'					=> $this->phpgwapi_common->msgbox($msgbox_data),
			'form_action'					=> phpgw::link('/index.php', $link_data),
			'done_action'					=> phpgw::link('/admin/index.php'),
			'lang_save'						=> lang('save'),
			'lang_done'						=> lang('done'),
			'processed'						=> $processed,
			'location'						=> $this->location,

			'link_url'						=> phpgw::link('/index.php', $link_data),
			'img_path'						=> $this->phpgwapi_common->get_image_path('phpgwapi', 'default'),

			'lang_no_cat'					=> lang('no category'),
			'lang_cat_statustext'			=> lang('Select the category the permissions belongs to. To do not use a category select NO CATEGORY'),
			'select_name'					=> 'cat_id',
			'cat_list'						=> $this->bo->select_category_list('filter', $this->cat_id),
			'select_action'					=> phpgw::link('/index.php', $link_data),
			'cat_id'						=> $this->cat_id,
			'permission'					=> 1,

			'lang_searchfield_statustext'	=> lang('Enter the search string. To show all entries, empty this field and press the SUBMIT button again'),
			'lang_searchbutton_statustext'	=> lang('Submit the search string'),
			'lang_search'					=> lang('search'),
			'table_header_permission'		=> $table_header,
			'values_groups'					=> $groups,
			'values_users'					=> $users,
			'lang_groups'					=> lang('groups'),
			'lang_users'					=> lang('users'),

			'lang_no_location'				=> lang('No location'),
			'lang_location_statustext'		=> lang('Select submodule'),
			'select_name_location'			=> 'module',
			'location_list'					=> $this->bo->select_location('filter', $this->location, False),
			'lang_enable_inheritance'       => lang('enable inheritance'),
			'lang_enable_inheritance_statustext'        => lang('rights are inherited down the hierarchy')
		);

		$appname		= lang('permission');
		$function_msg		= lang('set permission');
		phpgwapi_jquery::load_widget('select2');

		$this->flags['app_header'] = lang('admin') . ' - ' . $this->acl_app . ': ' . $function_msg;
		Settings::getInstance()->set('flags', $this->flags);

		phpgwapi_xslttemplates::getInstance()->set_var('phpgw', array('list_permission' => $data));
		$this->save_sessiondata();
	}
}
