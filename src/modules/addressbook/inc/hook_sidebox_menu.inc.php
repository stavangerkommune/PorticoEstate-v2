<?php
	$menu_title = $GLOBALS['phpgw_info']['apps'][$appname]['title'] . ' '. lang('Menu');

	$file = array
	(
		array
		(
			'text'	=> 'New Person',
			'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook.add_person'))
		),
		
		array
		(
			'text'	=> 'New Org',
			'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiaddressbook.add_org'))
		),

		array('text' => '_NewLine_'),
		
		array
		(
			'text'	=> 'Add VCard',
			'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uivcard.in'))
		),

		array
		(
			'text'	=> 'Categorize Persons',
			'url'	=>  phpgw::link('/index.php', array('menuaction' => 'addressbook.uicategorize_contacts.index'))
		),
		
		array
		(
			'text'	=> 'Import Contacts',
			'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiXport.import'))
		),
		
		array
		(
			'text'	=> 'Import CSV',
			'url'	=> phpgw::link('/addressbook/csv_import.php')
		),

		array
		(
			'text'	=> 'Export Contacts',
			'url'	=> phpgw::link('/index.php', array('menuaction' => 'addressbook.uiXport.export'))
		)
	);

	display_sidebox($appname,$menu_title,$file);
