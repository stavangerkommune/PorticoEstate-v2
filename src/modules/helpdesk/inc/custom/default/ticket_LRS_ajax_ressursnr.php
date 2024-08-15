<?php
/*
	 * This file will only work for the implementation of LRS
	 */

use App\modules\phpgwapi\controllers\Locations;
use App\modules\phpgwapi\services\Cache;
use App\Database\Db2;

/**
 * Intended for custom validation of ajax-request from form.
 *
 * @author Sigurd Nes <sigurdne@online.no>
 */
if (!class_exists("ticket_LRS_validate_ressurs"))
{
	class ticket_LRS_validate_ressurs
	{

		protected	$config, $db;

		function __construct()
		{
			$location_obj = new Locations();
			$this->config = CreateObject('admin.soconfig', $location_obj->get_id('property', '.admin'));
		}


		function ping($host)
		{
			exec(sprintf('ping -c 1 -W 5 %s', escapeshellarg($host)), $res, $rval);
			return $rval === 0;
		}

		public function get_db()
		{
			if ($this->db && is_object($this->db))
			{
				return $this->db;
			}

			if (!$this->config->config_data['fellesdata']['host'] || !$this->ping($this->config->config_data['fellesdata']['host']))
			{
				$message = "Database server {$this->config->config_data['fellesdata']['host']} is not accessible";
				Cache::message_set($message, 'error');
				return false;
			}
			$dsn = Db2::CreateDsn([
				'db_host' => $this->config->config_data['fellesdata']['host'],
				'db_port' => $this->config->config_data['fellesdata']['port'],
				'db_name' => $this->config->config_data['fellesdata']['db_name'],
				'db_type' => 'oracle'
			]);

			try
			{
				$db = new Db2(
					$dsn,
					$this->config->config_data['fellesdata']['user'],
					$this->config->config_data['fellesdata']['password'],
					[
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
						PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
						PDO::ATTR_EMULATE_PREPARES => false
					]
				);
			}
			catch (Exception $e)
			{
				$status = lang('unable_to_connect_to_database');
				Cache::message_set($status, 'error');
				return false;
			}

			$this->db = $db;
			return $db;
		}

		function get_ressurs_name()
		{
			$ressursnr_id = Sanitizer::get_var('ressursnr_id', 'int');

			if (!$ressursnr_id)
			{
				return;
			}

			if (!$db = $this->get_db())
			{
				return;
			}

			$sql = "SELECT * FROM FELLESDATA.V_PORTICO_ANSATT WHERE RESSURSNR = {$ressursnr_id}";

			$db->query($sql, __LINE__, __FILE__);

			if ($db->next_record())
			{
				$last_name	= $db->f('ETTERNAVN', true);
				$first_name	= $db->f('FORNAVN', true);
				$email	= $db->f('EPOST', true);
				$ret = "{$last_name}, {$first_name} [{$email}]";
			}
			else
			{
				$ret = 'Ugyldig ressursnr';
			}

			return $ret;
		}
	}
}

$method = Sanitizer::get_var('method');

if ($method == 'get_ressurs_name')
{
	$ressurs = new ticket_LRS_validate_ressurs();
	$ajax_result['ressurs_name'] =  $ressurs->get_ressurs_name();
}
