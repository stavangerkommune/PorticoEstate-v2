<?php

use App\modules\phpgwapi\controllers\Accounts\Accounts;

class booking_account_ui_utils
{

	public static function yui_accounts()
	{
		$query = Sanitizer::get_var('query');

		$accounts_obj = new Accounts();
		$account_info = $accounts_obj->get_list('accounts', 0, 'lid', '', $query, 20);
		$x = 0;

		$result = array();

		foreach ($account_info as $account)
		{
			$firstname = $account->firstname;
			$lastname = $account->lastname;
			$lastname and $firstname .= ' ';
			$result[] = array(
				'name' => sprintf('%s (%s%s)', $account->lid, $firstname, $lastname),
				'id' => $account->id,
			);
		}

		$data = array(
			'ResultSet' => array(
				"totalResultsAvailable" => $accounts_obj->total,
				"Result" => $result
			)
		);
		return $data;
	}
}
