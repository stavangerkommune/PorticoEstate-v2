<?php
	/**
	* Handles xslt nm widgets
	* @author Bettina Gille <ceb@phpgroupware.org>
	* @copyright Copyright (C) 2005 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.fsf.org/licenses/gpl.html GNU General Public License
	* @package phpgwapi
	* @subpackage gui
	* @version $Id$
	*/
	use App\modules\phpgwapi\services\Settings;
	/**
	* XSLT nextmatch
	*
	* @package phpgwapi
	* @subpackage gui
	*/
	class phpgwapi_nextmatchs_xslt
	{
		var $maxmatches;
		var $action;
		var $template;
		protected $userSettings;
		protected $serverSettings;
		protected $flags;

		function __construct()
		{
			$this->userSettings = Settings::getInstance()->get('user');
			$this->serverSettings = Settings::getInstance()->get('server');
			$this->flags = Settings::getInstance()->get('flags');


		}

		function xslt_filter($data=0)
		{

			phpgwapi_xslttemplates::getInstance()->add_file('filter_select', PHPGW_TEMPLATE_DIR);

			if(is_array($data))
			{
				$filter		= (isset($data['filter'])?$data['filter']:'');
				$format		= (isset($data['format'])?$data['format']:'all');
				$link_data	= (isset($data['link_data'])?$data['link_data']:'');
			}
			else
			{
				//$filter = Sanitizer::get_var('filter');
				//$filter = $data;
				//$format	= 'all';
				return False;
			}

			switch($format)
			{
				case 'yours':
					$filter_obj = array
					(
						array('key' => 'none','lang' => lang('show all')),
						array('key' => 'yours','lang' => lang('only yours'))
					);
					break;
				case 'private':
					$filter_obj = array
					(
						array('key' => 'none','lang' => lang('show all')),
						array('key' => 'private','lang' => lang('only private'))
					);
					break;
				default:
					$filter_obj = array
					(
						array('key' => 'none','lang' => lang('show all')),
						array('key' => 'yours','lang' => lang('only yours')),
						array('key' => 'private','lang' => lang('only private'))
					);
			}

			for($i=0;$i<count($filter_obj);$i++)
			{
				if($filter_obj[$i]['key'] == $filter)
				{
					$filter_obj[$i]['selected'] = 'yes';
				}
			}

			$filter_data = array
			(
				'filter_list'				=> $filter_obj,
				'lang_filter_statustext'	=> lang('Select the filter. To show all entries select SHOW ALL'),
				'lang_submit'				=> lang('submit'),
				'select_url'				=> phpgw::link('/index.php',$link_data)
			);
			return $filter_data;
		}

		function xslt_search($values=0)
		{
			phpgwapi_xslttemplates::getInstance()->add_file('search_field', PHPGW_TEMPLATE_DIR);

			$search_data = array
			(
				'lang_searchfield_statustext'	=> lang('Enter the search string. To show all entries, empty this field and press the SUBMIT button again'),
				'lang_searchbutton_statustext'	=> lang('Submit the search string'),
				'query'							=> $values['query'],
				'lang_search'					=> lang('search'),
				'select_url'					=> phpgw::link('/index.php',$values['link_data'])
			);
			return $search_data;
		}

		function xslt_nm($values = 0)
		{
			phpgwapi_xslttemplates::getInstance()->add_file('nextmatchs', PHPGW_TEMPLATE_DIR);

			$start = isset($values['start']) && $values['start'] ? (int) $values['start'] : 0;
			$phpgwapi_common = new phpgwapi_common();


			$nm_data = array
			(
				'img_width'			=> $this->userSettings['preferences']['common']['template_set'] == 'funkwerk' ? '' : '12',
				'img_height'		=> $this->userSettings['preferences']['common']['template_set'] == 'funkwerk' ? '' : '12',
				'allow_all_rows'	=> isset($values['allow_all_rows']) && $values['allow_all_rows'] ? true : false,
				'allrows'			=> isset($values['allrows']) && $values['allrows'] ? true : false,
				'start_record'		=> $start,
				'record_limit'		=> $this->maxmatches,
				'num_records'		=> (int) $values['num_records'],
				'all_records'		=> (int) $values['all_records'],
				'nextmatchs_url'	=> phpgw::link('/index.php',$values['link_data']),
				'first_img'			=> $phpgwapi_common->image('phpgwapi','first'),
				'first_grey_img'	=> $phpgwapi_common->image('phpgwapi','first-grey'),
				'left_img'			=> $phpgwapi_common->image('phpgwapi','left'),
				'left_grey_img'		=> $phpgwapi_common->image('phpgwapi','left-grey'),
				'right_img'			=> $phpgwapi_common->image('phpgwapi','right'),
				'right_grey_img'	=> $phpgwapi_common->image('phpgwapi','right-grey'),
				'last_img'			=> $phpgwapi_common->image('phpgwapi','last'),
				'last_grey_img'		=> $phpgwapi_common->image('phpgwapi','last-grey'),
				'all_img'			=> $phpgwapi_common->image('phpgwapi','down'),
				'title_first'		=> lang('first page'),
				'title_previous'	=> lang('previous page'),
				'title_next'		=> lang('next page'),
				'title_last'		=> lang('last page'),
				'title_all'			=> lang('show all'),
				'lang_showing'		=> $this->show_hits((int)$values['all_records'],$start,(int)$values['num_records']),
				'query'				=> isset($values['query']) ? $values['query'] : '',
			);
			return $nm_data;
		}

		function show_hits($total_records = 0,$start = 0,$num_records = 0)
		{
			if ($total_records > $this->maxmatches && $total_records != $num_records)
			{
				if ($start + $this->maxmatches > $total_records)
				{
					$end = $total_records;
				}
				else
				{
					$end = $start + $this->maxmatches;
				}
				return lang('showing %1 - %2 of %3',($start + 1),$end,$total_records);
			}
			else
			{
				return lang('showing %1',$total_records);
			}
		}

	}

