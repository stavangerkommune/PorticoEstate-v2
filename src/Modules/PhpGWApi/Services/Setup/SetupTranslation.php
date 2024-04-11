<?php
	/**
	* Setup translation - Handles multi-language support using flat files
	* @author Miles Lott <milosch@phpgroupware.org>
	* @author Dan Kuykendall <seek3r@phpgroupware.org>
	* @copyright Portions Copyright (C) 2001-2004 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.fsf.org/licenses/lgpl.html GNU Lesser General Public License
	* @package phpgwapi
	* @subpackage application
	* @version $Id$
	*/

	namespace App\Modules\PhpGWApi\Services\Setup;

	use App\Modules\PhpGWApi\Services\Translation;
	use App\Modules\PhpGWApi\Services\Cache;
	use App\Modules\PhpGWApi\Services\Settings;
	/**
	* Setup translation - Handles multi-language support using flat files
	* 
	* @package phpgwapi
	* @subpackage application
	*/
	class SetupTranslation extends Translation
	{
		var $langarray,$lang;
		var $db;
		var $serverSettings;

		/**
		 * constructor for the class, loads all phrases into langarray
		*
		 * @param $lang	user lang variable (defaults to en)
		 */
		public function __construct()
		{
			$this->serverSettings = Settings::getInstance()->get('server');
			$this->db = \App\Database\Db::getInstance();
			parent::set_db($this->db);
			parent::set_serverSettings($this->serverSettings);

			$ConfigLang = \Sanitizer::get_var('ConfigLang', 'string', 'POST');
			
			if(!$ConfigLang)
			{
				$ConfigLang  =  \Sanitizer::get_var('ConfigLang', 'string', 'COOKIE');			
			}
			if(!$ConfigLang)
			{
				$ConfigLang = $this->serverSettings['default_lang'];
			}

			$this->set_userlang($ConfigLang);

			$fn = SRC_ROOT_PATH . "/Modules/Setup/Lang/phpgw_{$this->userlang}.lang";
			if (!file_exists($fn))
			{
				$fn = SRC_ROOT_PATH . '/Modules/PhpGWApi/Setup/phpgw_en.lang';
			}

			$strings = $this->parse_lang_file($fn, $this->userlang);

			if ( !is_array($strings) || !count($strings) )
			{
				$this->lang[strtolower($string['message_id'])] = $string['content'];
				echo "Unable to load lang file: {$fn}<br>String won't be translated";
				return;
			}
			foreach ( $strings as $string )
			{
				$this->lang[strtolower($string['message_id'])] = $string['content'];
			}
		}

		/**
		* Populate shared memory with the available translation strings - disabled for setup
		*/
		public function populate_cache()
		{}
		
		/**
		 * Translate phrase to user selected lang
		 *
		 * @param $key  phrase to translate
		 * @param $vars vars sent to lang function, passed to us
		 */
		public function translate($key, $vars = array(), $only_common = false , $force_app = '') 
		{
			if ( !is_array($vars) )
			{
				$vars = array();
			}

			$ret = $key;

			if ( isset($this->lang[strtolower($key)]) )
			{
				$ret = $this->lang[strtolower($key)];
			}
			else
			{
				$ret = "!{$key}";
			}
			$ndx = 1;
			foreach ( $vars as $var )
			{
				$ret = preg_replace( "/%$ndx/", $var, $ret );
				++$ndx;
			}
			return $ret;
		}

		/* Following functions are called for app (un)install */

		/**
		 * return array of installed languages, e.g. array('de','en')
		*
		 */
		function get_langs($DEBUG=False)
		{
			if($DEBUG)
			{
				echo '<br>get_langs(): checking db...' . "\n";
			}
			$stmt = $this->db->prepare("SELECT DISTINCT(lang) FROM phpgw_lang");
			$stmt->execute();

			$langs = array();

			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				if ($DEBUG) {
					echo '<br>get_langs(): found ' . $row['lang'];
				}
				$langs[] = $row['lang'];
			}

			return $langs;
		}

		/**
		 * delete all lang entries for an application, return True if langs were found
		*
		 * @param $appname app_name whose translations you want to delete
		 */
		function drop_langs($appname,$DEBUG=False)
		{
			if($DEBUG)
			{
				echo '<br>drop_langs(): Working on: ' . $appname;
			}
			$stmt = $this->db->prepare("SELECT COUNT(message_id) as cnt FROM phpgw_lang WHERE app_name=:appname");
			$stmt->execute([':appname' => $appname]);

			$result = $stmt->fetch(PDO::FETCH_ASSOC);

			if($result['cnt'])
			{
				if(function_exists('sem_get'))
				{
					$stmt = $this->db->prepare("SELECT lang FROM phpgw_lang WHERE app_name=:appname");
					$stmt->execute([':appname' => $appname]);

					while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
					{
						Cache::system_clear('phpgwapi', 'lang_' . $row['lang']);
					}
				}

				$stmt = $this->db->prepare("DELETE FROM phpgw_lang WHERE app_name=:appname");
				$stmt->execute([':appname' => $appname]);

				return true;
			}
			return false;
		}

		/**
		 * process an application's lang files, calling get_langs() to see what langs the admin installed already
		*
		 * @param $appname app_name of application to process
		 */
		function add_langs($appname,$DEBUG=False,$force_en=False)
		{
			$appname = ucfirst($appname);
			$langs = $this->get_langs($DEBUG);
			if($force_en && !in_array('en',$langs))
			{
				$langs[] = 'en';
			}

			if(!empty($this->serverSettings['default_lang']) && $force_en && !in_array($this->serverSettings['default_lang'],$langs))
			{
				$langs[] = $this->serverSettings['default_lang'];
			}

			if($DEBUG)
			{
				echo '<br>add_langs(): chose these langs: ';
				_debug_array($langs);
			}

			foreach ( $langs as $lang )
			{
				// escape it here - that will increase the string length
				$lang = $this->db->db_addslashes($lang);
				if ( strlen($lang) != 2 )
				{
					continue; // invalid lang
				}

				$lang = strtolower($lang);

				Cache::system_clear('phpgwapi', "lang_{$lang}");

				if($DEBUG)
				{
					echo '<br>add_langs(): Working on: ' . $lang . ' for ' . $appname;
				}
				$appfile = SRC_ROOT_PATH . "/Modules/{$appname}/Setup/phpgw_{$lang}.lang";
				if(file_exists($appfile))
				{
					if($DEBUG)
					{
						echo '<br>add_langs(): Including: ' . $appfile;
					}
					$raw_file = $this->parse_lang_file($appfile, $lang);

					foreach ( $raw_file as $line ) 
					{
						$message_id = $this->db->db_addslashes(strtolower(substr($line['message_id'], 0, self::MAX_MESSAGE_ID_LENGTH)));
						/* echo '<br>APPNAME:' . $app_name . ' PHRASE:' . $message_id; */
						$app_name   = $this->db->db_addslashes($line['app_name']);
						$content    = $this->db->db_addslashes($line['content']);

						$stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM phpgw_lang WHERE message_id=:message_id AND app_name=:app_name AND lang=:lang");
						$stmt->execute([':message_id' => $message_id, ':app_name' => $app_name, ':lang' => $lang]);

						$result = $stmt->fetch(PDO::FETCH_ASSOC);

						if ($result['cnt'] == 0)
						{
							if($message_id && $content)
							{
								if($DEBUG)
								{
									echo "<br>add_langs(): adding - INSERT INTO phpgw_lang (message_id,app_name,lang,content) VALUES ('{$message_id}','{$app_name}','{$lang}','{$content}')";
								}
								$stmt = $this->db->prepare("INSERT INTO phpgw_lang (message_id,app_name,lang,content) VALUES (:message_id, :app_name, :lang, :content)");
								$stmt->execute([':message_id' => $message_id, ':app_name' => $app_name, ':lang' => $lang, ':content' => $content]);
							}
						}
					}
				}
			}
		}
	}
