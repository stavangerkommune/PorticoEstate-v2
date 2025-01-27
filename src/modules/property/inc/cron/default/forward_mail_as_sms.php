<?php

/**
 * phpGroupWare - property: a Facilities Management System.
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2003-2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
 * @package property
 * @subpackage custom
 * @version $Id$
 */

use App\modules\phpgwapi\services\Settings;
use App\modules\phpgwapi\controllers\Accounts\Accounts;

/**
 * Description
 * usage:
 * @package property
 */
include_class('property', 'cron_parent', 'inc/cron/');

class forward_mail_as_sms extends property_cron_parent
{
	var $bocommon;
	function __construct()
	{
		parent::__construct();

		$this->function_name = get_class($this);
		$this->sub_location	 = lang('Async service');
		$this->function_msg	 = 'Forward email as SMS';

		$this->bocommon = CreateObject('property.bocommon');
	}

	function execute($data = array())
	{

		$accounts_obj = new Accounts();

		$data['account_id'] = $accounts_obj->name2id($data['user']);
		$this->check_for_new_mail($data);
	}

	function check_for_new_mail($data)
	{
		Settings::getInstance()->update('user', ['account_id' => $data['account_id']]);
		$preferences = createObject('phpgwapi.preferences', $data['data_id']);
		Settings::getInstance()->update('user', ['preferences' => $preferences->read()]);

		$boPreferences = CreateObject('felamimail.bopreferences');

		$bofelamimail = CreateObject('felamimail.bofelamimail');

		//			$bofelamimail->closeConnection();
		//			$boPreferences->setProfileActive(false);
		//			$boPreferences->setProfileActive(true,2); //2 for selected user

		$connectionStatus	 = $bofelamimail->openConnection();
		$headers			 = $bofelamimail->getHeaders('INBOX', 1, $maxMessages		 = 15, $sort				 = 0, $_reverse			 = 1, $_filter			 = array(
			'string' => '', 'type'	 => 'quick', 'status' => 'unseen'
		));

		//_debug_array($headers);
		//die();

		$sms = array();
		$j	 = 0;
		if (isset($headers['header']) && is_array($headers['header']))
		{
			foreach ($headers['header'] as $header)
			{
				//			if(!$header['seen'])
				{
					$sms[$j]['message']	 = mb_convert_encoding($header['subject'], 'UTF-8', 'ISO-8859-1');
					$bodyParts			 = $bofelamimail->getMessageBody($header['uid']);
					$sms[$j]['message']	 .= "\n";
					for ($i = 0; $i < count($bodyParts); $i++)
					{
						$sms[$j]['message'] .= mb_convert_encoding($bodyParts[$i]['body'], 'UTF-8', 'ISO-8859-1') . "\n";
					}

					$sms[$j]['message'] = substr($sms[$j]['message'], 0, 160);
					$j++;
				}
				$bofelamimail->flagMessages('read', $header['uid']);
			}
		}

		if ($connectionStatus)
		{
			$bofelamimail->closeConnection();
		}

		$bosms = CreateObject('sms.bosms', false);
		foreach ($sms as $entry)
		{
			$bosms->send_sms(array('p_num_text' => $data['cellphone'], 'message' => $entry['message']));
		}

		if ($j)
		{
			$msg						 = $j . ' meldinger er sendt';
			$this->receipt['message'][]	 = array('msg' => $msg);
		}
	}
}
