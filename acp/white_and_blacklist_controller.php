<?php
/**
 *
 * @package phpBB Extension - RH Topic Tags
 * @copyright © 2014 Robert Heim
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */
namespace robertheim\topictags\acp;

/**
* @ignore
*/
use robertheim\topictags\prefixes;

/**
 * Handles the "Whitelist" and "Blacklist" pages of the ACP.
 */
class white_and_blacklist_controller
{
	/** @var \phpbb\config\config */
	private $config;

	/**
	 * @var \phpbb\config\db_text
	 */
	private $config_text;

	/** @var \phpbb\request\request */
	private $request;

	/** @var \phpbb\user */
	private $user;

	/** @var \phpbb\template\template */
	private $template;

	/** @var \robertheim\topictags\service\tags_manager */
	private $tags_manager;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\config\db_text $config_text,
		\phpbb\request\request $request,
		\phpbb\user $user,
		\phpbb\template\template $template,
		\robertheim\topictags\service\tags_manager $tags_manager)
	{
		$this->config = $config;
		$this->config_text = $config_text;
		$this->request = $request;
		$this->user = $user;
		$this->template = $template;
		$this->tags_manager = $tags_manager;
	}

	/**
	 *
	 * @param string $u_action	phpBB ACP user action
	 */
	public function manage_whitelist($u_action)
	{
		$this->manage_list($u_action, 'whitelist');
	}

	/**
	 *
	 * @param string $u_action	phpBB ACP user action
	 */
	public function manage_blacklist($u_action)
	{
		$this->manage_list($u_action, 'blacklist');
	}

	/**
	 * @param string $list_name Whitelist or Blacklist
	 * @param string $u_action	phpBB ACP user action
	 */
	private function manage_list($u_action, $list_name)
	{
		$list_name_upper = strtoupper($list_name);
		// Define the name of the form for use as a form key:
		$form_name = 'topictags';
		add_form_key($form_name);

		$errors = array();

		if ($this->request->is_set_post('submit'))
		{
			if (! check_form_key($form_name))
			{
				trigger_error('FORM_INVALID');
			}

			$this->config->set(prefixes::CONFIG . '_' . $list_name . '_enabled', $this->request->variable(prefixes::CONFIG . '_' . $list_name . '_enabled', 0));
			$list = rawurldecode(base64_decode($this->request->variable(prefixes::CONFIG . '_' . $list_name, '')));
			if (! empty($list))
			{
				$list = json_decode($list, true);
				$tags = array();
				for ($i = 0, $size = sizeof($list); $i < $size; $i ++)
				{
					$tags[] = $list[$i]['text'];
				}
				$list = json_encode($tags);
			}
			// Store the list:
			$this->config_text->set(prefixes::CONFIG . '_' . $list_name, $list);
			trigger_error($this->user->lang('TOPICTAGS_' . $list_name_upper . '_SAVED') . adm_back_link($u_action));
		}

		// Display:
		$list = $this->config_text->get(prefixes::CONFIG . '_' . $list_name);
		$list = base64_encode(rawurlencode($list));
		$this->template->assign_vars(
			array(
				'TOPICTAGS_VERSION'								=> $this->user->lang('TOPICTAGS_INSTALLED', $this->config[prefixes::CONFIG . '_version']),
				'TOPICTAGS_' . $list_name_upper . '_ENABLED'	=> $this->config[prefixes::CONFIG . '_' . $list_name . '_enabled'],
				'TOPICTAGS_' . $list_name_upper					=> $list,
				'S_RH_TOPICTAGS_INCLUDE_NG_TAGS_INPUT'			=> true,
				'S_RH_TOPICTAGS_INCLUDE_CSS_FROM_ACP'			=> true,
				'TOPICTAGS_CONVERT_SPACE_TO_HYPHEN'				=> $this->config[prefixes::CONFIG . '_convert_space_to_hyphen'] ? 'true' : 'false',
				'S_ERROR'										=> (sizeof($errors)) ? true : false,
				'ERROR_MSG'										=> implode('<br />', $errors),
				'U_ACTION'										=> $u_action
			));
	}
}
