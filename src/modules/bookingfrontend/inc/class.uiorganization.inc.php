<?php

use App\modules\phpgwapi\services\Translation;
use App\modules\phpgwapi\services\Cache;
use App\modules\bookingfrontend\helpers\UserHelper;

phpgw::import_class('booking.uiorganization');

class bookingfrontend_uiorganization extends booking_uiorganization
{

	public $public_functions = array(
		'show' => true,
		'add' => true,
		'edit' => true,
		'index' => true,
		'building_users' => true,
		'get_orgid' => true,
		'toggle_show_inactive' => true,
		'get_organization_list'	=> true
	);
	protected $module;

	public function __construct()
	{
		parent::__construct();
		$this->module = "bookingfrontend";
	}

	protected function indexing()
	{
		return parent::index_json();
	}

	public function get_orgid($orgnr, $customer_ssn = null)
	{
		return $this->bo->so->get_orgid($orgnr, $customer_ssn);
	}

	public function add()
	{

		/**
		 * check external login - and return here
		 */
		$bouser = new UserHelper();

		$external_login_info = $bouser->validate_ssn_login(array(
			'menuaction' => 'bookingfrontend.uiorganization.add'
		));

		if ($bouser->is_logged_in())
		{
			$orgs = (array)Cache::session_get($bouser->get_module(), $bouser::ORGARRAY_SESSION_KEY);

			$orgs_map = array();
			foreach ($orgs as $org)
			{
				$orgs_map[$org['orgnr']] = $org;
			}
			unset($org);

			$session_org_id = Sanitizer::get_var('session_org_id', 'string', 'GET');

			if ($session_org_id && in_array($session_org_id, array_keys($orgs_map)))
			{
				try
				{
					$org_number = createObject('booking.sfValidatorNorwegianOrganizationNumber')->clean($session_org_id);
					if ($org_number)
					{
						$bouser->change_org($org_number);
					}
				}
				catch (sfValidatorError $e)
				{
					$session_org_id = -1;
				}
			}
			else
			{
				$org_number = $bouser->orgnr;
			}

			$delegate_data = CreateObject('booking.souser')->get_delegate($external_login_info['ssn'], array_keys($orgs_map));

			$delegate_map = array();
			foreach ($delegate_data as $delegate_entry)
			{
				if (empty($delegate_entry['active']))
				{
					continue;
				}

				$delegate_map[] = $delegate_entry['organization_number'];
				if ($delegate_entry['customer_ssn'] == $external_login_info['ssn'])
				{
					$this->personal_org = $delegate_entry['name'];
				}
			}
			$_new_org_list = array_diff(array_keys($orgs_map), $delegate_map);

			$new_org_list = array();

			foreach ($_new_org_list as $key)
			{
				$orgs_map[$key]['selected']	 = $key == $org_number ? 1 : 0;

				$_name = $orgs_map[$key]['orgname'] == $key ? $key : "{$key} [{$orgs_map[$key]['orgname']}]";
				$new_org_list[]				 = array(
					'id'		 => $key,
					'name'		 => $_name,
					'selected'	 => $key == $org_number ? 1 : 0
				);
			}

			$this->new_org_list = $new_org_list;
			$this->ssn = $external_login_info['ssn'];

			self::add_javascript('bookingfrontend', 'base', 'organization_add.js');

			$submitted_organization_number = Sanitizer::get_var('organization_number', 'string', 'POST');
			if ($submitted_organization_number)
			{
				if (!in_array($submitted_organization_number, $_new_org_list))
				{
					return array(
						'status'	 => 'error',
						'message'	 => array('Not authorized for this organization')
					);
				}
			}
			else if ($this->personal_org && 'ssn' == Sanitizer::get_var('customer_identifier_type', 'string', 'POST'))
			{
				return array(
					'status'	 => 'error',
					'message'	 => array("Du har allerede registrert \"{$this->personal_org}\"")
				);
			}

			$ret =  parent::add();

			/**
			 * Refresh list
			 */
			if (Sanitizer::get_var('phpgw_return_as') == 'json' && $ret['status'] == 'saved')
			{
				$bouser->log_in();
			}
			return $ret;
		}
		else
		{
			self::render_template_xsl('access_denied', array());
		}
	}

	public function edit()
	{

		$bouser = new UserHelper();

		$id = Sanitizer::get_var('id', 'int');
		$session_org_id = Sanitizer::get_var('session_org_id', 'bool') ? Sanitizer::get_var('session_org_id') :  $bouser->orgnr;

		if (!$id && $session_org_id)
		{
			$id = CreateObject('bookingfrontend.uiorganization')->get_orgid($session_org_id, $bouser->ssn);
			$_GET['id'] = $id;
		}

		$organization = $this->bo->read_single($id);

		if (isset($organization['permission']['write']))
		{
			parent::edit();
		}
		else
		{
			self::render_template_xsl('access_denied', array());
		}
	}

	public function show()
	{
		$bouser = new UserHelper();
		$config = CreateObject('phpgwapi.config', 'booking');
		$config->read();
		$id = Sanitizer::get_var('id', 'int');

		$session_org_id = Sanitizer::get_var('session_org_id', 'bool') ? Sanitizer::get_var('session_org_id') :  $bouser->orgnr;

		if (!$id && $session_org_id)
		{
			$id = CreateObject('bookingfrontend.uiorganization')->get_orgid($session_org_id, $bouser->ssn);
		}

		$organization = $this->bo->read_single($id);
		$organization['organizations_link'] = self::link(array('menuaction' => $this->module . '.uiorganization.index'));
		$organization['edit_link'] = self::link(array(
			'menuaction' => $this->module . '.uiorganization.edit',
			'id' => $organization['id']
		));
		$organization['start'] = self::link(array(
			'menuaction' => 'bookingfrontend.uisearch.index',
			'type' => "organization"
		));

		$translation = Translation::getInstance();
		$userlang = $translation->get_userlang();
		$organization['description'] = !empty($organization['description_json'][$userlang]) ? $organization['description_json'][$userlang] : $organization['description_json']['no'];

		$organization['contact_info'] = "";
		$contactdata = array();
		foreach (array('homepage', 'email', 'phone') as $field)
		{
			if (!empty(trim($organization[$field])))
			{
				$value = trim($organization[$field]);
				if ($field == 'homepage')
				{
					if (!preg_match("/^(http|https):\/\//", $value))
					{
						$value = 'http://' . $value;
					}
					$value = sprintf('<a href="%s" target="_blank">%s</a>', $value, $value);
				}
				if ($field == 'email')
				{
					$value = "<a href=\"mailto:{$value}\">{$value}</a>";
				}

				$contactdata[] = sprintf('%s: %s', lang($field), $value);
			}
		}
		if (!empty($contactdata))
		{
			$organization['contact_info'] = sprintf('<p>%s</p>', join('<br/>', $contactdata));
		}

		$auth_forward = "?redirect_menuaction={$this->module}.uiorganization.show&redirect_id={$organization['id']}";

		// BEGIN EVIL HACK
		$auth_forward .= '&orgnr=' . $organization['organization_number'];
		// END EVIL HACK

		$organization['login_link'] = 'login/' . $auth_forward;
		$organization['logoff_link'] = 'logoff.php' . $auth_forward;
		$organization['new_group_link'] = self::link(array(
			'menuaction' => $this->module . '.uigroup.edit',
			'organization_id' => $organization['id']
		));
		$organization['new_delegate_link'] = self::link(array(
			'menuaction' => $this->module . '.uidelegate.edit',
			'organization_id' => $organization['id']
		));
		//			if ($bouser->is_organization_admin($organization['id'], $organization['organization_number']))
		//			{
		//				$organization['logged_on'] = true;
		//			}

		if (isset($organization['permission']['write']))
		{
			$organization['logged_on'] = true;
		}

		phpgwapi_jquery::load_widget("core");

		phpgwapi_js::getInstance()->add_external_file("phpgwapi/templates/bookingfrontend/js/build/aui/aui-min.js");
		self::add_javascript('bookingfrontend', 'base', 'organization.js', true);
		//            _debug_array(array('organization' => $organization, 'config_data' => $config->config_data));die();
		self::render_template_xsl('organization', array('organization' => $organization, 'config_data' => $config->config_data));
	}
	public function index()
	{
		if (Sanitizer::get_var('phpgw_return_as') == 'json')
		{
			return $this->query();
		}

		phpgw::no_access();
	}

	public function get_organization_list()
	{
		$page = Sanitizer::get_var('page', 'int');
		$query = Sanitizer::get_var('query', 'string');
		$length = $this->userSettings['preferences']['common']['maxmatchs'];
		$start = ($page - 1) * $length;

		$organizations = $this->bo->read(array(
			'start' => $start,
			'query' => $query,
			'results' => $length,
			'filters' => array(
				'active' => 1, 'where' => array(
					//		'%%table%%.organization_number IS NOT NULL',
					//		"%%table%%.organization_number != ''",
					"length(%%table%%.organization_number) = 9"
				)
			)
		));
		$total = $organizations['total_records'];

		$results = array();
		foreach ($organizations['results'] as $entry)
		{
			$results[] = array(
				'id'	=> "{$entry['id']}_{$entry['organization_number']}",
				'text' => "{$entry['organization_number']} [{$entry['name']}]",
				'disabled' => $entry['active'] ? false : true,
				'name' => $entry['name'],
				'organization_number' => $entry['organization_number']
			);
		}

		$num_records = count($results);

		$morePages = $total > ($num_records + $start);

		return array(
			'results'	=> $results,
			'start'		=> $start,
			'pagination' => array(
				'more' => $morePages
			)
		);
	}
}
