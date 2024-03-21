<?php
namespace App\Middleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Response as Psr7Response;
use Slim\Routing\RouteContext;
//use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;
use App\Security\Auth\Auth;
use PDO;
use App\Services\Settings;
use App\Services\ServerSettings;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\NotFoundException;
use App\Services\DatabaseObject;
Use App\Services\Preferences;
use App\Services\Cache;
use App\Controllers\Api\Accounts\phpgwapi_account;
use App\Controllers\Api\Accounts\Accounts;
use App\Services\Crypto;


$session_identifier = 'phpgw';
session_name("session{$session_identifier}sessid");


class SessionsMiddleware implements MiddlewareInterface
{
    protected $container;
    private $db;
	private $_account_lid;
	private $_account_domain;
	private $_sessionid;
	private $_use_cookies;
	private $_cookie_domain;
	private $config;
	private $routePath;
    private $_session_flags;
    private $_data;
    private $_account_id;
    private $_key;
    private $_iv;
    private $_verified;
    private $cd_reason;
	private $reason;
	private $_login;
	private $_passwd;
	private $serverSetting;
	private $Auth;
	private $Crypto;
		
	
    public function __construct($container)
    {
        $this->container = $container;
        $this->db = $container->get('db');
		DatabaseObject::getInstance($this->db);

		$this->config = $container->get('settings')['settings'];
		
		$serverSetting = ServerSettings::getInstance()->get('server');
		
		$auth_type = !empty($serverSetting['auth_type']) ? ucfirst($serverSetting['auth_type']) : 'Sql';
		include_once SRC_ROOT_PATH . "/Security/Auth/Auth_{$auth_type}.php";
		$this->Auth = new Auth($this->db);


		//how do I get the settings from the container?
		//	$this->_use_cookies = $this->serverSetting['use_cookies'];


		$this->_use_cookies = true;

	//	$this->_phpgw_set_cookie_params();

		$this->_sessionid	= \Sanitizer::get_var(session_name(), 'string', 'COOKIE');


		//respect the config option for cookies
		ini_set('session.use_cookies', true);

		//don't rewrite URL, as we have to do it in link - why? cos it is buggy otherwise
	//	ini_set('url_rewriter.tags', '');
	//	ini_set("session.gc_maxlifetime", $this->serverSetting['sessions_timeout']);

    }

	public function get_session_id()
	{
		return $this->_sessionid;
	}


	/**
	 * Configure cookies to be used properly for this session
	 *
	 * @return string domain
	 */
	protected function _phpgw_set_cookie_params()
	{
		if (!is_null($this->_cookie_domain)) {
			return $this->_cookie_domain;
		}

		if (!empty($this->config['cookieDomain'])) {
			$this->_cookie_domain = $this->config['cookieDomain'];
		} else {
			$parts = explode(':', \Sanitizer::get_var('HTTP_HOST', 'string', 'SERVER')); // strip portnumber if it exists in url (as in 'http://127.0.0.1:8080/')
			$this->_cookie_domain = $parts[0];
		}

		if ($this->_cookie_domain == 'localhost') {
			$this->_cookie_domain = '';
		}
		/**
		 * Test if the cookie make it through a reverse proxy where the request switch from https to http
		 */
		//			$secure = \Sanitizer::get_var('HTTPS', 'bool', 'SERVER');
		$secure = false;

		if (!empty($this->config['webserverUrl'])) {
			$webserver_url = $this->config['webserverUrl'] . '/';
		} else {
			$webserver_url = '/';
		}

		/*
			 * Temporary hack
			 */
		$webserver_url = '/';

		session_set_cookie_params(
			array(
				'lifetime' => 0,
				'path' => parse_url($webserver_url, PHP_URL_PATH),
				'domain' => $this->_cookie_domain,
				'secure' => $secure,
				'httponly' => false,
				'samesite' => 'Lax'
			)
		);

		return $this->_cookie_domain;
	}



	public function process(Request $request, RequestHandler $handler): Response
    {
	//	print_r(__CLASS__);

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();


		// If there is no route, return 404
		if (empty($route)) {
			return $this->sendErrorResponse(['msg' => 'route not found'], 404);
		}


        //get the route path
        $this->routePath = $route->getPattern();
        $routePath_arr = explode('/', $this->routePath);
        $currentApp = $routePath_arr[1];       

        $UserName = $request->getHeaderLine('X-API-User');
		$this->_account_lid = $UserName;
		$this->_account_domain = 'default';

		$sql = "SELECT account_id FROM phpgw_accounts"
		 ." WHERE account_lid = :UserName";
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':UserName', $UserName);
		$stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $account_id = !empty($result['account_id']) ? $result['account_id'] : 0;

		session_start();
		$this->_sessionid = session_id();






		$this->read_repositories($account_id, $currentApp);

//		echo lang('date');
 
        // Continue with the next middleware
        return $handler->handle($request);
    }


	/**
	 * Create a new session
	 *
	 * @param string  $login     user login
	 * @param string  $passwd    user password
	 * @param boolean $skip_auth create a sesison without authenticating the user?
	 *
	 * @return string session id
	 */
	public function create($login, $passwd = '', $skip_auth = false)
	{
		$accounts = (new Accounts())->getObject();

		if (is_array($login)) {
			$this->_login	= $login['login'];
			$this->_passwd	= $login['passwd'];
			$login			= $this->_login;
		} else {
			$this->_login	= $login;
			$this->_passwd	= $passwd;
		}

		$now = time();

		$this->_set_login($login);
		$user_ip	= $this->_get_user_ip();

		if ($this->_login_blocked($login, $this->_get_user_ip())) {
			$this->reason		= 'blocked, too many attempts';
			$this->cd_reason	= 99;

			// log unsuccessfull login
			$this->log_access($this->reason, $login, $user_ip, 0);

			return false;
		}

		if (
			\App\Security\GloballyDenied::user($this->_account_lid)

			|| !$accounts->name2id($this->_account_lid)
			|| (!$skip_auth && !$this->Auth->authenticate($this->_account_lid, $this->_passwd))
			|| get_class($accounts->get($accounts->name2id($this->_account_lid)))
			== phpgwapi_account::CLASS_TYPE_GROUP
		) {
			$this->reason		= 'bad login or password';
			$this->cd_reason	= 5;

			// log unsuccessfull login
			$this->log_access($this->reason, $login, $user_ip, 0);
			return false;
		}

	
		$this->_account_id = $accounts->name2id($this->_account_lid);
	
	//	$GLOBALS['phpgw_info']['user']['account_id'] = $this->_account_id;
		$accounts->set_account($this->_account_id);

		session_start();
		$this->_sessionid = session_id();

		if (!empty($this->serverSetting['usecookies'])) {
			$this->phpgw_setcookie('domain', $this->_account_domain);
		}

		$Acl = new \App\Security\Acl();

		if ($Acl->check('anonymous', 1, 'phpgwapi')) {
			$session_flags = 'A';
		} else {
			$session_flags = 'N';
		}

		if ($session_flags == 'N' && (!empty($this->serverSetting['usecookies'])
		|| isset($_COOKIE['last_loginid']))) {
			// Create a cookie which expires in 14 days
			$cookie_expires = $now + (60 * 60 * 24 * 14);
			$this->phpgw_setcookie('last_loginid', $this->_account_lid, $cookie_expires);
			$this->phpgw_setcookie('last_domain', $this->_account_domain, $cookie_expires);
		}
		/* we kill this for security reasons */
		unset($this->serverSetting['default_domain']);

		/* init the crypto object */
		$this->_key = md5($this->_sessionid . $this->serverSetting['encryptkey']);
		$this->_iv  = $this->serverSetting['mcrypt_iv'];
		
		$this->Crypto = new Crypto();
		$this->Crypto->init(array($this->_key, $this->_iv));

		$this->read_repositories();
		if ($this->_data['expires'] != -1 && $this->_data['expires'] < time()) {
			if (is_object($GLOBALS['phpgw']->log)) {
				$GLOBALS['phpgw']->log->message(array(
					'text' => 'W-LoginFailure, account loginid %1 is expired',
					'p1'   => $this->_account_lid,
					'line' => __LINE__,
					'file' => __FILE__
				));
				$GLOBALS['phpgw']->log->commit();
			}

			$this->cd_reason = 2;
			return false;
		}

		$GLOBALS['phpgw_info']['user']  = $this->_data;
		//		$GLOBALS['phpgw_info']['hooks'] = $this->hooks;

		Cache::session_set('phpgwapi', 'password', base64_encode($this->_passwd));

		$this->db->transaction_begin();
		$this->register_session($login, $user_ip, $now, $session_flags);
		$this->log_access($this->_sessionid, $login, $user_ip, $this->_account_id);
		$GLOBALS['phpgw']->auth->update_lastlogin($this->_account_id, $user_ip);
		$this->db->transaction_commit();

		$this->_verified = true;
		return $this->_sessionid;
	}

	/**
	 * Update the last active timestamp for this session
	 *
	 * This prevents sessions timing out - not really needed anymore
	 *
	 * @return boolean was the timestamp updated?
	 */
	public function update_dla()
	{
		if ($this->_sessionid != session_id()) {
			throw new \Exception("sessions::sessionid is tampered");
		}

		if ($this->routePath) {
			$action = $this->routePath;
		} else {
			$action = $_SERVER['PHP_SELF'];
		}

		$_SESSION['phpgw_session']['session_dla'] = time();
		$_SESSION['phpgw_session']['session_action'] = $action;

		return true;
	}

	/**
	 * Check to see if a session is still current and valid
	 *
	 * @param string $sessionid session id to be verfied
	 *
	 * @return bool is the session valid?
	 */
	public function verify($sessionid = '')
	{
		if (empty($sessionid) || !$sessionid) {
			$sessionid = $this->get_session_id();
		}

		if (!$sessionid) {
			return false;
		}

		$this->_sessionid = $sessionid;

		$session = $this->read_session($sessionid);
		if (!$session) {
			return false;
		}
		$this->_session_flags = $session['session_flags'];

		$lid_data = explode('#', $session['session_lid']);
		$this->_account_lid = $lid_data[0];

		if (!in_array($this->serverSetting['auth_type'], array('ntlm', 'customsso'))) //Timeout make no sense for SSO
		{
			$timeout = time() - $this->serverSetting['sessions_timeout'];
			if (
				!isset($session['session_dla'])
				|| $session['session_dla'] <= $timeout
			) {
				if (isset($session['session_dla'])) {
					if (is_object($GLOBALS['phpgw']->log)) {
						$GLOBALS['phpgw']->log->message(array(
							'text' => 'W-VerifySession, session for %1 is expired by %2 sec, inactive for %3 sec',
							'p1'   => $this->_account_lid,
							'p2'   => ($timeout - $session['session_dla']),
							'p3'   => (time() - $session['session_dla']),
							'line' => __LINE__,
							'file' => __FILE__
						));
						$GLOBALS['phpgw']->log->commit();
					}
					if (is_object($this->Crypto)) {
						$this->Crypto->cleanup();
						unset($this->Crypto);
					}

					$this->cd_reason = 10;
				}
				return false;
			}
		}


		if (isset($lid_data[1])) {
			$this->_account_domain = $lid_data[1];
		} else {
			$this->_account_domain = $this->serverSetting['default_domain'];
		}

		if (!empty($this->serverSetting['usecookies'])) {
			$this->phpgw_setcookie('domain', $this->_account_domain);
		}

		unset($lid_data);

		$this->update_dla();
		$this->_account_id = $GLOBALS['phpgw']->accounts->name2id($this->_account_lid);
		if (!$this->_account_id) {
			$this->cd_reason = 5;
			return false;
		}

	//	$GLOBALS['phpgw_info']['user']['account_id'] = $this->_account_id;

		/* init the crypto object before appsession call below */
		//$this->_key = md5($this->_sessionid . $this->serverSetting['encryptkey']); //Sigurd: not good for permanent data
		$this->_key = $this->serverSetting['encryptkey'];
		$this->_iv  = $this->serverSetting['mcrypt_iv'];

		$this->Crypto = new Crypto();
		$this->Crypto->init(array($this->_key, $this->_iv));


		$use_cache = false;
		if (isset($this->serverSetting['cache_phpgw_info'])) {
			$use_cache = !!$this->serverSetting['cache_phpgw_info'];
		}

		$this->read_repositories($use_cache);

		if ($this->_data['expires'] != -1 && $this->_data['expires'] < time()) {
			if (is_object($GLOBALS['phpgw']->log)) {
				$GLOBALS['phpgw']->log->message(array(
					'text' => 'W-VerifySession, account loginid %1 is expired',
					'p1'   => $this->_account_lid,
					'line' => __LINE__,
					'file' => __FILE__
				));
				$GLOBALS['phpgw']->log->commit();
			}
			if (is_object($this->Crypto)) {
				$this->Crypto->cleanup();
				unset($this->Crypto);
			}
			$this->cd_reason = 2;
			return false;
		}

		$GLOBALS['phpgw_info']['user']  = $this->_data;
		//		$GLOBALS['phpgw_info']['hooks'] = $this->hooks;

		$GLOBALS['phpgw_info']['user']['session_ip'] = $session['session_ip'];
		$GLOBALS['phpgw_info']['user']['passwd']     = Cache::session_get('phpgwapi', 'password');

		if ($this->_account_domain != $GLOBALS['phpgw_info']['user']['domain']) {
			if (is_object($GLOBALS['phpgw']->log)) {
				$GLOBALS['phpgw']->log->message(array(
					'text' => 'W-VerifySession, the domains %1 and %2 don\'t match',
					'p1'   => $this->_account_domain,
					'p2'   => $GLOBALS['phpgw_info']['user']['domain'],
					'line' => __LINE__,
					'file' => __FILE__
				));
				$GLOBALS['phpgw']->log->commit();
			}
			if (is_object($this->Crypto)) {
				$this->Crypto->cleanup();
				unset($this->Crypto);
			}
			$this->cd_reason = 5;
			return false;
		}

		// verify the user agent in an attempt to stop session hijacking
		if ($_SESSION['phpgw_session']['user_agent'] != md5(\Sanitizer::get_var('HTTP_USER_AGENT', 'string', 'SERVER'))) {
			if (is_object($GLOBALS['phpgw']->log)) {
				// This needs some better wording
				$GLOBALS['phpgw']->log->message(array(
					'text' => 'W-VerifySession, User agent hash %1 doesn\'t match user agent hash %2 in session',
					'p1'   => $_SESSION['phpgw_session']['user_agent'],
					'p2'   => md5(\Sanitizer::get_var('HTTP_USER_AGENT', 'string', 'SERVER')),
					'line' => __LINE__,
					'file' => __FILE__
				));
				$GLOBALS['phpgw']->log->commit();
			}
			if (is_object($this->Crypto)) {
				$this->Crypto->cleanup();
				unset($this->Crypto);
			}
			// generic session can't be verified error - don't be specific about the problem
			$this->cd_reason = 2;
			return false;
		}

		$check_ip = false;
		if (isset($this->serverSetting['sessions_checkip'])) {
			$check_ip = !!$this->serverSetting['sessions_checkip'];
		}

		if ($check_ip) {
			if (
				PHP_OS != 'Windows' &&
				(!$GLOBALS['phpgw_info']['user']['session_ip']
				|| $GLOBALS['phpgw_info']['user']['session_ip'] != $this->_get_user_ip())
			) {
				if (is_object($GLOBALS['phpgw']->log)) {
					// This needs some better wording
					$GLOBALS['phpgw']->log->message(array(
						'text' => 'W-VerifySession, IP %1 doesn\'t match IP %2 in session',
						'p1'   => $this->_get_user_ip(),
						'p2'   => $GLOBALS['phpgw_info']['user']['session_ip'],
						'line' => __LINE__,
						'file' => __FILE__
					));
					$GLOBALS['phpgw']->log->commit();
				}
				if (is_object($this->Crypto)) {
					$this->Crypto->cleanup();
					unset($this->Crypto);
				}
				$this->cd_reason = 2;
				return false;
			}
		}
		/*
			$GLOBALS['phpgw']->acl->set_account_id($this->_account_id);
			$GLOBALS['phpgw']->accounts->set_account($this->_account_id);
			$GLOBALS['phpgw']->preferences->set_account_id($this->_account_id);
			$GLOBALS['phpgw']->applications->set_account_id($this->_account_id);
*/
		$GLOBALS['phpgw']->translation->populate_cache();

		if (!$this->_account_lid) {
			if (is_object($GLOBALS['phpgw']->log)) {
				// This needs some better wording
				$GLOBALS['phpgw']->log->message(array(
					'text' => 'W-VerifySession, account_id is empty',
					'line' => __LINE__,
					'file' => __FILE__
				));
				$GLOBALS['phpgw']->log->commit();
			}
			if (is_object($this->Crypto)) {
				$this->Crypto->cleanup();
				unset($this->Crypto);
			}
			return false;
		}
		$this->_verified = true;
		return true;
	}

	/**
	 * Set a cookie
	 *
	 * @param string  $cookiename  name of cookie to be set
	 * @param string  $cookievalue value to be used, if unset cookie is cleared (optional)
	 * @param integer $cookietime  when cookie should expire, 0 for session only (optional)
	 *
	 * @return void
	 */
	public function phpgw_setcookie($cookiename, $cookievalue = '', $cookietime = 0)
	{
		$cookie_params = session_get_cookie_params();

		setcookie($cookiename, $cookievalue, [
			'expires'	 => $cookietime,
			'path'		 => $cookie_params['path'],
			'domain'	 => $cookie_params['domain'],
			'secure'	 => !!$cookie_params['secure'],
			'httponly'	 => !!$cookie_params['httponly'],
			'samesite'	 => $cookie_params['samesite'],
		]);
	}

	/**
	 * Terminate a session
	 *
	 * @param string $sessionid the session to terminate
	 *
	 * @return boolean was the session terminated?
	 */
	public function destroy($sessionid)
	{
		if (!$sessionid) {
			return false;
		}

		$this->log_access($this->_sessionid);	// log logout-time

		// Only do the following, if where working with the current user
		if ($sessionid == $GLOBALS['phpgw_info']['user']['sessionid']) {
			session_unset();
			session_destroy();
			$this->phpgw_setcookie(session_name());
		} else if ($this->serverSetting['sessions_type'] == 'php') {
			$sessions = $this->list_sessions(0, '', '', true);

			if (isset($sessions[$sessionid])) {
				unlink($sessions[$sessionid]['session_file']);
			}
		} else {
			phpgwapi_session_handler_db::destroy($sessionid);
		}

		return true;
	}
	/**
	 * Write or update (for logout) the access_log
	 *
	 * @param string  $sessionid  id of session or 0 for unsuccessful logins
	 * @param string  $login      account_lid (evtl. with domain) or '' for settion the logout-time
	 * @param string  $user_ip    ip to log
	 * @param integer $account_id the user's account_id
	 *
	 * @return void
	 */
	public function log_access($sessionid, $login = '', $user_ip = '', $account_id = '')
	{
		$now		= time();
		$sessionid	= $this->db->db_addslashes($sessionid);
		$login		= $this->db->db_addslashes($login);
		$user_ip	= $this->db->db_addslashes($user_ip);
		$account_id	= (int) $account_id;

		if ($login != '') {
			$sql = 'INSERT INTO phpgw_access_log(sessionid,loginid,ip,li,lo,account_id)'
			. " VALUES ('{$sessionid}', '{$login}', '{$user_ip}', {$now}, 0, {$account_id})";
			$this->db->query($sql, __LINE__, __FILE__);
		} else {
			$sql = "UPDATE phpgw_access_log SET lo ={$now}"
			. " WHERE sessionid='{$sessionid}'";
			$this->db->query($sql, __LINE__, __FILE__);
		}
		if ($this->serverSetting['max_access_log_age']) {
			$max_age = $now - ($this->serverSetting['max_access_log_age'] * 24 * 60 * 60);

			$this->db->query("DELETE FROM phpgw_access_log WHERE li < {$max_age}");
		}
	}

	/**
	 * Read the repositories for the current user
	 * @param int $account_id
	 * @param string $currentApp
	 * @param bool $write_cache
	 */
	private function read_repositories($account_id = 0, $currentApp = '', $write_cache = true)
	{
		if ($write_cache) {
			$data = Cache::session_get('phpgwapi', 'phpgw_info');
		echo json_encode($data);die();
		}

		$flags = [
			'currentapp' => $currentApp
		];

		$data = [];
		ServerSettings::getInstance()->set('flags', $flags);
		$preferences = Preferences::getInstance($account_id)->get('preferences');
		Settings::getInstance()->setAccountId($account_id);

//		$acl = (new \App\Security\Acl())->set_account_id($account_id);
		$apps = (new \App\Controllers\Api\Applications($account_id))->read();

		Settings::getInstance()->set('server', ServerSettings::getInstance()->get('server'));
		Settings::getInstance()->set('apps', $apps);
		Settings::getInstance()->set('flags', $flags);
		
		$accounts = (new \App\Controllers\Api\Accounts\Accounts($account_id))->getObject();

		$data               	= $accounts->read()->toArray();
		$data['fullname']		= $accounts->read()->__toString();
		$data['preferences']	= $preferences;
		$data['apps']       	= $apps;

		//$phpgw_info['user' => [], 'server' => [], 'apps' =>[], 'flags' =>[]];
		$data['session_ip']  = $this->_get_user_ip();
		$data['session_lid'] = $this->_account_lid . '#' . $this->_account_domain;
		$data['account_id']  = $account_id;
		
		Settings::getInstance()->set('user', $data);
		header('Content-Type: application/json');
//		echo json_encode($data);
//		echo json_encode(Settings::getInstance()->get('flags'));
//		die();
		if ($write_cache) {
			Cache::session_set('phpgwapi', 'phpgw_info', $data);
		}
		
	}

	/**
	 * Get the ip address of current users
	 *
	 * @return string ip address
	 */
	protected function _get_user_ip()
	{
		return \Sanitizer::get_var(
			'HTTP_X_FORWARDED_FOR',
			'ip',
			'SERVER',
			\Sanitizer::get_var('REMOTE_ADDR', 'ip', 'SERVER')
		);
	}


	/**
	 * Store user specific data in the session array
	 *
	 * @param string  $login         the user's login id
	 * @param string  $user_ip       the IP address the user connected from
	 * @param integer $now           current unix timestamp
	 * @param string  $session_flags the flags associated with the session
	 *
	 * @return void
	 */
	public function register_session($login, $user_ip, $now, $session_flags)
	{
		if ($this->_sessionid != session_id()) {
			throw new \Exception("sessions::sessionid is tampered");
		}

		if (!strlen(session_id())) {
			throw new \Exception("sessions::register_session() - No value for session_id()");
		}

		$_SESSION['phpgw_session'] = array(
			'session_id'		=> $this->_sessionid,
			'session_lid'		=> $login,
			'session_ip'		=> $user_ip,
			'session_logintime'	=> $now,
			'session_dla'		=> $now,
			'session_action'	=> $_SERVER['PHP_SELF'],
			'session_flags'		=> $session_flags,
			'user_agent'		=> md5(\Sanitizer::get_var('HTTP_USER_AGENT', 'string', 'SERVER')),
			// we need the install-id to differ between serveral installs shareing one tmp-dir
			'session_install_id'	=> $this->serverSetting['install_id']
		);
	}

	/**
	 * Set the user's login details
	 *
	 * @param string $login the user login to parse
	 *
	 * @return void
	 */
	protected function _set_login($login)
	{
		$m = array();
		if (preg_match('/(.*)#(.*)/', $login, $m)) {
			$this->_account_lid = $m[1];
			$this->_account_domain = $m[2];
			return;
		}

		$this->_account_lid = $login;
		$this->_account_domain = $this->serverSetting['default_domain'];
	}

	/**
	 * Protect against brute force attacks, block login if too many unsuccessful login attmepts
	 *
	 * @param string $login account_lid (evtl. with domain)
	 * @param string $ip    the ip that made the request
	 *
	 * @return boolean login blocked?
	 */
	protected function _login_blocked($login, $ip)
	{
		$blocked	= false;
		$block_time = time() - $this->serverSetting['block_time'] * 60;
		$ip			= $this->db->db_addslashes($ip);

		if (isset($this->serverSetting['sessions_checkip']) && $this->serverSetting['sessions_checkip']) {
			$sql = 'SELECT COUNT(*) AS cnt FROM phpgw_access_log'
			. " WHERE account_id = 0 AND ip = '{$ip}' AND li > {$block_time}";

			$this->db->query($sql, __LINE__, __FILE__);
			$this->db->next_record();

			$false_ip = $this->db->f('cnt');
			if ($false_ip > $this->serverSetting['num_unsuccessful_ip']) {
				$blocked = true;
			}
		}

		$login	= $this->db->db_addslashes($login);
		$sql	= 'SELECT COUNT(*) AS cnt FROM phpgw_access_log'
		. " WHERE account_id = 0 AND (loginid='{$login}' OR loginid LIKE '$login#%')"
			. " AND li > {$block_time}";
		$this->db->query($sql, __LINE__, __FILE__);

		$this->db->next_record();
		$false_id = $this->db->f('cnt');
		if ($false_id > $this->serverSetting['num_unsuccessful_id']) {
			$blocked = true;
		}

		if (
			$blocked && isset($this->serverSetting['admin_mails'])
			&& $this->serverSetting['admin_mails']
			// max. one mail each 5mins
			&& $this->serverSetting['login_blocked_mail_time'] < (time() - (5 * 60))
		) {
			// notify admin(s) via email

			$from_name = !empty($this->serverSetting['site_title']) ? $this->serverSetting['site_title'] : $this->serverSetting['system_name'];
			$from    = str_replace(" ", "_", $from_name) . "@{$this->serverSetting['email_domain']}";
			$subject = lang("%1: login blocked for user '%2', ip %3", $from_name, $login, $ip);
			$body    = lang('Too many unsuccessful attempts to login: '
			. "%1 for the user '%2', %3 for the IP %4", $false_id, $login, $false_ip, $ip);

			if (!is_object($GLOBALS['phpgw']->send)) {
				$GLOBALS['phpgw']->send = createObject('phpgwapi.send');
			}
			$subject = $GLOBALS['phpgw']->send->encode_subject($subject);
			$admin_mails = explode(',', $this->serverSetting['admin_mails']);
			foreach ($admin_mails as $to) {
				$GLOBALS['phpgw']->send->msg(
					'email',
					$to,
					$subject,
					$body,
					'',
					'',
					'',
					$from,
					$from
				);
			}
			// save time of mail, to not send to many mails
			$config = createObject('phpgwapi.config', 'phpgwapi');
			$config->read_repository();
			$config->value('login_blocked_mail_time', time());
			$config->save_repository();
		}
		return $blocked;
	}


	/**
	 * Send an error response
	 *
	 * @param array $error
	 * @param int   $statusCode
	 *
	 * @return Response
	 */
	private function sendErrorResponse($error, $statusCode = 401): Response
	{
		$response = new Response();
		$response->getBody()->write(json_encode($error));
		return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
	}


}