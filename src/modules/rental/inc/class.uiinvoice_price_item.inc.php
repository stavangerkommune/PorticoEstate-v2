<?php
	phpgw::import_class('rental.uicommon');
	phpgw::import_class('rental.soinvoice_price_item');
	include_class('rental', 'contract', 'inc/model/');
	include_class('rental', 'billing', 'inc/model/');

	class rental_uiinvoice_price_item extends rental_uicommon
	{

		public $public_functions = array
			(
			'query' => true,
			'download' => true
		);

		public function query()
		{
			if ($GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs'] > 0)
			{
				$user_rows_per_page = $GLOBALS['phpgw_info']['user']['preferences']['common']['maxmatchs'];
			}
			else
			{
				$user_rows_per_page = 10;
			}

			$search = Sanitizer::get_var('search');
			$order = Sanitizer::get_var('order');
			$draw = Sanitizer::get_var('draw', 'int');
			$columns = Sanitizer::get_var('columns');

			$start_index = Sanitizer::get_var('start', 'int', 'REQUEST', 0);
			$num_of_objects = (Sanitizer::get_var('length', 'int') <= 0) ? $user_rows_per_page : Sanitizer::get_var('length', 'int');
			$sort_field = ($columns[$order[0]['column']]['data']) ? $columns[$order[0]['column']]['data'] : 'title';
			$sort_ascending = ($order[0]['dir'] == 'desc') ? false : true;
			// Form variables
			$search_for = (is_array($search)) ? $search['value'] : $search;
			$search_type = Sanitizer::get_var('search_option', 'string', 'REQUEST', 'all');
			$export = Sanitizer::get_var('export', 'bool');

			if ($export)
			{
				$num_of_objects = 0;
			}

			// Create an empty result set
			$result_objects = array();
			$object_count = 0;
			//Retrieve the type of query and perform type specific logic
			$query_type = Sanitizer::get_var('type');
			switch ($query_type)
			{
				case 'invoice_price_items':
					$filters = array('invoice_id' => Sanitizer::get_var('invoice_id'));
					$result_objects = rental_soinvoice_price_item::get_instance()->get($start_index, $num_of_objects, $sort_field, $sort_ascending, $search_for, $search_type, $filters);
					$object_count = rental_soinvoice_price_item::get_instance()->get_count($search_for, $search_type, $filters);
					break;
			}

			//Create an empty row set
			$rows = array();
			foreach ($result_objects as $result)
			{
				if (isset($result))
				{
					if ($result->has_permission(ACL_READ))
					{
						// ... add a serialized result
						$rows[] = $result->serialize();
					}
				}
			}

			if ($export)
			{
				return $rows;
			}

			$result_data = array('results' => $rows);
			$result_data['total_records'] = $object_count;
			$result_data['draw'] = $draw;

			return $this->jquery_results($result_data);
		}
	}