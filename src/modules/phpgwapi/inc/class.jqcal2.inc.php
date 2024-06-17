<?php

/**
 * jQuery datepicker wrapper-class
 *
 * @author Sigurd Nes
 * @copyright Copyright (C) 2012 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.fsf.org/licenses/gpl.html GNU General Public License
 * @package phpgwapi
 * @subpackage gui
 * @version $Id: class.jqcal.inc.php 15194 2016-05-24 13:10:40Z sigurdne $
 */

use App\modules\phpgwapi\services\Settings;

/**
 * Import the jQuery class
 */
phpgw::import_class('phpgwapi.jquery');

/**
 * jQuery datepicker wrapper-class
 *
 * @package phpgwapi
 * @subpackage gui
 */
class phpgwapi_jqcal2
{

	public $img_cal;
	public $dateformat;
	private $lang_select_date;
	private $userlang = 'en';

	function __construct()
	{
		phpgwapi_jquery::load_widget('datetimepicker');
		$phpgwapi_common = new \phpgwapi_common();
		$userSettings = Settings::getInstance()->get('user');

		$this->img_cal			 = $phpgwapi_common->image('phpgwapi', 'cal');
		$this->dateformat		 = str_ireplace(array('y'), array('Y'), $userSettings['preferences']['common']['dateformat']);
		$this->lang_select_date      = lang('select date');

		if (isset($userSettings['preferences']['common']['lang']))
		{
			$this->userlang = $userSettings['preferences']['common']['lang'];
		}
	}

	function add_listener($name, $type = 'date', $value = 0, $config = array())
	{
		switch ($type)
		{
			case 'datetime':
				$_type = 'datetime';
				$dateformat = "{$this->dateformat} H:i";
				break;
			case 'time':
				$_type	 = 'time';
				$dateformat = "H:i";
				break;
			default:
				$_type = 'date';
				$dateformat = "{$this->dateformat}";
		}
		if (ctype_digit((string)$value) && $value)
		{
			$start_value = date('Y-m-d H:i', $value);
		}
		else
		{
			$start_value = $dateformat == 'H:i' ? $value : '';
		}
		$this->_input_modern($name, $_type, $dateformat, $config, $start_value);
		return "<input id='{$name}' type='text' value='{$value}' size='10' name='{$name}'/>";
	}

	/**
	 * Add an event listener to the trigger icon - used for XSLT
	 *
	 * @access private
	 * @param string $name the element ID
	 */
	function _input_modern($id, $type, $dateformat, $config = array(), $start_value = '')
	{
		$datepicker = $type == 'time' ? 0 : 1;
		$timepicker = $type == 'date' ? 0 : 1;
		$placeholder = str_ireplace(array('Y', 'm', 'd', 'H', 'i'), array('YYYY', 'MM', 'DD', 'HH', 'mm'), $dateformat);


		if (empty($config['readonly']))
		{
			$readonly = 'false';
		}
		else
		{
			$readonly = 'true';
		}


		if (empty($config['min_date']))
		{
			$min_date = 'false';
		}
		else
		{
			$min_date = "'" . date('Y/m/d', (int)$config['min_date']) . "'";
		}

		if (!empty($config['max_date']))
		{
			$min_date .= ",maxDate:'" . date('Y/m/d', (int)$config['max_date']) . "'";
		}

		if (!empty($config['disabled_dates']))
		{
			$disabled_dates  = "disabledDates: ['" . implode("','", $config['disabled_dates']) . "']"; // Y/m/d
		}
		else
		{
			"disabledDates: []";
		}

		$value = 'false';
		if (!$start_value)
		{
			$start_value = 'new Date()';
		}
		else
		{
			if ($datepicker)
			{
				$start_value = "new Date('{$start_value}')";
				//					$start_value = "'{$start_value}'";
			}
			else if ($timepicker)
			{
				$value = $start_value ? "'{$start_value}'" : 'false';
				$start_value = 'false';
			}
		}


		$js = <<<JS
			$(document).ready(function()
			{
				$( "#{$id}" ).attr('readonly', {$readonly});
				$( "#{$id}" ).attr('placeholder', '{$placeholder}');
				$( "#{$id}" ).attr('autocomplete', 'off');

				jQuery.datetimepicker.setLocale('{$this->userlang}');
				$( "#{$id}" ).datetimepicker(
				{
					format: '{$dateformat}',
					datepicker:{$datepicker},
					timepicker: {$timepicker},
					step: 15,
					value: {$value},
					weeks: true,
					dayOfWeekStart:1,
//					mask:true,
					formatDate:'Y/m/d', //Format date for minDate and maxDate
					formatTime: 'H:i',
					startDate: {$start_value},
					minDate:{$min_date},
					$disabled_dates
				}).keyup(function(e) {
					if(e.keyCode == 8 || e.keyCode == 46) {
						$( "#{$id}" ).val('');
					}
				});
			});
JS;
		phpgwapi_js::getInstance()->add_code('', $js);
	}
}
