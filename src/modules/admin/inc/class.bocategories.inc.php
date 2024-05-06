<?php

/**************************************************************************\
 * phpGroupWare - Admin - Global categories                                 *
 * http://www.phpgroupware.org                                              *
 * Written by Bettina Gille [ceb@phpgroupware.org]                          *
 * -----------------------------------------------                          *
 *  This program is free software; you can redistribute it and/or modify it *
 *  under the terms of the GNU General Public License as published by the   *
 *  Free Software Foundation; either version 2 of the License, or (at your  *
 *  option) any later version.                                              *
	\**************************************************************************/
/* $Id$ */
/* $Source$ */

use App\modules\phpgwapi\services\Cache;
use App\modules\phpgwapi\services\Settings;

class admin_bocategories
{
	var $cats;

	var $start;
	var $query;
	var $sort;
	var $order;
	var $filter;
	var $cat_id;
	var $total;
	var $allrows;

	var $debug = False;

	function __construct()
	{
		$userSettings = Settings::getInstance()->get('user');
		$appname = Sanitizer::get_var('appname');
		$location = Sanitizer::get_var('location');
		if ($appname)
		{
			$this->cats = CreateObject('phpgwapi.categories', -1, $appname, $location);
		}
		else
		{
			$this->cats = CreateObject('phpgwapi.categories', $userSettings['account_id'], 'phpgw');
		}

		$this->read_sessiondata();

		$start	= Sanitizer::get_var('start', 'int', 'REQUEST', 0);
		$query  = Sanitizer::get_var('query');
		$sort   = Sanitizer::get_var('sort');
		$order  = Sanitizer::get_var('order');
		$cat_id = Sanitizer::get_var('cat_id', 'int');
		$allrows  = Sanitizer::get_var('allrows', 'bool');

		$this->allrows	= $allrows ? $allrows : false;

		$this->start = $start ? $start : 0;
		if ((empty($query) && !empty($this->query)) || !empty($query))
		{
			if ($this->debug)
			{
				echo '<br>setting query to: "' . $query . '"';
			}
			$this->query = $query;
		}

		if (isset($cat_id) && !empty($cat_id))
		{
			$this->cat_id = $cat_id;
		}

		if ($cat_id == 0)
		{
			//				unset($this->cat_id);
			$this->cat_id = '';
		}

		if (isset($sort) && !empty($sort))
		{
			$this->sort = $sort;
		}

		if (isset($order) && !empty($order))
		{
			$this->order = $order;
		}
	}

	function save_sessiondata($data)
	{
		if ($this->debug)
		{
			echo '<br>Save:';
			_debug_array($data);
		}
		Cache::session_set('admin_cats', 'session_data', $data);
	}

	function read_sessiondata()
	{
		$data = Cache::session_get('admin_cats', 'session_data');
		if ($this->debug)
		{
			echo '<br>Read:';
			_debug_array($data);
		}

		$this->start  = $data['start'];
		$this->query  = $data['query'];
		$this->sort   = $data['sort'];
		$this->order  = $data['order'];
		if (isset($data['cat_id']))
		{
			$this->cat_id = $data['cat_id'];
		}
	}

	function get_list($global_cats = False)
	{
		if ($this->debug)
		{
			echo '<br>querying: "' . $this->query . '"';
		}
		$limit = $this->allrows ? false : true;

		if ($global_cats)
		{
			return $this->cats->return_sorted_array($this->start, $limit, $this->query, $this->sort, $this->order, True);
		}
		else
		{
			return $this->cats->return_sorted_array($this->start, $limit, $this->query, $this->sort, $this->order);
		}
	}

	function save_cat($values)
	{
		if ($values['cat_id'] && $values['cat_id'] != 0)
		{
			return $this->cats->edit($values);
		}
		else
		{
			return $this->cats->add($values);
		}
	}

	function exists($data)
	{
		return $this->cats->exists($data);
	}

	function formatted_list($data)
	{
		return $this->cats->formatted_list($data);
	}

	function delete($cat_id, $drop_subs = False, $modify_subs = False)
	{
		$this->cats->delete($cat_id, $drop_subs, $modify_subs);
	}

	function check_values($values)
	{
		$error = array();
		if (strlen($values['descr']) >= 255)
		{
			$error[] = lang('Description can not exceed 255 characters in length !');
		}

		if (!$values['name'])
		{
			$error[] = lang('Please enter a name');
		}
		else
		{
			if (!$values['parent'])
			{
				$exists = $this->exists(array(
					'type'     => 'appandmains',
					'cat_name' => $values['name'],
					'cat_id'   => $values['cat_id']
				));
			}
			else
			{
				$exists = $this->exists(array(
					'type'     => 'appandsubs',
					'cat_parent' => $values['parent'],
					'cat_name' => $values['name'],
					'cat_id'   => $values['cat_id']
				));
			}

			if ($exists == True)
			{
				$error[] = lang('That name has been used already');
			}
		}

		if (
			is_array($error)
			&& count($error)
		)
		{
			return $error;
		}
	}
}
