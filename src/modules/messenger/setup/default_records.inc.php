<?php

/**
 * messenger
 * @copyright Copyright (C) 2010 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package frontend
 * @subpackage setup
 * @version $Id: default_records.inc.php 4175 2009-11-22 14:00:45Z sigurd $
 */

use App\modules\phpgwapi\controllers\Locations;

$location_obj = new Locations();

$location_obj->add('.', 'top', 'messenger', false);
$location_obj->add('.compose', 'compose messages to users', 'messenger', false);
$location_obj->add('.compose_groups', 'compose messages to groups', 'messenger', false);
$location_obj->add('.compose_global', 'compose global message', 'messenger', false);
