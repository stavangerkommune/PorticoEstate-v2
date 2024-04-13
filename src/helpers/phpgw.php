<?php
	/**
	*
	* @author Dave Hall <skwashd@phpgroupware.org>
	* @author Dan Kuykendall <seek3r@phpgroupware.org>
	* @author Joseph Engo <jengo@phpgroupware.org>
	* @copyright Copyright (C) 2000-2008 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.fsf.org/licenses/lgpl.html GNU Lesser General Public License
	* @package phpgroupware
	* @subpackage phpgwapi
	* @version $Id$
	*/

	/*
	   This program is free software: you can redistribute it and/or modify
	   it under the terms of the GNU Lesser General Public License as published by
	   the Free Software Foundation, either version 2 of the License, or
	   (at your option) any later version.

	   This program is distributed in the hope that it will be useful,
	   but WITHOUT ANY WARRANTY; without even the implied warranty of
	   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	   GNU General Public License for more details.

	   You should have received a copy of the GNU Lesser General Public License
	   along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */


	/**
	* Global ugliness class
	*
	* Here lives all the code which makes the API tick and makes any serious
	* refactoring almost impossible
	*
	* @package phpgroupware
	* @subpackage phpgwapi
	*/
	class phpgw
	{
		public $accounts;
		public $adodb;
		public $acl;
		public $auth;
		public $db;
		/**
		 * Turn on debug mode. Will output additional data for debugging purposes.
		 * @var	string	$debug
		 * @access public
		 */
		public $debug = 0;		// This will turn on debugging information.
		public $contacts;
		public $preferences;

		// FIXME find all instances and change to sessions then we can drop this
		public $session;
		public $send;
		public $template;
		public $utilities;
		public $vfs;
		public $calendar;
		public $msg;
		public $addressbook;
		public $todo;
		public $xslttpl;
		public $mapping;

		/**
		* @var array $instance_vars holds most of the public instance variable, so they are only instatiated when needed
		* @internal removes the need for a lot of if ( !isset($var) || !is_object($var)) { $var = createObject("phpgwapi.$var"); } - YAY!
		*/
		private $instance_vars = array();

		/**
		* Handle instance variables better - this way we only load what we need
		*
		* @param string $var the variable name to get
		*/
		public function __get($var)
		{
			if ( !isset($this->instance_vars[$var]) || !is_object($this->instance_vars[$var]) )
			{
				$this->instance_vars[$var] = createObject("phpgwapi.{$var}");
			}
			return $this->instance_vars[$var];
		}

		/**
		* Handle setting instance variables better
		*
		* @internal this will probably validate the variable name at some point in the future to stop typo bugs
		* @param string $var the varliable to set
		* @param mixed $value the value to assign to the variable
		*/
		public function __set($var, $value)
		{
			$this->instance_vars[$var] = $value;
		}

		/**
		* Handle unset()ing of instance variables
		*
		* @param string $var the variable to unset
		*/
		public function __unset($var)
		{
			unset($this->instance_vars[$var]);
		}

		/**
		* Check if an instance variable isset() or not
		*
		* @internal we also check if it an object or not - as that is all we should be storing in here
		* @param string $var the variable to check
		* @return bool is the variable set or not
		*/
		public function __isset($var)
		{
			return isset($this->instance_vars[$var]) && is_object($this->instance_vars[$var]);
		}

		/**
		 * Strips out html chars
		 *
		 * Used as a shortcut for stripping out html special chars.
		 *
		 * @param $s string The string to have its html special chars stripped out.
		 * @return string The string with html special characters removed
		 */
		public static function strip_html($s)
		{
			$s = htmlspecialchars(strip_tags($s), ENT_QUOTES, 'UTF-8');
			return $s;
		}


		/**
		 * Link url generator
		 *
		 * Used for backwards compatibility and as a shortcut. If no url is passed, it
		 * will use PHP_SELF. Wrapper to session->link()
		 *
		 * @access public
		 * @param string  $string The url the link is for
		 * @param array   $extravars	Extra params to be passed to the url
		 * @param boolean $redirect is the resultant link being used in a header('Location:' ... redirect?
		 * @param boolean $external is the resultant link being used as external access (i.e url in emails..)
		 * @param boolean $force_backend if the resultant link is being used to reference resources in the api
		 * @return string The full url after processing
		 * @see	session->link()
		 */
		public function link($url = '', $extravars = array(), $redirect = false, $external = false, $force_backend = false)
		{
			return $this->session->link($url, $extravars, $redirect, $external, $force_backend);
		}

		/**
		 * Redirect to another URL
		 *
		 * @param string $string The url the link is for
		 * @param string $extravars	Extra params to be passed to the url
		 * @return null
		 */
		public function redirect_link($url = '', $extravars=array())
		{
			self::redirect($this->session->link($url, $extravars, true));
		}

		/**
		* Safe redirect to external urls
		*
		* Stop session theft for "GET" based sessions
		*
		* @access public
		* @param string $url the target url
		* @returns string safe redirect url
		* @author Dave Hall
		*/
		public static function safe_redirect($url)
		{
			return $GLOBALS['phpgw_info']['server']['webserver_url']
				. '/redirect.php?go=' . urlencode($url);
		}

		/**
		* Repsost Prevention Detection
		*
		* Used as a shortcut. Wrapper to session->is_repost()
		*
		* @access public
		* @param bool $display_error	Use common error handler? - not yet implemented
		* @return bool True if called previously, else False - call ok
		* @see session->is_repost()
		* @author Dave Hall
		*/
		public function is_repost($display_error = False)
		{
			return $this->session->is_repost($display_error);
		}

		/**
		 * Handles redirects under iis and apache
		 *
		 * This function handles redirects under iis and apache it assumes that $GLOBALS['phpgw']->link() has already been called
		 *
		 * @access public
		 * @param string The url ro redirect to
		 */
		public static function redirect($url = '')
		{
			if ( !$url )
			{
				$url = self::get_var('PHP_SELF', 'string', 'SERVER');
			}

			if ( headers_sent($filename, $linenum) )
			{

				$GLOBALS['phpgw']->log->error(array(
				'text'	=> 'Headers already sent in %1 on line %2.',
				'p1'	=> $filename,
				'p2'	=> $linenum,
				'line'	=> $linenum,
				'file'	=> $filename
			));

				echo "<html>\n<head>\n<title>Redirecting to $url</title>";
				echo "\n<meta http-equiv=\"refresh\ content=\"0; URL=$url\">";
				echo "\n</head>\n<body>";
				echo "\n<h3>Please continue to <a href=\"$url\">this page</a></h3>";
				echo "\n</body>\n</html>";
				exit;
			}
			else
			{
				header('Location: ' . $url);
				exit;
			}
		}


		/**
		* Import a class, should be used in the top of each class, doesn't instantiate like createObject does
		*
		* @internal when calling static methods, phpgw::import_class() should be called to ensure it is available
		* @param string $clasname the class to import module.class
		*/
		public static function import_class($classname)
		{
			$parts = explode('.', $classname);

			if ( count($parts) != 2 )
			{
				trigger_error(lang('Invalid class: %1', $classname), E_USER_ERROR);
			}

			if ( !include_class($parts[0], $parts[1]) )
			{
				trigger_error(lang('Unable to load class: %1', $classname), E_USER_ERROR);
			}
		}

		/**
		 * Display a message - and quit.
		 * @param string $appname
		 * @param string $message
		 */
		public static function no_access($appname = '', $message = '')
		{
			$message = $message ? $message : lang('no access');
			if (phpgw::get_var('phpgw_return_as') == 'json')
			{
				echo $message;
			}
			else
			{
				phpgwapi_cache::message_set($message, 'error');
				$appname = $appname ? $appname : $GLOBALS['phpgw_info']['flags']['currentapp'];
				$GLOBALS['phpgw_info']['flags']['app_header'] = lang($appname) . '::' . lang('No access');
				$GLOBALS['phpgw_info']['flags']['xslt_app'] = false;
				$GLOBALS['phpgw']->common->phpgw_header(true);
			}
			$GLOBALS['phpgw']->common->phpgw_exit();
		}
	}
