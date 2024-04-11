<?php

/**
 * phpGroupWare - phpmailer wrapper script
 * @author Dave Hall - skwashd at phpgroupware.org
 * @copyright Copyright (C) 2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package phpgwapi
 * @subpackage communication
 * @version $Id$
 */

 namespace App\Modules\PhpGWApi\Services;
/**
 * @see phpmailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email messages via SMTP
 *
 * @internal this is really just a phpgw friendly wrapper for phpmailer
 * @package phpgwapi
 * @subpackage communication
 */
class MailerSmtp extends PHPMailer
{
	/**
	 * @var array
	 */
	protected $serverSetting;
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->serverSetting = Settings::getInstance()->get('server');

		parent::__construct(true); // enable exceptions
		$this->IsSMTP(true);
		$this->Host = $this->serverSetting['smtp_server'];
		$this->Port = isset($this->serverSetting['smtp_port']) ? $this->serverSetting['smtp_port'] : 25;
		$this->SMTPSecure = isset($this->serverSetting['smtpSecure']) ? $this->serverSetting['smtpSecure'] : '';
		$this->CharSet = 'utf-8';
		$this->Timeout = isset($this->serverSetting['smtp_timeout']) && $this->serverSetting['smtp_timeout'] ? (int)$this->serverSetting['smtp_timeout'] : 10;

		if (isset($this->serverSetting['smtpAuth']) && $this->serverSetting['smtpAuth'] == 'yes') {
			$this->SMTPAuth	= true;
			$this->Username = isset($this->serverSetting['smtpUser']) ? $this->serverSetting['smtpUser'] : '';
			$this->Password =  isset($this->serverSetting['smtpPassword']) ? $this->serverSetting['smtpPassword'] : '';
		}

		/*
			 *	http://stackoverflow.com/questions/26827192/phpmailer-ssl3-get-server-certificatecertificate-verify-failed
			 */
		$this->SMTPOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		);
		/**
		 * SMTP class debug output mode.
		 * Options: 0 = off, 1 = commands, 2 = commands and data
		 * (`3`) As DEBUG_SERVER plus connection status
		 * (`4`) Low-level data output, all messages
		 * @type int
		 */

		if (isset($this->serverSetting['SMTPDebug'])) {
			$this->SMTPDebug = (int)$this->serverSetting['SMTPDebug'];
		}

		/**
		 * The function/method to use for debugging output.
		 * Options: 'echo', 'html' or 'error_log'
		 * @type string
		 * @see SMTP::$Debugoutput
		 */

		if (isset($this->serverSetting['Debugoutput']) && $this->serverSetting['Debugoutput'] != 'echo') {
			switch ($this->serverSetting['Debugoutput']) {
				case 'html':
					$this->Debugoutput =  'html';
					break;
				case 'errorlog':
					$this->Debugoutput =  'error_log';
					break;
				default:
			}
		}
	}
}
