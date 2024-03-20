<?php

namespace App\Helpers;
use DateTime;
use DateTimeZone;
use Exception;
use App\Services\Settings;


class DateHelper
{
    /**
	 * Format date
	 *
	 * @param string $date
	 * @param string $format
	 * @return string
	 */
	public static function formatDate($date, $format = 'Y-m-d'): string
    {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    }

	/**
	 * Show current date
	 *
	 * @param integer $t Time, defaults to user preferences
	 * @param string $format Date format, defaults to user preferences
	 * @return string Formated date
	 */
	public static function showDate($t = 0, $format = '', $preferences = []): string
	{
		if (!$t || (substr(php_uname(), 0, 7) == "Windows" && intval($t) <= 0)) {
			return ''; // return nothing if not valid input
		}

		$this->preferences = isset(Settings::getInstance()->get('user')['preferences']) ? Settings::getInstance()->get('user')['preferences'] : $preferences;

		try {
			$date = new DateTime(date('Y-m-d H:i:s', $t));
		} catch (Exception $exc) {
			return 'invalid date';
		}

		$timezone	 = !empty($preferences['common']['timezone']) ? $preferences['common']['timezone'] : 'UTC';
		$DateTimeZone	 = new DateTimeZone($timezone);
		$date->setTimezone($DateTimeZone);

		if (!$format) {
			$format = $preferences['common']['dateformat'] . ' - ';
			if ($preferences['common']['timeformat'] == '12') {
				$format .= 'h:i a';
			} else {
				$format .= 'H:i';
			}
		}

		return $date->format($format);
	}

}
