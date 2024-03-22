<?php

namespace App\Services;

use App\Services\Translation;
use PDO;
use DateTimeZone;
use DateTime;
use App\Controllers\Api\Accounts\Accounts;


class Preferences
{
	private static $instance;
	private $settings;
	private $db;
	private $translation;
	private $config_data;
	private $forced;
	private $default;
	private $user;
	private $data;
	private $values;
	private $vars;
	private $serverSettings;
	private $apps;
	private $preferences;
	public $account_id;


	public function __construct($account_id = null)
	{
		$this->db = DatabaseObject::getInstance()->get('db');
		$this->account_id = $account_id;
		$this->serverSettings = Settings::getInstance()->get('server');
		$this->preferences = $this->Readpreferences();
	//	$this->translation = new Translation($this->preferences);

		// Load settings from the database
		$this->settings = $this->loadSettingsFromDatabase();
		if ($account_id) {
			$this->set('account_id', $account_id);
		}
	}

	public function setAccountId($account_id)
	{
		$this->account_id = $account_id;
		$this->preferences = $this->Readpreferences();
		$this->set('account_id', $account_id);
	}

	public static function getInstance($account_id = null)
	{
		if (null === static::$instance) {
			static::$instance = new static($account_id);
		}

		return static::$instance;
	}

	private function loadSettingsFromDatabase()
	{
		return [
			'preferences' => $this->preferences,
			// ...
		];
	}


	/**
	 * unquote (stripslashes) recursivly the whole array
	 *
	 * @param $arr array to unquote (var-param!)
	 */
	public function unquote(&$arr)
	{
		if (!is_array($arr)) {
			$arr = stripslashes($arr);
			return;
		}
		foreach ($arr as $key => $value) {
			if (is_array($value)) {
				$this->unquote($arr[$key]);
			} else {
				$arr[$key] = stripslashes($value);
			}
		}
	}

	private function Readpreferences()
	{

		$stmt = $this->db->prepare('SELECT * FROM phpgw_preferences WHERE preference_owner IN (-1,-2,?)');
		$stmt->execute([intval($this->account_id)]);

		$this->forced = $this->default = $this->user = array();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$app = str_replace(' ', '', $row['preference_app']);

			$value = json_decode($row['preference_json'], true);

			$this->unquote($value);
			if (!is_array($value)) {
				continue;
			}
			switch ($row['preference_owner']) {
				case -1:    // forced
					$this->forced[$app] = $value;
					break;
				case -2:    // default
					$this->default[$app] = $value;
					break;
				default:    // user
					$this->user[$app] = $value;
					break;
			}
		}


		$this->data = $this->user;

		// now use defaults if needed (user-value unset or empty)
		//
		foreach ($this->default as $app => $values) {
			foreach ($values as $var => $value) {
				if (!isset($this->data[$app][$var]) || $this->data[$app][$var] === '') {
					$this->data[$app][$var] = $value;
				}
			}
		}
		// now set/force forced values
		//
		foreach ($this->forced as $app => $values) {
			foreach ($values as $var => $value) {
				$this->data[$app][$var] = $value;
			}
		}
		// setup the standard substitues and substitues the data in $this->data
		//
		$this->standard_substitutes();

		// This is to supress warnings durring login
		if (is_array($this->data)) {
			reset($this->data);

			// This one should cope with daylight saving time
			// Create two timezone objects, one for UTC (GMT) and one for
			// user pref
			$dateTimeZone_utc = new DateTimeZone('UTC');
			$dateTimeZone_pref = new DateTimeZone(isset($this->data['common']['timezone']) && $this->data['common']['timezone'] ? $this->data['common']['timezone'] : 'UTC');

			// Create two DateTime objects that will contain the same Unix timestamp, but
			// have different timezones attached to them.
			$dateTime_utc = new DateTime("now", $dateTimeZone_utc);
			$dateTime_pref = new DateTime("now", $dateTimeZone_pref);

			// Calculate the GMT offset for the date/time contained in the $dateTime_utc
			// object, but using the timezone rules as defined for Tokyo

			$this->data['common']['tz_offset'] = (int)$dateTimeZone_pref->getOffset($dateTime_utc) / 3600;
		}

		return $this->data;
	}

	/**
	 * parses a notify and replaces the substitutes
	 *
	 * @param $msg message to parse / substitute
	 * @param $values extra vars to replace in addition to $this->values, vars are in an array with \
	 * 	$key => $value pairs, $key does not include the $'s and is the *untranslated* name
	 * @param $use_standard_values should the standard values are used
	 * @return the parsed notify-msg
	 */
	function parse_notify($msg, $values = '', $use_standard_values = true)
	{
		$replace = $with = array();
		$vals = $values ? $values : array();

		if ($use_standard_values && is_array($this->values)) {
			$vals += $this->values;
		}
		foreach ($vals as $key => $val) {
			$replace[] = '$$' . $key . '$$';
			$with[]    = $val;
		}
		return str_replace($replace, $with, $msg);
	}


	/**
	 * replaces the english key's with translated ones, or if $un_lang the opposite
	 *
	 * @param $msg message to translate
	 * @param $values extra vars to replace in addition to $this->values, vars are in an array with \
	 * 	$key => $value pairs, $key does not include the $'s and is the *untranslated* name
	 * @param $un_lang if true translate back
	 * @return the result
	 */
	function lang_notify($msg, $vals = array(), $un_lang = False)
	{
		foreach ($vals as $key => $val) {
			$lname = ($lname = lang($key)) == $key . '*' ? $key : $lname;
			if ($un_lang) {
				$langs[$lname] = '$$' . $key . '$$';
			} else {
				$langs[$key] = '$$' . $lname . '$$';
			}
		}
		return $this->parse_notify($msg, $langs, False);
	}

	/**
	 * returns the custom email-address (if set) or generates a default one
	 *
	 * This will generate the appropriate email address used as the "From:"
	 *	 email address when the user sends email, the localpert * part. The "personal"
	 *	 part is generated elsewhere.
	 *	 In the absence of a custom ['email']['address'], this function should be used to set it.
	 * @param $accountid - as determined in and/or passed to "create_email_preferences"
	 * @access public
	 */
	public function email_address($account_id = '')
	{
		if (isset($this->data['email']['address'])) {
			return $this->data['email']['address'];
		}

		$prefs_email_address = (new Accounts())->getObject()->id2lid($this->account_id);

		if (!preg_match('/@/', $prefs_email_address)) {
			$prefs_email_address .= "@{$this->serverSettings['mail_suffix']}";
		}
		return $prefs_email_address;
	}

	function standard_substitutes()
	{
		//hack to include the usersetting in the translation object
		$this->translation = new Translation($this->data);
		if (empty($this->serverSettings['mail_suffix'])) {
			$this->serverSettings['mail_suffix'] = $_SERVER['HTTP_HOST'];
		}

		$user = (new Accounts())->getObject()->get($this->account_id);

		// we cant use phpgw_info/user/fullname, as it's not set when we run
		// standard notify replacements

		$this->values = array(
			'fullname'  => $user->__toString(),
			'firstname' => $user->firstname,
			'lastname'  => $user->lastname,
			'domain'    => $this->serverSettings['mail_suffix'],
			'email'     => $this->email_address($this->account_id),
			'date'      => \App\Helpers\DateHelper::showDate('', $this->data['common']['dateformat'], $this->data)
		);
		// do this first, as it might be already contain some substitues
		//
		$this->values['email'] = $this->parse_notify($this->values['email']);

		// langs have to be in common !!!
		$this->vars = array(
			'fullname'  => $this->translation->translate('name of the user, eg. %1', [$this->values['fullname']]),
			'firstname' => $this->translation->translate('first name of the user, eg. %1', [$this->values['firstname']]),
			'lastname'  => $this->translation->translate('last name of the user, eg. %1', [$this->values['lastname']]),
			'domain'    => $this->translation->translate('domain name for mail-address, eg. %1', [$this->values['domain']]),
			'email'     => $this->translation->translate('email-address of the user, eg. %1', [$this->values['email']]),
			'date'      => $this->translation->translate('todays date, eg. %1', [$this->values['date']])
		);

		// do the substituetion in the effective prefs (data)
		//
		foreach ($this->data as $app => $data) {
			foreach ($data as $key => $val) {
				if (!is_array($val) && strstr($val, '$$') !== False) {
					$this->data[$app][$key] = $this->parse_notify($val);
				} elseif (is_array($val)) {
					foreach ($val as $k => $v) {
						if (!is_array($v) && strstr($v, '$$') !== False) {
							$this->data[$app][$key][$k] = $this->parse_notify($v);
						}
					}
				}
			}
		}
	}

	private function ReadInstalledApps()
	{
		$sql = 'SELECT * FROM phpgw_applications WHERE app_enabled != 0 ORDER BY app_order ASC';
		// get all installed apps
		try {
			$apps = $this->db->query($sql)->fetchAll();
		} catch (\PDOException $e) {
			die("Error executing query: " . $e->getMessage());
		}
		$values = [];
		foreach ($apps as $key => $value) {
			$values[$value['app_name']] =
				[
					'name'    => $value['app_name'],
					'title'   => $this->translation->translate($value['app_name'], array(), false, $value['app_name']),
					'enabled' => true,
					'status'  => $value['app_enabled'],
					'id'      => (int) $value['app_id'],
					'version' => $value['app_version']
				];
		}
		return $values;
	}

	public function set($name, $value)
	{
		$this->settings = array_merge($this->settings, array($name => $value));
	}

	public function get($name)
	{
		return $this->settings[$name] ?? null;
	}
}
