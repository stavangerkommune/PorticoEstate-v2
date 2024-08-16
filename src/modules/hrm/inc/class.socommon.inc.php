<?php

/**
 * phpGroupWare - HRM: a  human resource competence management system.
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2003-2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
 * @package hrm
 * @subpackage core
 * @version $Id$
 */

use App\Database\Db;
use App\modules\phpgwapi\services\Settings;

/**
 * Description
 * @package hrm
 */

class hrm_socommon
{
	var $account, $db, $join, $like, $left_join, $total_records;

	function __construct()
	{
		$this->db = Db::getInstance();
		$this->like 	= $this->db->like;
		$this->join 	= $this->db->join;
		$this->left_join = $this->db->left_join;

		$userSettings = Settings::getInstance()->get('user');
		$this->account	= $userSettings['account_id'];
	}

	function create_preferences($app = '', $user_id = '')
	{
		$this->db->query("SELECT preference_json FROM phpgw_preferences where preference_app = '$app' AND preference_owner=" . (int)$user_id);
		$this->db->next_record();
		$value = json_decode($this->db->f('preference_json'), true);
		return $value;
	}
}
