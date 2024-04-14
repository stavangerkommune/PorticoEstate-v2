<?php

namespace App\modules\phpgwapi\services;

use App\modules\phpgwapi\services\Translation;
use PDO;
use DateTimeZone;
use DateTime;
use App\modules\phpgwapi\controllers\Accounts\Accounts;
use App\modules\phpgwapi\security\Sessions;


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
	private $global_lock = false;


	public function __construct($account_id = null)
	{
		$this->db = \App\Database\Db::getInstance();
		$this->account_id = $account_id;
		$this->serverSettings = Settings::getInstance()->get('server');
		$this->preferences = $this->Readpreferences();

		// Load settings from the database
		$this->settings = $this->loadSettingsFromDatabase();
		if ($account_id)
		{
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
		if (null === static::$instance)
		{
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
		if (!is_array($arr))
		{
			$arr = stripslashes($arr);
			return;
		}
		foreach ($arr as $key => $value)
		{
			if (is_array($value))
			{
				$this->unquote($arr[$key]);
			}
			else
			{
				$arr[$key] = stripslashes($value);
			}
		}
	}

	private function Readpreferences()
	{

		$stmt = $this->db->prepare('SELECT * FROM phpgw_preferences WHERE preference_owner IN (-1,-2,?)');
		$stmt->execute([intval($this->account_id)]);

		$this->forced = $this->default = $this->user = array();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$app = str_replace(' ', '', $row['preference_app']);

			$value = json_decode($row['preference_json'], true);

			$this->unquote($value);
			if (!is_array($value))
			{
				continue;
			}
			switch ($row['preference_owner'])
			{
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
		foreach ($this->default as $app => $values)
		{
			foreach ($values as $var => $value)
			{
				if (!isset($this->data[$app][$var]) || $this->data[$app][$var] === '')
				{
					$this->data[$app][$var] = $value;
				}
			}
		}
		// now set/force forced values
		//
		foreach ($this->forced as $app => $values)
		{
			foreach ($values as $var => $value)
			{
				$this->data[$app][$var] = $value;
			}
		}
		// setup the standard substitues and substitues the data in $this->data
		//
		$this->standard_substitutes();

		// This is to supress warnings durring login
		if (is_array($this->data))
		{
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
	 * add preference to $app_name a particular app
	 *
	 * @param $app_name name of the app
	 * @param $var name of preference to be stored
	 * @param $value value of the preference
	 * @param $type of preference to set: forced, default, user
	 * the effective prefs ($this->data) are updated to reflect the change
	 * @return the new effective prefs (even when forced or default prefs are set !)
	 */
	public function add($app_name, $var, $value = '##undef##', $type = 'user')
	{
		//echo "<p>add('$app_name','$var','$value')</p>\n";
		if ($value == '##undef##')
		{
			global $$var;
			$value = $$var;
		}

		switch ($type)
		{
			case 'forced':
				$this->data[$app_name][$var] = $this->forced[$app_name][$var] = $value;
				break;

			case 'default':
				$this->default[$app_name][$var] = $value;
				if ((!isset($this->forced[$app_name][$var]) || $this->forced[$app_name][$var] === '') &&
					(!isset($this->user[$app_name][$var]) || $this->user[$app_name][$var] === '')
				)
				{
					$this->data[$app_name][$var] = $value;
				}
				break;

			case 'user':
			default:
				$this->user[$app_name][$var] = $value;
				if (!isset($this->forced[$app_name][$var]) || $this->forced[$app_name][$var] === '')
				{
					$this->data[$app_name][$var] = $value;
				}
				break;
		}
		reset($this->data);
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

		if ($use_standard_values && is_array($this->values))
		{
			$vals += $this->values;
		}
		foreach ($vals as $key => $val)
		{
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
		foreach ($vals as $key => $val)
		{
			$lname = ($lname = lang($key)) == $key . '*' ? $key : $lname;
			if ($un_lang)
			{
				$langs[$lname] = '$$' . $key . '$$';
			}
			else
			{
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
		if (isset($this->data['email']['address']))
		{
			return $this->data['email']['address'];
		}

		$prefs_email_address = (new Accounts())->id2lid($this->account_id);

		if (!preg_match('/@/', $prefs_email_address))
		{
			$prefs_email_address .= "@{$this->serverSettings['mail_suffix']}";
		}
		return $prefs_email_address;
	}

	function standard_substitutes()
	{
		//hack to include the usersetting in the translation object
		$this->translation = Translation::getInstance($this->data);
		if (empty($this->serverSettings['mail_suffix']))
		{
			$this->serverSettings['mail_suffix'] = $_SERVER['HTTP_HOST'];
		}

		$user = (new Accounts())->get($this->account_id);

		// we cant use phpgw_info/user/fullname, as it's not set when we run
		// standard notify replacements

		$this->values = array(
			'fullname'  => $user->__toString(),
			'firstname' => $user->firstname,
			'lastname'  => $user->lastname,
			'domain'    => $this->serverSettings['mail_suffix'],
			'email'     => $this->email_address($this->account_id),
			'date'      => \App\helpers\DateHelper::showDate('', $this->data['common']['dateformat'], $this->data)
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
		foreach ($this->data as $app => $data)
		{
			foreach ($data as $key => $val)
			{
				if (!is_array($val) && strstr($val, '$$') !== False)
				{
					$this->data[$app][$key] = $this->parse_notify($val);
				}
				elseif (is_array($val))
				{
					foreach ($val as $k => $v)
					{
						if (!is_array($v) && strstr($v, '$$') !== False)
						{
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
		try
		{
			$this->db->query($sql);
			$apps = $this->db->resultSet;
		}
		catch (\PDOException $e)
		{
			die("Error executing query: " . $e->getMessage());
		}
		$values = [];
		foreach ($apps as $key => $value)
		{
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

	/**
	 * save the the preferences to the repository
	 *
	 * @param $update_session_info old param, seems not to be used
	 * @param $type which prefs to update: user/default/forced
	 * the user prefs for saveing are in $this->user not in $this->data, which are the effectiv prefs only
	 */
	public function save_repository($update_session_info = False, $type = 'user')
	{
		// Don't get the old values back from the cache on next load
		\App\modules\phpgwapi\services\Cache::session_clear('phpgwapi', 'phpgw_info');

		switch ($type)
		{
			case 'forced':
				$account_id = -1;
				$prefs = &$this->forced;
				break;
			case 'default':
				$account_id = -2;
				$prefs = &$this->default;
				break;
			default:
				$account_id = intval($this->account_id);
				$prefs = &$this->user;	// we use the user-array as data contains default values too
				break;
		}
		//echo "<p>preferences::save_repository(,$type): account_id=$account_id, prefs="; print_r($prefs); echo "</p>\n";

		$Acl = \App\modules\phpgwapi\security\Acl::getInstance();
		if (!$Acl->check('session_only_preferences', 1, 'preferences'))
		{
			if ($this->db->get_transaction())
			{
				$this->global_lock = true;
			}
			else
			{
				$this->db->transaction_begin();
			}

			$stmt = $this->db->prepare("DELETE FROM phpgw_preferences WHERE preference_owner = :account_id");
			$stmt->execute([':account_id' => $account_id]);

			foreach ($prefs as $app => $value)
			{
				if (!is_array($value))
				{
					continue;
				}

				$value_set = array(
					':preference_owner' => $account_id,
					':preference_app' => $app,
					':preference_json' => json_encode($value)
				);

				$sql = "INSERT INTO phpgw_preferences (preference_owner, preference_app, preference_json) VALUES (:preference_owner, :preference_app, :preference_json)";
				$stmt = $this->db->prepare($sql);
				$stmt->execute($value_set);
			}

			if (!$this->global_lock)
			{
				$this->db->transaction_commit();
			}
		}

		//	$GLOBALS['phpgw_info']['user']['preferences'] = $this->data;

		if (($type == 'user' || !$type) && !empty($this->serverSettings['cache_phpgw_info']))
		{
			$sessions = Sessions::getInstance();
			$sessions->read_repositories(false);
		}

		return $this->data;
	}
}
