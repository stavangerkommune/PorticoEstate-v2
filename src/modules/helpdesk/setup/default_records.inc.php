<?php
	/**
	* phpGroupWare - helpdesk: a Facilities Management System.
	*
	* @author Sigurd Nes <sigurdne@online.no>
	* @copyright Copyright (C) 2003-2005 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	* @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
	* @package helpdesk
	* @subpackage setup
 	* @version $Id: default_records.inc.php 6689 2010-12-21 14:23:40Z sigurdne $
	*/


	/**
	 * Description
	 * @package helpdesk
	 */

use App\Database\Db;

$db				 = Db::getInstance();

$db->query("SELECT app_id FROM phpgw_applications WHERE app_name = 'helpdesk'");
$db->next_record();
$app_id = $db->f('app_id');

#
#  phpgw_locations
#

$db->query("INSERT INTO phpgw_locations (app_id, name, descr, allow_grant) VALUES ({$app_id}, '.', 'Top', 1)");
$db->query("INSERT INTO phpgw_locations (app_id, name, descr) VALUES ({$app_id}, '.admin', 'Admin')");
$db->query("INSERT INTO phpgw_locations (app_id, name, descr, allow_grant, allow_c_function, allow_c_attrib, c_attrib_table) VALUES ({$app_id}, '.ticket', 'Helpdesk', 1, 1, 1, 'phpgw_helpdesk_tickets')");
$db->query("INSERT INTO phpgw_locations (app_id, name, descr) VALUES ({$app_id}, '.ticket.order', 'Helpdesk ad hock order')");
$db->query("INSERT INTO phpgw_locations (app_id, name, descr) VALUES ({$app_id}, '.custom', 'Custom reports')");
$db->query("INSERT INTO phpgw_locations (app_id, name, descr) VALUES ({$app_id}, '.ticket.response_template', 'Ticket response template')");
$db->query("INSERT INTO phpgw_locations (app_id, name, descr) VALUES ({$app_id}, '.email_out', 'email out')");

$db->query("DELETE from phpgw_config WHERE config_app='helpdesk'");
