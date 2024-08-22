<?php

/**
 * phpGroupWare - HRM: a  human resource competence management system.
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2003-2005 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
 * @package hrm
 * @subpackage setup
 * @version $Id$
 */

use App\Database\Db;

/**
 * Description
 * @package hrm
 */
$db = Db::getInstance();
//	$app_id = $GLOBALS['phpgw']->applications->name2id('hrm');
$db->query("SELECT app_id FROM phpgw_applications WHERE app_name = 'hrm'");
$db->next_record();
$app_id = $db->f('app_id');

$db->query("DELETE FROM phpgw_locations where app_id = {$app_id} AND name != 'run'");
$db->query("INSERT INTO phpgw_locations (app_id, name, descr) VALUES ({$app_id}, '.', 'Top')");
$db->query("INSERT INTO phpgw_locations (app_id, name, descr, allow_grant) VALUES ({$app_id}, '.user', 'User',1)");
$db->query("INSERT INTO phpgw_locations (app_id, name, descr) VALUES ({$app_id}, '.job', 'Job description')");
