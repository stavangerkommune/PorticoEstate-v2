<?php
	/*	 * ************************************************************************\
	 * phpGroupWare - Setup                                                     *
	 * http://www.phpgroupware.org                                              *
	 * --------------------------------------------                             *
	 *  This program is free software; you can redistribute it and/or modify it *
	 *  under the terms of the GNU General Public License as published by the   *
	 *  Free Software Foundation; either version 2 of the License, or (at your  *
	 *  option) any later version.                                              *
	  \************************************************************************* */

	/* $Id$ */

	$test[] = '0.8.1';
	function registration_upgrade0_8_1($oProc)
	{
		$oProc->CreateTable('phpgw_reg_fields', array(
			'fd' => array(
				'field_name' => array('type' => 'varchar', 'precision' => 255, 'nullable' => False),
				'field_text' => array('type' => 'text', 'nullable' => False),
				'field_type' => array('type' => 'varchar', 'precision' => 255, 'nullable' => True),
				'field_values' => array('type' => 'text', 'nullable' => True),
				'field_required' => array('type' => 'char', 'precision' => 1, 'nullable' => True),
				'field_order' => array('type' => 'int', 'precision' => 4, 'nullable' => True)
			),
			'pk' => array(),
			'ix' => array(),
			'fk' => array(),
			'uc' => array()
		));

		$currentver = '0.8.2';
		return $currentver;
	}

	$test[] = '0.8.2';
	function registration_upgrade0_8_2($oProc)
	{
		$oProc->m_odb->transaction_begin();

		$oProc->AddColumn('phpgw_reg_accounts', 'reg_approved', array(
			'type' => 'int', 'precision' => 2, 'nullable' => True));

		if ($oProc->m_odb->transaction_commit())
		{
			$currentver = '0.8.3';
			return $currentver;
		}
	}

	$test[] = '0.8.3';
	function registration_upgrade0_8_3($oProc)
	{
		$oProc->m_odb->transaction_begin();

		$oProc->AlterColumn('phpgw_reg_accounts', 'reg_info', array(
			'type' => 'text', 'nullable' => True));

		if ($oProc->m_odb->transaction_commit())
		{
			$currentver = '0.8.4';
			return $currentver;
		}
	}

	$test[] = '0.8.4';
	function registration_upgrade0_8_4($oProc)
	{
		$oProc->m_odb->transaction_begin();

		$asyncservice = CreateObject('phpgwapi.asyncservice');
		$asyncservice->set_timer(
			array('hour' => "*/2"), 'registration_clear_reg_accounts', 'registration.hook_helper.clear_reg_accounts', null
		);

		if ($oProc->m_odb->transaction_commit())
		{
			$currentver = '0.8.5';
			return $currentver;
		}
	}
