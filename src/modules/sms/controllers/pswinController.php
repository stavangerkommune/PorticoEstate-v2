<?php

namespace App\modules\sms\controllers;

use Psr\Container\ContainerInterface;
use App\modules\phpgwapi\services\Settings;
use App\Database\Db;
use App\modules\phpgwapi\security\Sessions;
use Sanitizer;
use App\modules\phpgwapi\controllers\Locations;
use SoapServer;
use PDOException;
use SoapFault;



require_once SRC_ROOT_PATH . '/helpers/LegacyObjectHandler.php';

/**
 * ReceiveSMSMessageResponse
 */
class ReceiveSMSMessageResponse
{

	/**
	 * @access public
	 * @var ReturnValue
	 */
	public $ReceiveSMSMessageResult;
}

/**
 * ReceiveDeliveryReportResponse
 */
class ReceiveDeliveryReportResponse
{

	/**
	 * @access public
	 * @var ReturnValue
	 */
	public $ReceiveDeliveryReportResult;
}

/**
 * ReturnValue
 */
class ReturnValue
{

	/**
	 * @access public
	 * @var sint
	 */
	public $Code;

	/**
	 * @access public
	 * @var sstring
	 */
	public $Description;

	/**
	 * @access public
	 * @var sstring
	 */
	public $Reference;
}

class pswinFunctions
{
	private static $errors = [];

	function __construct($errors = [])
	{
		self::$errors = $errors;
	}
	
	public function hello($someone)
	{
		if ($error = self::check_error())
		{
			return $error;
		}

		return "Hello " . $someone . " ! - SOAP 1.2";
	}


	private static function check_error()
	{
		if (self::$errors)
		{
			$error = 'Error(s): ' . implode(' ## AND ## ', self::$errors);
			return new SoapFault("phpgw", $error);
		}
	}

	public function ReceiveSMSMessage($ReceiveSMSMessage)
	{
		if ($error = self::check_error())
		{
			return $error;
		}
		$db = Db::getInstance();
		$ReceiveSMSMessageResponse = new ReceiveSMSMessageResponse();
		$ReturnValue = new ReturnValue();
		$ReturnValue->Reference = '';

		$value_set = array(
			'type' => 'sms', // report
			'data' => $db->db_addslashes(serialize($ReceiveSMSMessage)),
			'entry_date' => time(),
			'modified_date' => time(),
		);

		$cols = implode(',', array_keys($value_set));
		$values = $db->validate_insert(array_values($value_set));

		$db->Exception_On_Error = true;

		try
		{
			$db->query("INSERT INTO phpgw_sms_received_data ({$cols}) VALUES ({$values})", __LINE__, __FILE__);
		}
		catch (PDOException $e)
		{
		}

		if ($e)
		{
			$ReturnValue->Description = $e->getMessage();
			$ReturnValue->Code = 500;
		}
		else
		{
			$ReturnValue->Description = 'All is good';
			$ReturnValue->Code = 200;
		}

		$ReceiveSMSMessageResponse->ReceiveSMSMessageResult = $ReturnValue;

		return $ReceiveSMSMessageResponse;
	}

	public function ReceiveMMSMessage($ReceiveMMSMessage)
	{
		if ($error = self::check_error())
		{
			return $error;
		}
		$db = Db::getInstance();

		$ReceiveMMSMessageResponse = new \ReceiveMMSMessageResponse();
		$ReturnValue = new ReturnValue();
		$ReturnValue->Code = '500';
		$ReturnValue->Description = '';
		$ReturnValue->Reference = '';

		$value_set = array(
			'type' => 'mms', // report
			'data' => base64_encode(serialize($ReceiveMMSMessage)),
			'entry_date' => time(),
			'modified_date' => time(),
		);

		$cols = implode(',', array_keys($value_set));
		$values = $db->validate_insert(array_values($value_set));
		if ($db->query("INSERT INTO phpgw_sms_received_data ({$cols}) VALUES ({$values})", __LINE__, __FILE__))
		{
			$ReturnValue->Code = '200';
		}

		$ReceiveMMSMessageResponse->ReceiveMMSMessageResult = $ReturnValue;

		return $ReceiveMMSMessageResponse;
	}

	public function ReceiveDeliveryReport($DeliveryReport)
	{
		if ($error = self::check_error())
		{
			return $error;
		}
		$db = Db::getInstance();

		$ReceiveDeliveryReportResponse = new ReceiveDeliveryReportResponse();
		$ReturnValue = new ReturnValue();
		$ReturnValue->Code = '500';
		$ReturnValue->Description = '';
		$ReturnValue->Reference = '';

		$value_set = array(
			'type' => 'report',
			'data' => $db->db_addslashes(serialize($DeliveryReport)),
			'entry_date' => time(),
			'modified_date' => time(),
		);

		$cols = implode(',', array_keys($value_set));
		$values = $db->validate_insert(array_values($value_set));
		if ($db->query("INSERT INTO phpgw_sms_received_data ({$cols}) VALUES ({$values})", __LINE__, __FILE__))
		{
			$ReturnValue->Code = '200';
		}

		$ReceiveDeliveryReportResponse->ReceiveDeliveryReportResult = $ReturnValue;

		return $ReceiveDeliveryReportResponse;
	}
}

class pswinController
{

	private $db;
	private $acl;
	private $userSettings;
	private $phpgwapi_common;
	private $server;
	private static $errors = [];

	public function __construct(ContainerInterface $container)
	{


		if (!isset($_GET['domain']) || !$_GET['domain'])
		{
			self::$errors[] = 'domain not given as input';
		}
		else
		{

			// Add your settings to the container
			$database_settings = require SRC_ROOT_PATH . '/helpers/FilterDatabaseConfig.php';

			$_domain_info = isset($database_settings['domain']) && $database_settings['domain'] == $_GET['domain'];
			if (!$_domain_info)
			{
				echo "not a valid domain\n";
				die();
			}
		}

		Settings::getInstance()->update('flags', ['session_name' => 'soapclientsession']);

		$sessions = Sessions::getInstance();

		$this->phpgwapi_common = new \phpgwapi_common();

		$this->userSettings = Settings::getInstance()->get('user');
		$location_obj = new Locations();
		$location_id = $location_obj->get_id('sms', 'run');
		$c = CreateObject('admin.soconfig', $location_id);

		$login = $c->config_data['common']['anonymous_user'];
		$passwd = $c->config_data['common']['anonymous_pass'];

		$_POST['submitit'] = "";

		$session_id = $sessions->create($login, $passwd);

		if (!$session_id)
		{
			$lang_denied = lang('Anonymous access not correctly configured');
			self::$errors[] = $lang_denied;
		}

		$wsdl = PHPGW_SERVER_ROOT . '/sms/inc/plugin/gateway/pswin/Receive.wsdl';
		if (isset($_GET['wsdl']))
		{
			header('Content-Type: text/xml');
			readfile($wsdl);
			$this->phpgwapi_common->phpgw_exit();
		}

		$options = array(
			'uri' => "http://test-uri/", # the name space of the SOAP service
			'soap_version' => SOAP_1_2,
			'encoding' => "UTF-8", # the encoding name
		);

		ini_set("soap.wsdl_cache_enabled", "0");
		$this->server = new SoapServer($wsdl, $options);
	}

	public function process($request, $response, $args)
	{

		$pswinFunctions = new pswinFunctions(self::$errors);
		$this->server->setObject($pswinFunctions);
	
		$request_xml = implode(" ", file('php://input'));

		if ($_SERVER["REQUEST_METHOD"] == "POST")
		{
			/*
		  $filename = '/tmp/test_soap.txt';
		  $fp = fopen($filename, "wb");
		  fwrite($fp,serialize($request_xml));
		  fclose($fp);
		 */
			$this->server->handle($request_xml);
		}
		else
		{
			if (self::$errors)
			{
				$error = 'Error(s): ' . implode(' ## AND ## ', self::$errors);
				echo $error;
				$this->phpgwapi_common->phpgw_exit(True);
			}

			echo "This SOAP server can handle following functions: ";

			_debug_array($this->server->getFunctions());
		}
		$this->phpgwapi_common->phpgw_exit();
	}

}
