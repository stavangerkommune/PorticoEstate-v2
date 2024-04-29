<?php

/**
 * phpGroupWare Administration Misc Page Renderers
 *
 * @author Dave Hall <skwashd@phpgroupware.org>
 * @author coreteam <phpgroupware-developers@gnu.org>
 * @author Various Others <unknown>
 * @copyright Copyright (C) 2003-2008 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package phpgroupware
 * @subpackage phpgwapi
 * @category gui
 * @version $Id$
 */

/*
	   This program is free software: you can redistribute it and/or modify
	   it under the terms of the GNU General Public License as published by
	   the Free Software Foundation, either version 2 of the License, or
	   (at your option) any later version.

	   This program is distributed in the hope that it will be useful,
	   but WITHOUT ANY WARRANTY; without even the implied warranty of
	   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	   GNU General Public License for more details.

	   You should have received a copy of the GNU General Public License
	   along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */

/**
 *  Miscellaneous Admin Pages renderer 
 *
 * @author Dave Hall <skwashd@phpgroupware.org>
 * @author coreteam <phpgroupware-developers@gnu.org>
 * @author Various Others <unknown>
 * @copyright Copyright (C) 2003-2008 Free Software Foundation, Inc. http://www.fsf.org/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package phpgroupware
 * @subpackage phpgwapi
 */

use App\modules\phpgwapi\services\Settings;
use App\helpers\Template;


class admin_uimainscreen
{
	/**
	 * @var array $public_functions the publicly callable methods
	 */
	public $public_functions = array(
		'index'			=> true,
		'mainscreen'	=> true
	);

	/**
	 * @var object $nextmatchs the nextmatchs object
	 */
	private $nextmatchs;

	/**
	 * @var array $serverSettings the server settings
	 */
	private $serverSettings;

	/**
	 * @var array $flags the flags
	 */
	private $flags;

	/**
	 * @var array $userSettings the user settings
	 */
	private $userSettings;

	/**
	 * @var object $phpgwapi_common the phpgwapi_common object
	 */
	private $phpgwapi_common;


	/**
	 * Constucttor
	 */
	public function __construct()
	{
		$menuaction = Sanitizer::get_var('menuaction', 'location');
		$this->serverSettings = Settings::getInstance()->get('server');
		$this->userSettings = Settings::getInstance()->get('user');
		$this->flags = Settings::getInstance()->get('flags');

		$this->flags['xslt_app'] = false;
		$this->flags['menu_selection'] = 'admin';
		Settings::getInstance()->set('flags', $this->flags);
		$this->nextmatchs = CreateObject('phpgwapi.nextmatchs');
		$this->phpgwapi_common = new \phpgwapi_common();
	}

	/**
	 * Render the admin menu
	 *
	 * @return void
	 */
	function mainscreen()
	{
//		$this->flags['menu_selection'] .= '::admin::index';
//		Settings::getInstance()->set('flags', $this->flags);
		$menu		= createObject('phpgwapi.menu');
		$navbar		= $menu->get('navbar');
		$navigation = $menu->get('admin');

		$treemenu = '';
		foreach ($this->userSettings['apps'] as $app => $app_info)
		{
			if (!in_array($app, array('logout', 'about', 'preferences')) && isset($navbar[$app]))
			{
				$treemenu .= $menu->render_menu($app, isset($navigation[$app]) ? $navigation[$app] : null, $navbar[$app], true);
			}
		}
		$this->phpgwapi_common->phpgw_header(true);
		echo $treemenu;
	}

	/**
	 * Render the welcome screen editor
	 *
	 * @return void
	 */
	public function index()
	{
		if (Sanitizer::get_var('cancel', 'bool', 'POST'))
		{
			phpgw::redirect_link('/index.php', array('menuaction' => 'admin.uimainscreen.mainscreen'));
		}

		$this->flags['menu_selection'] .= '::admin::mainscreen';
		Settings::getInstance()->set('flags', $this->flags);



		$template = new Template();
		$template->set_root(PHPGW_APP_TPL);
		$template->set_file(array('message' => 'mainscreen_message.tpl'));
		$template->set_block('message', 'form', 'form');
		$template->set_block('message', 'row', 'row');
		$template->set_block('message', 'row_2', 'row_2');

		$this->phpgwapi_common->phpgw_header(true);
		$select_lang = Sanitizer::get_var('select_lang', 'string', 'POST');
		$section     = Sanitizer::get_var('section', 'string', 'POST');

		$db = \App\Database\Db::getInstance();

		if (Sanitizer::get_var('update', 'bool', 'POST'))
		{
			$message = Sanitizer::get_var('message', 'string', 'POST');

			// Prepare and execute the DELETE statement
			$deleteStmt = $db->prepare("DELETE FROM phpgw_lang WHERE message_id = :message_id AND app_name = :app_name AND lang = :lang");
			$deleteStmt->execute([
				'message_id' => $section . "_message",
				'app_name' => $section,
				'lang' => $select_lang
			]);

			// Prepare and execute the INSERT statement
			$insertStmt = $db->prepare("INSERT INTO phpgw_lang VALUES (:message_id, :app_name, :lang, :message)");
			$insertStmt->execute([
				'message_id' => $section . "_message",
				'app_name' => $section,
				'lang' => $select_lang,
				'message' => $message
			]);
			
			$message = '<center>' . lang('message has been updated') . '</center>';
		}

		$tr_class = '';
		if (empty($select_lang))
		{
			$template->set_var('header_lang', lang('Main screen message'));
			$template->set_var('form_action', phpgw::link('/index.php', array('menuaction' => 'admin.uimainscreen.index')));
			$template->set_var('tr_class', 'th');
			$template->set_var('value', '&nbsp;');
			$template->fp('rows', 'row_2', True);

			$tr_class = $this->nextmatchs->alternate_row_class($tr_class);
			$template->set_var('tr_class', $tr_class);

			$select_lang = '<select name="select_lang">';
			$db->query("SELECT lang,phpgw_languages.lang_name,phpgw_languages.lang_id FROM phpgw_lang,phpgw_languages WHERE "
				. "phpgw_lang.lang=phpgw_languages.lang_id GROUP BY lang,phpgw_languages.lang_name,"
				. "phpgw_languages.lang_id ORDER BY lang");
			while ($db->next_record())
			{
				$select_lang .= '<option value="' . $db->f('lang') . '">' . $db->f('lang_id')
					. ' - ' . $db->f('lang_name') . '</option>';
			}
			$select_lang .= '</select>';
			$template->set_var('label', lang('Language'));
			$template->set_var('value', $select_lang);
			$template->fp('rows', 'row', True);

			$tr_class = $this->nextmatchs->alternate_row_class($tr_class);
			$template->set_var('tr_class', $tr_class);
			$select_section = '<select name="section"><option value="mainscreen">' . lang('Main screen')
				. '</option><option value="loginscreen">' . lang("Login screen") . '</option>'
				. '</select>';
			$template->set_var('label', lang('Section'));
			$template->set_var('value', $select_section);
			$template->fp('rows', 'row', True);

			$tr_class = $this->nextmatchs->alternate_row_class($tr_class);
			$template->set_var('tr_class', $tr_class);
			$template->set_var('value', '<input type="submit" name="submit" value="' . lang('Submit')
				. '"><input type="submit" name="cancel" value="' . lang('cancel') . '">');
			$template->fp('rows', 'row_2', True);
		}
		else
		{

			$stmt = $db->prepare("SELECT content FROM phpgw_lang WHERE lang=:lang AND message_id=:message_id");
			$stmt->execute([
				'lang' => $select_lang,
				'message_id' => $section . "_message"
			]);

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			$current_message = $result['content'] ?? null;

			if ($section == 'mainscreen')
			{
				$template->set_var('header_lang', lang('Edit main screen message'));
			}
			else
			{
				$template->set_var('header_lang', lang('Edit login screen message'));
			}

			$template->set_var('form_action', phpgw::link('/index.php', array('menuaction' => 'admin.uimainscreen.index')));
			$template->set_var('select_lang', $select_lang);
			$template->set_var('section', $section);
			$template->set_var('tr_class', 'th');
			$template->set_var('value', '&nbsp;');
			$template->fp('rows', 'row_2', True);

			$tr_class = $this->nextmatchs->alternate_row_class($tr_class);
			$template->set_var('tr_class', $tr_class);
			$template->set_var('value', '<textarea name="message" cols="50" rows="10" wrap="virtual">' . stripslashes($current_message) . '</textarea>');
			$template->fp('rows', 'row_2', True);

			$tr_class = $this->nextmatchs->alternate_row_class($tr_class);
			$template->set_var('tr_class', $tr_class);
			$template->set_var(
				'value',
				'<input type="submit" name="update" value="' . lang('Update')
					. '"><input type="submit" name="cancel" value="' . lang('cancel') . '">'
			);
			$template->fp('rows', 'row_2', True);
		}

		$template->set_var('lang_cancel', lang('Cancel'));
		$template->set_var('error_message', $message);
		$template->pfp('out', 'form');
	}
}
