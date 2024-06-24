<?php

/**
 * phpGroupWare - bim: a Facilities Management System.
 *
 * @author Sigurd Nes <sigurdne@online.no>
 * @copyright Copyright (C) 2003-2009 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
 * @package bim
 * @subpackage setup
 * @version $Id: tables_update.inc.php 6982 2011-02-14 20:01:17Z sigurdne $
 */
/**
 * Update bim version from 0.9.17.500 to 0.9.17.501
 */

use App\modules\phpgwapi\services\Settings;

$test[] = '0.9.17.500';

function bim_upgrade0_9_17_500($oProc)
{
	$oProc->m_odb->transaction_begin();
	$oProc->AddColumn('fm_bim_type', 'location_id', array(
		'type' => 'int', 'precision' => 4, 'nullable' => True
	));
	$oProc->AddColumn('fm_bim_type', 'is_ifc', array(
		'type' => 'int',
		'precision' => 2, 'default' => 1, 'nullable' => True
	));

	$oProc->AddColumn('fm_bim_item', 'p_location_id', array(
		'type' => 'int', 'precision' => '4', 'nullable' => True
	));
	$oProc->AddColumn('fm_bim_item', 'p_id', array(
		'type' => 'int',
		'precision' => '4', 'nullable' => True
	));
	$oProc->AddColumn('fm_bim_item', 'location_code', array(
		'type' => 'varchar', 'precision' => '20', 'nullable' => true
	));
	$oProc->AddColumn('fm_bim_item', 'address', array(
		'type' => 'varchar',
		'precision' => '150', 'nullable' => True
	));
	$oProc->AddColumn('fm_bim_item', 'entry_date', array(
		'type' => 'int',
		'precision' => '4', 'nullable' => True
	));
	$oProc->AddColumn('fm_bim_item', 'user_id', array(
		'type' => 'int',
		'precision' => '4', 'nullable' => True
	));

	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.501';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
/**
 * Update bim version from 0.9.17.501 to 0.9.17.502
 */
$test[] = '0.9.17.501';

function bim_upgrade0_9_17_501($oProc)
{
	$oProc->m_odb->transaction_begin();
	$oProc->AlterColumn('fm_bim_item', 'guid', array(
		'type' => 'varchar',
		'precision' => '50', 'nullable' => False
	));
	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.502';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
/**
 * Update bim version from 0.9.17.502 to 0.9.17.503
 */
$test[] = '0.9.17.502';

function bim_upgrade0_9_17_502($oProc)
{
	$oProc->m_odb->transaction_begin();
	$oProc->AddColumn('fm_bim_item', 'loc1', array(
		'type' => 'varchar',
		'precision' => '6', 'nullable' => true
	));
	$oProc->query('ALTER TABLE fm_bim_item DROP CONSTRAINT fm_bim_item_pkey', __LINE__, __FILE__);
	$oProc->query('ALTER TABLE fm_bim_item ADD CONSTRAINT fm_bim_item_pkey PRIMARY KEY(type,id)', __LINE__, __FILE__);
	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.503';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
/**
 * Update bim version from 0.9.17.503 to 0.9.17.504
 */
$test[] = '0.9.17.503';

function bim_upgrade0_9_17_503($oProc)
{
	$oProc->m_odb->transaction_begin();

	$oProc->AlterColumn('fm_bim_type', 'name', array(
		'type' => 'varchar',
		'precision' => '150', 'nullable' => False
	));
	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.504';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
/**
 * Update bim version from 0.9.17.504 to 0.9.17.505
 */
$test[] = '0.9.17.504';

function bim_upgrade0_9_17_504($oProc)
{
	$oProc->m_odb->transaction_begin();

	$oProc->AddColumn('fm_bim_item', 'location_id', array(
		'type' => 'int', 'precision' => 4, 'nullable' => true
	));


	$oProc->query("SELECT * FROM fm_bim_type", __LINE__, __FILE__);

	$types = array();
	while ($oProc->next_record())
	{
		$types[] = array(
			'id'			=> (int)$oProc->f('id'),
			'location_id'	=> (int)$oProc->f('location_id'),
			'name' => $oProc->f('name', true),
			'description' => $oProc->f('description', true)
		);
	}

	$location_obj = new \App\modules\phpgwapi\controllers\Locations();
	foreach ($types as $entry)
	{
		if (!$location_id = $entry['location_id'])
		{
			$location_id = $location_obj->add($entry['name'], $entry['description'], 'bim');
		}

		$oProc->query("UPDATE fm_bim_item SET location_id = {$location_id} WHERE type = {$entry['id']}", __LINE__, __FILE__);
	}

	$oProc->AlterColumn('fm_bim_item', 'location_id', array(
		'type' => 'int', 'precision' => 4, 'nullable' => false
	));

	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.505';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
/**
 * Update bim version from 0.9.17.505 to 0.9.17.506
 */
$test[] = '0.9.17.505';

function bim_upgrade0_9_17_505($oProc)
{
	$oProc->m_odb->transaction_begin();

	$oProc->CreateTable(
		'fm_bim_item_inventory',
		array(
			'fd' => array(
				'id' => array('type' => 'auto', 'precision' => 4, 'nullable' => False),
				'location_id' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'item_id' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'p_location_id' => array('type' => 'int', 'precision' => 4, 'nullable' => True),
				'p_id' => array('type' => 'int', 'precision' => 4, 'nullable' => True),
				'unit_id' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'inventory' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'write_off' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'bookable' => array('type' => 'int', 'precision' => 2, 'nullable' => False),
				'active_from' => array('type' => 'int', 'precision' => 8, 'nullable' => True),
				'active_to' => array('type' => 'int', 'precision' => 8, 'nullable' => True),
				'created_on' => array('type' => 'int', 'precision' => 8, 'nullable' => False),
				'created_by' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'expired_on' => array('type' => 'int', 'precision' => 8, 'nullable' => True),
				'expired_by' => array('type' => 'int', 'precision' => 8, 'nullable' => True),
				'remark' => array('type' => 'text', 'nullable' => True)
			),
			'pk' => array('id'),
			'fk' => array(), //'fm_bim_item' => array('location_id' => 'location_id')), 'item_id'=> 'id')),
			'ix' => array(),
			'uc' => array()
		)
	);

	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.506';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
/**
 * Update bim version from 0.9.17.506 to 0.9.17.507
 */
$test[] = '0.9.17.506';

function bim_upgrade0_9_17_506($oProc)
{
	$oProc->m_odb->transaction_begin();

	$oProc->AddColumn('fm_bim_item', 'department_id', array(
		'type' => 'int', 'precision' => 4, 'nullable' => true
	));

	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.507';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
/**
 * Update bim version from 0.9.17.507 to 0.9.17.508
 */
$test[] = '0.9.17.507';

function bim_upgrade0_9_17_507($oProc)
{
	$oProc->m_odb->transaction_begin();

	$oProc->RenameColumn('fm_bim_item', 'department_id', 'org_unit_id');

	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.508';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
/**
 * Update bim version from 0.9.17.508 to 0.9.17.509
 */
$test[] = '0.9.17.508';

function bim_upgrade0_9_17_508($oProc)
{
	$oProc->m_odb->transaction_begin();

	$oProc->AddColumn('fm_bim_item', 'entity_group_id', array(
		'type' => 'int', 'precision' => 4, 'nullable' => true
	));

	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.509';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
/**
 * Update bim version from 0.9.17.509 to 0.9.17.510
 */
$test[] = '0.9.17.509';

function bim_upgrade0_9_17_509($oProc)
{
	$oProc->m_odb->transaction_begin();

	$oProc->AddColumn('fm_bim_item', 'modified_by', array(
		'type' => 'int', 'precision' => 4, 'nullable' => true
	));
	$oProc->AddColumn('fm_bim_item', 'modified_on', array(
		'type' => 'int', 'precision' => 8, 'nullable' => true
	));

	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.510';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
/**
 * Update bim version from 0.9.17.510 to 0.9.17.511
 */
$test[] = '0.9.17.510';

function bim_upgrade0_9_17_510($oProc)
{
	$oProc->m_odb->transaction_begin();

	$oProc->AddColumn('fm_bim_item', 'json_representation', array(
		'type' => 'jsonb', 'nullable' => true
	));

	$oProc->query("SELECT id,location_id,xml_representation FROM fm_bim_item", __LINE__, __FILE__);

	$items = array();
	while ($oProc->next_record())
	{
		$items[] = array(
			'id'			=> (int)$oProc->f('id'),
			'location_id'	=> (int)$oProc->f('location_id'),
			'xml_representation' => $oProc->f('xml_representation', true),
		);
	}

	$xmlparse = CreateObject('property.XmlToArray');
	$xmlparse->setEncoding('UTF-8');
	$xmlparse->setDecodesUTF8Automaticly(false);

	foreach ($items as $item)
	{
		$xmldata = $item['xml_representation'];
		$var_result = $xmlparse->parse($xmldata);

		$jsondata = json_encode($var_result, JSON_HEX_APOS);
		$oProc->query("UPDATE fm_bim_item SET json_representation = '{$jsondata}'"
			. " WHERE id = {$item['id']} AND location_id = {$item['location_id']}", __LINE__, __FILE__);
	}

	$oProc->AlterColumn('fm_bim_item', 'json_representation', array(
		'type' => 'jsonb', 'nullable' => False
	));

	$oProc->DropColumn('fm_bim_item', array(), 'xml_representation');

	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.511';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}

/**
 * Update bim version from 0.9.17.511 to 0.9.17.512
 */
$test[] = '0.9.17.511';

function bim_upgrade0_9_17_511($oProc)
{
	$oProc->m_odb->transaction_begin();

	$oProc->query("ALTER TABLE fm_bim_item
		ADD CONSTRAINT fm_bim_item_id_location_id_key UNIQUE (id, location_id)");

	$oProc->query("ALTER TABLE fm_bim_type
		ADD CONSTRAINT fm_bim_type_location_id_key UNIQUE (location_id)");

	$oProc->CreateTable(
		'fm_bim_item_checklist',
		array(
			'fd' => array(
				'id' => array('type' => 'auto', 'precision' => 4, 'nullable' => False),
				'type_location_id' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'location_id' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'name' => array('type' => 'varchar', 'precision' => 150, 'nullable' => False),
				'descr' => array('type' => 'text', 'nullable' => True),
				'active' => array('type' => 'int', 'precision' => 2, 'nullable' => true),
				'fileupload' => array('type' => 'int', 'precision' => 2, 'nullable' => true),
			),
			'pk' => array('id'),
			'fk' => array('fm_bim_type' => array('type_location_id' => 'location_id')),
			'ix' => array(),
			'ix' => array(),
			'uc' => array('location_id')
		)
	);

	$oProc->CreateTable(
		'fm_bim_item_checklist_stage',
		array(
			'fd' => array(
				'id' => array('type' => 'auto', 'precision' => 4, 'nullable' => False),
				'checklist_id' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'name' => array('type' => 'varchar', 'precision' => 50, 'nullable' => False),
				'descr' => array('type' => 'text', 'nullable' => True),
				'stage_sort' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'active' => array('type' => 'int', 'precision' => 2, 'nullable' => true),
				'active_attribs' => array('type' => 'jsonb', 'nullable' => true),
			),
			'pk' => array('id'),
			'fk' => array('fm_bim_item_checklist' => array('checklist_id' => 'id')),
			'ix' => array(),
			'uc' => array()
		)
	);

	$oProc->CreateTable(
		'fm_bim_item_checklist_data',
		array(
			'fd' => array(
				'id' => array('type' => 'auto', 'precision' => 4, 'nullable' => False),
				'item_id' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'type_location_id' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'stage_id' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'created_on' => array('type' => 'int', 'precision' => 8, 'nullable' => False),
				'created_by' => array('type' => 'int', 'precision' => 4, 'nullable' => False),
				'json_representation' => array('type' => 'jsonb', 'nullable' => False),
			),
			'pk' => array('id'),
			'fk' => array(
				'fm_bim_item' => array('item_id' => 'id', 'type_location_id' => 'location_id'),
				'fm_bim_item_checklist_stage' => array('stage_id' => 'id'),
			),
			'ix' => array(),
			'uc' => array()
		)
	);


	if ($oProc->m_odb->transaction_commit())
	{
		$currentver = '0.9.17.512';
		Settings::getInstance()->update('setup_info', ['bim' => ['currentver' => $currentver]]);
		return $currentver;
	}
}
