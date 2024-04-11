<?php
	/**
	* VFS class loader
	* @copyright Copyright (C) 2004 Free Software Foundation, Inc. http://www.fsf.org/
	* @license http://www.fsf.org/licenses/lgpl.html GNU Lesser General Public License
	* @package phpgwapi
	* @subpackage vfs
	* @version $Id$
	*/
	namespace App\Modules\PhpGWApi\Services\Vfs;

	$serverSettings  = \App\Modules\PhpGWApi\Services\Settings::getInstance()->get('server');	
	if ( !isset($serverSettings['file_repository']) 
		|| empty($serverSettings['file_repository']) )
	{
		$serverSettings['file_repository'] = 'sql';
	}
	$file_repository = ucfirst($serverSettings['file_repository']);

	/**
	* Include shared vfs class
	*/
	require_once  SRC_ROOT_PATH . '/Services/Vfs/VfsShared.php';
	/**
	* Include vfs class
	*/
	require_once SRC_ROOT_PATH . "/Services/Vfs/Vfs{$file_repository}.php";
