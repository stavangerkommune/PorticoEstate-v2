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

	/**
	 * Description
	 * @package property
	 */
	class property_bocustom
	{

		var $start;
		var $query;
		var $filter;
		var $sort;
		var $order;
		var $cat_id;
		var $so, $allrows, $total_records, $uicols,$use_session,$phpgwapi_common;
		var $public_functions = array
			(
			'read'			 => true,
			'read_single'	 => true,
			'save'			 => true,
			'delete'		 => true,
		);

		function __construct( $session = false )
		{
			$this->so = CreateObject('property.socustom');
			$this->phpgwapi_common = new \phpgwapi_common();


			if ($session)
			{
				$this->read_sessiondata();
				$this->use_session = true;
			}

			$start	 = Sanitizer::get_var('start', 'int', 'REQUEST', 0);
			$query	 = Sanitizer::get_var('query');
			$sort	 = Sanitizer::get_var('sort');
			$order	 = Sanitizer::get_var('order');
			$filter	 = Sanitizer::get_var('filter', 'int');
			$cat_id	 = Sanitizer::get_var('cat_id', 'int');
			$allrows = Sanitizer::get_var('allrows', 'bool');

			if ($start)
			{
				$this->start = $start;
			}
			else
			{
				$this->start = 0;
			}

			if (isset($query))
			{
				$this->query = $query;
			}
			if (!empty($filter))
			{
				$this->filter = $filter;
			}
			if (isset($sort))
			{
				$this->sort = $sort;
			}
			if (isset($order))
			{
				$this->order = $order;
			}
			if (isset($cat_id) && !empty($cat_id))
			{
				$this->cat_id = $cat_id;
			}
			else
			{
				unset($this->cat_id);
			}
			if (isset($allrows))
			{
				$this->allrows = $allrows;
			}
		}

		function save_sessiondata( $data )
		{
			if ($this->use_session)
			{
				Cache::session_set('custom', 'session_data', $data);
			}
		}

		function read_sessiondata()
		{
			$data = Cache::session_get('custom', 'session_data');

			$this->start	 = $data['start'];
			$this->query	 = $data['query'];
			$this->filter	 = $data['filter'];
			$this->sort		 = $data['sort'];
			$this->order	 = $data['order'];
			$this->cat_id	 = $data['cat_id'];
		}

		function read( $data = array() )
		{
			$custom				 = $this->so->read($data);
			$this->total_records = $this->so->total_records;
			$userSettings = Settings::getInstance()->get('user');
		
			for ($i = 0; $i < count($custom); $i++)
			{
				$custom[$i]['entry_date'] = $this->phpgwapi_common->show_date($custom[$i]['entry_date'], $userSettings['preferences']['common']['dateformat']);
			}
			return $custom;
		}

		function read_single( $custom_id )
		{
			return $this->so->read_single($custom_id);
		}

		function read_custom_name( $custom_id )
		{
			return $this->so->read_custom_name($custom_id);
		}

		function save( $custom )
		{

			if ($custom['custom_id'])
			{
				if ($custom['custom_id'] != 0)
				{
					$custom_id	 = $custom['custom_id'];
					$receipt	 = $this->so->edit($custom);
				}
			}
			else
			{
				$receipt = $this->so->add($custom);
			}
			return $receipt;
		}

		function delete( $params )
		{
			if (is_array($params))
			{
				$this->so->delete($params[0]);
			}
			else
			{
				$this->so->delete($params);
			}
		}

		function resort( $data )
		{
			$this->so->resort($data);
		}

		function read_custom( $data = array() )
		{
			$custom				 = $this->so->read_custom($data);
			$this->uicols		 = $this->so->uicols;
			$this->total_records = $this->so->total_records;
			return $custom;
		}
	}