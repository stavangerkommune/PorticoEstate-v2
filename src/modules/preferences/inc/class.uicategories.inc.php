<?php

/**
 * Preferences - categories user interface
 *
 * @author Bettina Gille [ceb@phpgroupware.org]
 * @copyright Copyright (C) 2000-2003,2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package preferences
 * @version $Id$
 */

use App\modules\phpgwapi\services\Settings;
use App\helpers\Template;


/**
 * Categories user interface
 * 
 * @package preferences
 */
class preferences_uicategories
{
	var $nextmatchs, $account, $user;
	/**
	 * 
	 * @var object
	 */
	var $bo;

	/**
	 * 
	 * @var unknown
	 */
	var $start;

	/**
	 * 
	 * @var unknown
	 */
	var $query;

	/**
	 * 
	 * @var unknown
	 */
	var $sort;

	/**
	 * 
	 * @var unknown
	 */
	var $order;

	/**
	 * 
	 * @var string
	 */
	var $cat_id;

	/**
	 * 
	 * @var string
	 */
	var $cats_app;

	/**
	 * 
	 * @var array
	 */
	var $public_functions = array(
		'index'  => True,
		'add'    => True,
		'edit'   => True,
		'delete' => True
	);

	private $template, $flags, $phpgwapi_common;

	/**
	 * Constructor
	 */
	function __construct()
	{
		$cats_app			= Sanitizer::get_var('cats_app');

		$this->bo			= CreateObject('preferences.bocategories', $cats_app);
		$this->nextmatchs	= CreateObject('phpgwapi.nextmatchs');
		$this->template  = Template::getInstance();

		$userSettings		= Settings::getInstance()->get('user');
		$this->flags 		= Settings::getInstance()->get('flags');
		$this->phpgwapi_common = new phpgwapi_common();

		$this->account		= $userSettings['account_id'];
		$this->user			= $userSettings['fullname'];

		$this->start = $this->bo->start;
		$this->query = $this->bo->query;
		$this->sort  = $this->bo->sort;
		$this->order = $this->bo->order;
	}

	/**
	 * Save session data
	 * 
	 * @param $cats_app
	 */
	function save_sessiondata($cats_app)
	{
		$data = array(
			'start' => $this->start,
			'query' => $this->query,
			'sort'  => $this->sort,
			'order' => $this->order
		);
		$this->bo->save_sessiondata($data, $cats_app);
	}

	/**
	 * Set languages
	 */
	function set_langs()
	{
		$this->template->set_var('lang_access', lang('Private'));
		$this->template->set_var('lang_save', lang('Save'));
		$this->template->set_var('user_name', $this->user);
		$this->template->set_var('lang_search', lang('Search'));
		$this->template->set_var('lang_cancel', lang('Cancel'));
		$this->template->set_var('lang_done', lang('done'));
		$this->template->set_var('lang_sub', lang('Add sub'));
		$this->template->set_var('lang_edit', lang('Edit'));
		$this->template->set_var('lang_delete', lang('Delete'));
		$this->template->set_var('lang_parent', lang('Parent category'));
		$this->template->set_var('lang_none', lang('None'));
		$this->template->set_var('lang_name', lang('Name'));
		$this->template->set_var('lang_descr', lang('Description'));
		$this->template->set_var('lang_add', lang('Add'));
		$this->template->set_var('lang_reset', lang('Clear Form'));
	}

	/**
	 * Display data elements in td
	 * 
	 * @param array $edata
	 * @param array $data
	 * @return string td element string
	 */
	function cat_data($edata, $data)
	{
		$td_data = '';
		for ($j = 0; $j < count($edata); ++$j)
		{
			$td_data .= '<td>' . $data[$edata[$j]] . '</td>' . "\n";
		}
		return $td_data;
	}

	/**
	 * 
	 */
	function index()
	{
		$cats_app    = Sanitizer::get_var('cats_app');
		$extra       = Sanitizer::get_var('extra');
		$global_cats = Sanitizer::get_var('global_cats');
		$cats_level  = Sanitizer::get_var('cats_level');

		$link_data = array(
			'menuaction'  => 'preferences.uicategories.index',
			'cats_app'    => $cats_app,
			'extra'       => $extra,
			'global_cats' => $global_cats,
			'cats_level'  => $cats_level
		);

		$edata = array();
		if ($extra)
		{
			$edata = explode(',', $extra);
		}
		$this->flags['app_header'] = $GLOBALS['phpgw_info']['apps'][$cats_app]['title'] .
			'&nbsp;' . lang('categories for') . ':&nbsp;' . $this->user;
		Settings::getInstance()->set('flags', $this->flags);
		$this->phpgwapi_common->phpgw_header(true);
		$this->template->set_root(PHPGW_APP_TPL);
		$this->template->set_file('cat_list_t', 'listcats.tpl');
		$this->template->set_block('cat_list_t', 'data_column', 'column');
		$this->template->set_block('cat_list_t', 'cat_list', 'list');

		$this->set_langs();

		$this->template->set_var('title_categories', lang('categories for'));
		$this->template->set_var('lang_app', lang($cats_app));
		$this->template->set_var('actionurl', phpgw::link('/index.php', $link_data));
		$this->template->set_var('doneurl', phpgw::link('/preferences/index.php'));

		if (!$this->start)
		{
			$this->start = 0;
		}

		if (!$global_cats)
		{
			$global_cats = False;
		}

		$cats = $this->bo->get_list($global_cats);

		//--------------------------------- nextmatch --------------------------------------------

		$left  = $this->nextmatchs->left('/index.php', $this->start, $this->bo->cats->total_records, $link_data);
		$right = $this->nextmatchs->right('/index.php', $this->start, $this->bo->cats->total_records, $link_data);
		$this->template->set_var('left', $left);
		$this->template->set_var('right', $right);

		$this->template->set_var('lang_showing', $this->nextmatchs->show_hits($this->bo->cats->total_records, $this->start));

		// ------------------------------ end nextmatch ------------------------------------------

		//------------------- list header variable template-declarations ------------------------- 

		$this->template->set_var('sort_name', $this->nextmatchs->show_sort_order($this->sort, 'cat_name', $this->order, '/index.php', lang('Name'), $link_data));
		$this->template->set_var('sort_description', $this->nextmatchs->show_sort_order($this->sort, 'cat_description', $this->order, '/index.php', lang('Description'), $link_data));

		if (count($edata))
		{
			foreach ($edata as $data)
			{
				$this->template->set_var('sort_data', '<th>' . lang($data) . '</th>', true);
				$this->template->fp('column', 'data_column', True);
			}
		}
		else
		{
			$this->template->set_var('th_data', '');
		}

		// -------------------------- end header declaration --------------------------------------

		for ($i = 0; $i < count($cats); ++$i)
		{
			$this->template->set_var('tr_class', $this->nextmatchs->alternate_row_class($i));

			if ($cats[$i]['app_name'] == 'phpgw')
			{
				$appendix = '&lt;' . lang('Global') . '&gt;';
			}
			elseif ($cats[$i]['owner'] == '-1')
			{
				$appendix = '&lt;' . lang('Global') . '&nbsp;' . $GLOBALS['phpgw_info']['apps'][$cats_app]['title'] . '&gt;';
			}
			else
			{
				$appendix = '';
			}

			$level = $cats[$i]['level'];

			if ($level > 0)
			{
				$space = '&nbsp;&nbsp;';
				$spaceset = str_repeat($space, $level);
				$name = $spaceset . phpgw::strip_html($cats[$i]['name']) . $appendix;
			}

			$descr = phpgw::strip_html($cats[$i]['description']);
			if (!$descr)
			{
				$descr = '&nbsp;';
			}

			if (is_array($edata))
			{
				$data = unserialize($cats[$i]['data']);
				if (!is_array($data))
				{
					$holder = '<td>&nbsp;</td>' . "\n";
					$placeholder = str_repeat($holder, count($edata));
					$this->template->set_var('td_data', $placeholder);
				}
				else
				{
					$this->template->set_var('td_data', $this->cat_data($edata, $data));
				}
			}

			if ($level == 0)
			{
				$name = '<font color="FF0000"><b>' . phpgw::strip_html($cats[$i]['name']) . '</b></font>' . $appendix;
				$descr = '<font color="FF0000"><b>' . $descr . '</b></font>';
			}

			$this->template->set_var(array(
				'name'  => $name,
				'descr' => $descr
			));

			$this->template->set_var('app_url', phpgw::link("/{$cats_app}/index.php", array('cat_id' => (int)$cats[$i]['id'])));

			if ($cats_level || ($level == 0))
			{
				if ($cats[$i]['owner'] == $this->account || $cats[$i]['app_name'] == 'phpgw')
				{
					$link_data['menuaction'] = 'preferences.uicategories.add';
					$link_data['cat_parent'] = $cats[$i]['id'];
					$this->template->set_var('add_sub', phpgw::link('/index.php', $link_data));
					$this->template->set_var('lang_sub_entry', lang('Add sub'));
				}
			}
			else
			{
				$this->template->set_var('add_sub', '');
				$this->template->set_var('lang_sub_entry', '&nbsp;');
			}

			$link_data['cat_id'] = $cats[$i]['id'];
			if ($cats[$i]['owner'] == $this->account && $cats[$i]['app_name'] != 'phpgw')
			{
				$link_data['menuaction'] = 'preferences.uicategories.edit';
				$this->template->set_var('edit', phpgw::link('/index.php', $link_data));
				$this->template->set_var('lang_edit_entry', lang('Edit'));

				$link_data['menuaction'] = 'preferences.uicategories.delete';
				$this->template->set_var('delete', phpgw::link('/index.php', $link_data));
				$this->template->set_var('lang_delete_entry', lang('Delete'));
			}
			else
			{
				$this->template->set_var('edit', '');
				$this->template->set_var('lang_edit_entry', '&nbsp;');

				$this->template->set_var('delete', '');
				$this->template->set_var('lang_delete_entry', '&nbsp;');
			}
			$this->template->fp('list', 'cat_list', True);
		}
		$link_data['menuaction'] = 'preferences.uicategories.add';
		$this->template->set_var('add_action', phpgw::link('/index.php', $link_data));
		$this->save_sessiondata($cats_app);

		$this->template->pfp('out', 'cat_list_t');
	}

	/**
	 * 
	 */
	function add()
	{
		$cats_app    = Sanitizer::get_var('cats_app');
		$extra       = Sanitizer::get_var('extra');
		$global_cats = Sanitizer::get_var('global_cats');
		$cats_level  = Sanitizer::get_var('cats_level');

		$link_data = array(
			'menuaction'  => 'preferences.uicategories.add',
			'cats_app'    => $cats_app,
			'extra'       => $extra,
			'global_cats' => $global_cats,
			'cats_level'  => $cats_level
		);

		$this->flags['app_header'] = lang(
			'Add %1 category for',
			$GLOBALS['phpgw_info']['apps'][$cats_app]['title']
		) . ':&nbsp;' . $this->user;
		Settings::getInstance()->set('flags', $this->flags);

		$this->phpgwapi_common->phpgw_header();
		echo parse_navbar();

		$new_parent      = isset($_POST['new_parent']) ? $_POST['new_parent'] : 0;
		$cat_parent      = isset($_GET['cat_parent']) ? $_GET['cat_parent'] : 0;
		$cat_name        = isset($_POST['cat_name']) ? $_POST['cat_name'] : '';
		$cat_description = isset($_POST['cat_description']) ? $_POST['cat_description'] : '';
		$cat_data        = isset($_POST['cat_data']) ? $_POST['cat_data'] : array();
		$cat_access      = isset($_POST['cat_access']) ? $_POST['cat_access'] : 'private';

		$this->template->set_root(PHPGW_APP_TPL);
		$this->template->set_file(array('form' => 'category_form.tpl'));
		$this->template->set_block('form', 'data_row', 'row');
		$this->template->set_block('form', 'add', 'addhandle');
		$this->template->set_block('form', 'edit', 'edithandle');

		$this->set_langs();

		if ($new_parent)
		{
			$cat_parent = $new_parent;
		}

		if (!$global_cats)
		{
			$global_cats = False;
		}

		if (isset($_POST['save']) && $_POST['save'])
		{
			$data = serialize($cat_data);

			$values = array(
				'parent' => $cat_parent,
				'descr'  => $cat_description,
				'name'   => $cat_name,
				'access' => $cat_access,
				'data'   => $data
			);

			$error = $this->bo->check_values($values);
			if (is_array($error))
			{
				$this->template->set_var('message', $this->phpgwapi_common->error_list($error));
			}
			else
			{
				$this->bo->save_cat($values);
				$this->template->set_var('message', lang('Category %1 has been added !', $cat_name));
			}
		}

		$this->template->set_var('actionurl', phpgw::link('/index.php', $link_data));

		if ($cats_level)
		{
			$type = 'all';
		}
		else
		{
			$type = 'mains';
		}

		$this->template->set_var('category_list', $this->bo->cats->formated_list('select', $type, $cat_parent, $global_cats));
		$this->template->set_var('cat_name', $cat_name);
		$this->template->set_var('cat_description', $cat_description);

		$this->template->set_var('access', '<input type="checkbox" name="cat_access" value="True"'
			. ($cat_access == True ? ' checked="checked"' : '') . ' />');

		if ($extra)
		{
			$edata = explode(',', $extra);
			for ($i = 0; $i < count($edata); ++$i)
			{
				$this->template->set_var('tr_class', $this->nextmatchs->alternate_row_class($i));
				$this->template->set_var('td_data', '<input name="cat_data[' . $edata[$i] . ']" size="50" value="' . (isset($cat_data[$edata[$i]]) ? $cat_data[$edata[$i]] : '') . '" />');
				$this->template->set_var('lang_data', lang($edata[$i]));
				$this->template->fp('row', 'data_row', True);
			}
		}

		$link_data['menuaction'] = 'preferences.uicategories.index';
		$this->template->set_var('cancel_url', phpgw::link('/index.php', $link_data));
		$this->template->set_var('edithandle', '');
		$this->template->set_var('addhandle', '');
		$this->template->pfp('out', 'form');
		$this->template->pfp('addhandle', 'add');
	}

	function edit()
	{
		$cats_app    = Sanitizer::get_var('cats_app');
		$extra       = Sanitizer::get_var('extra');
		$global_cats = Sanitizer::get_var('global_cats');
		$cats_level  = Sanitizer::get_var('cats_level');
		$cat_id      = Sanitizer::get_var('cat_id');

		$link_data = array(
			'menuaction'	=> 'preferences.uicategories.index',
			'cats_app'		=> $cats_app,
			'extra'			=> $extra,
			'global_cats'	=> $global_cats,
			'cats_level'	=> $cats_level,
			'cat_id'		=> $cat_id
		);

		if (!$cat_id)
		{
			phpgw::redirect_link('/index.php', $link_data);
		}

		$this->flags['app_header'] = lang(
			'Edit %1 category for',
			$GLOBALS['phpgw_info']['apps'][$cats_app]['title']
		) . ':&nbsp;' . $this->user;
		Settings::getInstance()->set('flags', $this->flags);

		$this->phpgwapi_common->phpgw_header();
		echo parse_navbar();

		$new_parent			= $_POST['new_parent'];
		$cat_parent			= $_POST['cat_parent'];
		$cat_name			= $_POST['cat_name'];
		$cat_description	= $_POST['cat_description'];
		$cat_data			= $_POST['cat_data'];
		$cat_access			= $_POST['cat_access'];
		$old_parent			= $_POST['old_parent'];

		$this->template->set_root(PHPGW_APP_TPL);
		$this->template->set_file(array('form' => 'category_form.tpl'));
		$this->template->set_block('form', 'data_row', 'row');
		$this->template->set_block('form', 'add', 'addhandle');
		$this->template->set_block('form', 'edit', 'edithandle');

		$this->set_langs();
		$this->template->set_var('cancel_url', phpgw::link('/index.php', $link_data));

		if ($new_parent)
		{
			$cat_parent = $new_parent;
		}

		if (!$global_cats)
		{
			$global_cats = False;
		}

		if ($_POST['save'])
		{
			$data = serialize($cat_data);

			$values = array(
				'id'			=> $cat_id,
				'parent'		=> $cat_parent,
				'descr'			=> $cat_description,
				'name'			=> $cat_name,
				'access'		=> $cat_access,
				'data'			=> $data,
				'old_parent'	=> $old_parent
			);

			$error = $this->bo->check_values($values);
			if (is_array($error))
			{
				$this->template->set_var('message', $this->phpgwapi_common->error_list($error));
			}
			else
			{
				$cat_id = $this->bo->save_cat($values);
				$this->template->set_var('message', lang('Category %1 has been updated !', $cat_name));
			}
		}

		$cats = $this->bo->cats->return_single($cat_id);

		$link_data['menuaction'] = 'preferences.uicategories.edit';
		$this->template->set_var('actionurl', phpgw::link('/index.php', $link_data));

		$this->template->set_var('cat_name', phpgw::strip_html($cats[0]['name']));
		$this->template->set_var('cat_description', phpgw::strip_html($cats[0]['description']));

		$this->template->set_var('hidden_vars', '<input type="hidden" name="old_parent" value="' . $cats[0]['parent'] . '">');

		if ($cats_level)
		{
			$type = 'all';
		}
		else
		{
			$type = 'mains';
		}

		$this->template->set_var('category_list', $this->bo->cats->formated_list(array(
			'type' => $type, 'selected' => $cats[0]['parent'],
			'globals' => $global_cats, 'self' => $cat_id
		)));

		$this->template->set_var('access', '<input type="checkbox" name="cat_access" value="True"'
			. (($cats[0]['access'] == 'private') ? ' checked="checked"' : '') . ' />');

		if ($extra)
		{
			$edata = explode(',', $extra);

			$data = unserialize($cats[0]['data']);
			for ($i = 0; $i < count($edata); ++$i)
			{
				$this->template->set_var('td_data', '<input name="cat_data[' . $edata[$i] . ']" size="50" value="' . $data[$edata[$i]] . '" />');
				$this->template->set_var('lang_data', lang($edata[$i]));
				$this->template->fp('row', 'data_row', True);
			}
		}

		if ($cats[0]['owner'] == $this->account)
		{
			$link_data['menuaction'] = 'preferences.uicategories.delete';
			$this->template->set_var('delete', '<form method="post" action="' . phpgw::link('/index.php', $link_data)
				. '"><input type="submit" value="' . lang('Delete') . '" /></form>');
		}
		else
		{
			$this->template->set_var('delete', '&nbsp;');
		}

		$this->template->set_var('edithandle', '');
		$this->template->set_var('addhandle', '');
		$this->template->pfp('out', 'form');
		$this->template->pfp('edithandle', 'edit');
	}

	function delete()
	{
		$cats_app    = Sanitizer::get_var('cats_app');
		$extra       = Sanitizer::get_var('extra');
		$global_cats = Sanitizer::get_var('global_cats');
		$cats_level  = Sanitizer::get_var('cats_level');
		$cat_id      = Sanitizer::get_var('cat_id');

		$link_data = array(
			'menuaction'  => 'preferences.uicategories.index',
			'cats_app'    => $cats_app,
			'extra'       => $extra,
			'global_cats' => $global_cats,
			'cats_level'  => $cats_level,
			'cat_id'      => $cat_id
		);

		if (!$cat_id || $_POST['cancel'])
		{
			phpgw::redirect_link('/index.php', $link_data);
		}

		if ($_POST['confirm'])
		{
			if ($_POST['subs'])
			{
				$this->bo->delete($cat_id, True);
			}
			else
			{
				$this->bo->delete($cat_id, False);
			}
			phpgw::redirect_link('/index.php', $link_data);
		}
		else
		{
			$this->template->set_file(array('category_delete' => 'delete.tpl'));

			$this->flags['app_header'] = lang('Delete Categories');
			$this->phpgwapi_common->phpgw_header();
			echo parse_navbar();

			$this->template->set_var('deleteheader', lang('Are you sure you want to delete this category ?'));

			$exists = $this->bo->exists(array(
				'type'     => 'subs',
				'cat_name' => '',
				'cat_id'   => $cat_id
			));

			if ($exists)
			{
				$this->template->set_var('lang_subs', lang('Do you also want to delete all subcategories ?'));
				$this->template->set_var('subs', '<input type="checkbox" name="subs" value="True" />');
			}
			else
			{
				$this->template->set_var('lang_subs', '');
				$this->template->set_var('subs', '');
			}

			$this->template->set_var('lang_no', lang('No'));
			$link_data['menuaction'] = 'preferences.uicategories.delete';
			$this->template->set_var('action_url', phpgw::link('/index.php', $link_data));
			$this->template->set_var('lang_yes', lang('Yes'));
			$this->template->pfp('out', 'category_delete');
		}
	}
}
