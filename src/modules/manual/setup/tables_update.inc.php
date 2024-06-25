<?php

/**
 * phpGroupWare - Manual
 *
 * @copyright Copyright (C) 2003,2004,2005,2006,2007 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package manual
 * @subpackage setup
 * @version $Id$
 */

use App\modules\phpgwapi\controllers\Locations;

/**
 * Update manual version from 0.9.13.002 to 0.9.17.500
 */
$test[] = '0.9.13.002';

function manual_upgrade0_9_13_002($oProc)
{
	return '0.9.17.500';
}
$test[] = '0.9.17.500';

function manual_upgrade0_9_17_500($oProc)
{
	$location_obj = new Locations();

	$location_obj->add('.documents', 'Documents', 'manual');

	return '0.9.17.501';
}
