<?php

/**
 * frontend
 * @copyright Copyright (C) 2010 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package frontend
 * @subpackage setup
 * @version $Id$
 */

use App\modules\phpgwapi\controllers\Locations;

$location_obj = new Locations();

$location_obj->add('.', 'top', 'frontend', false);
$location_obj->add('.ticket', 'helpdesk', 'frontend', false);
$location_obj->add('.rental.contract', 'contract_internal', 'frontend', false);
$location_obj->add('.rental.contract_in', 'contract_in', 'frontend', false);
$location_obj->add('.rental.contract_ex', 'contract_ex', 'frontend', false);
$location_obj->add('.document.drawings', 'drawings', 'frontend', false);
$location_obj->add('.document.pictures', 'pictures', 'frontend', false);
$location_obj->add('.document.contracts', 'contract_documents', 'frontend', false);
$location_obj->add('.property.maintenance', 'maintenance', 'frontend', false);
$location_obj->add('.property.refurbishment', 'refurbishment', 'frontend', false);
$location_obj->add('.property.services', 'services', 'frontend', false);
$location_obj->add('.delegates', 'delegates', 'frontend', false);
$location_obj->add('.controller', 'controller', 'frontend', false);
