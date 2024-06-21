<?php
	/**
	 * phpGroupWare - property: a Facilities Management System.
	 *
	 * @author Sigurd Nes <sigurdne@online.no>
	 * @copyright Copyright (C) 2003-2005 Free Software Foundation, Inc. http://www.fsf.org/
	 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
	 * @internal Development of this application was funded by http://www.bergen.kommune.no/bbb_/ekstern/
	 * @package property
	 * @subpackage custom
	 * @version $Id$
	 */
/**
 * Description
 * usage: * example cron : /usr/bin/php -q /var/www/Api/src/modules/property/inc/cron/cron.php default update_phpgw
 * @package property
 */
	include_class('property', 'cron_parent', 'inc/cron/');

	use App\modules\phpgwapi\services\setup\Detection;
	use App\modules\phpgwapi\services\setup\Process;

	class update_phpgw extends property_cron_parent
	{
		private $detection;
		function __construct()
		{
			parent::__construct();

			$this->function_name = get_class($this);
			$this->sub_location	 = lang('Async service');
			$this->function_msg	 = 'Update all installed apps of phpgw';

			$this->detection = new Detection();

		}

		function execute()
		{
			$this->perform_update_db();
		}

		function perform_update_db()
		{

			$setup_info = $this->detection->get_versions();
			/* Check current versions and dependencies */
			$setup_info = $this->detection->get_db_versions($setup_info);
			$setup_info = $this->detection->compare_versions($setup_info);
			$setup_info = $this->detection->check_depends($setup_info);

			$process = new Process();

			ksort($setup_info);
			$clear_cache	 = '';
			$message		 = array();
			foreach ($setup_info as $app => $appinfo)
			{
				if (isset($appinfo['status']) && $appinfo['status'] == 'U' && !empty($appinfo['currentver']))
				{
					$terror						 = array();
					$terror[]					 = $setup_info[$appinfo['name']];
					$process->upgrade($terror, false);
					$process->upgrade_langs($terror, false);
					$message[]	 = array('msg' => 'Upgraded application: ' . $appinfo['name']);
					if ($appinfo['name'] == 'property')
					{
						$clear_cache = true;
					}
				}
			}
			if ($clear_cache)
			{
				$this->db->query('DELETE FROM fm_cache');
			}
			print_r($message);
		}
	}