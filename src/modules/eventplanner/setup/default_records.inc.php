<?php

/**
 * phpGroupWare - eventplanner
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2016 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
 * @package eventplanner
 * @subpackage setup
 * @version $Id: default_records.inc.php 14728 2016-02-11 22:28:46Z sigurdne $
 */
/**
 * Description
 * @package eventplanner
 */

use App\modules\phpgwapi\controllers\Locations;

$location_obj = new Locations();

$location_obj->add('.', 'Tom', 'eventplanner');
$location_obj->add('.admin', 'admin', 'eventplanner');
$location_obj->add('.application', 'application', 'eventplanner', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.events', 'events', 'eventplanner', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.customer', 'customer', 'eventplanner', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.vendor', 'vendor', 'eventplanner', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.calendar', 'calendar', 'eventplanner', $allow_grant = true);
$location_obj->add('.booking', 'booking', 'eventplanner', $allow_grant = true, $custom_tbl = '', $c_function = true);
$location_obj->add('.vendor_report', 'vendor_report', 'eventplanner', $allow_grant = true, $custom_tbl = '', $c_function = true, $c_attrib = true);
$location_obj->add('.customer_report', 'customer_report', 'eventplanner', $allow_grant = true, $custom_tbl = '', $c_function = true, $c_attrib = true);
