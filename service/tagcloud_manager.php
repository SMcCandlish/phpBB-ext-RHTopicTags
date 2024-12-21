<?php
/**
*
* @package phpBB Extension - RH Topic Tags
* @copyright © 2014 Robert Heim
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace robertheim\topictags\service;

/**
 * @ignore
 */
use robertheim\topictags\tables;
use robertheim\topictags\prefixes;
use robertheim\topictags\service\tags_manager;

/**
* Handles all operations regarding the tag cloud.
*/
class tagcloud_manager
{
	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var \phpbb\config\config */
	private $config;

	/** @var \phpbb\template\template */
	private $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\controller\helper */
	private $helper;

	/** @var string */
	private $table_prefix;

	/** @var tags_manager */
	private $tags_manager;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\controller\helper $helper,
		$table_prefix,
		tags_manager $tags_manager
	)
	{
		$this->db = $db;
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->table_prefix = $table_prefix;
		$this->tags_manager = $tags_manager;
	}

	/**
	 * Assigns all required data for the tag cloud to the template so that
	 * including tagcloud.html can display the tag cloud.
	 * @param $maxtags	The maximum number of assigned tags to display. If 0 (default), the config limit is used; if $maxtags <= -1 all tags will be shown; $maxtags otherwise.
	 */
	public function assign_tagcloud_to_template($maxtags = 0)
	{
		// Default value handling
		if (0 == $maxtags) {
			$maxtags = $this->config[prefixes::CONFIG . '_max_tags_in_tagcloud'];
		}

		// Get the data via get_top_tags(), which abstracts fetching/processing
		// When $maxtags is still 0, no tags should be displayed.
		if (0 == $maxtags) {
			$tags = array();
		} else {
			$tags = $this->get_top_tags($maxtags);
		} //This script uses "Yoda order" in `if $0 == $maxtags` to avoid
		  // silently zeroing the value of the variable, in the event a later
		  // edit of this code accidentally used `=` in place of `==`.
		  // Instead of that happening almost undetectably, an error would
		  // be thrown. */

		$maximum = $this->get_maximum_tag_usage_count();

		$result_size = sizeof($tags);
		if ($result_size < $maxtags) {
			$maxtags = $result_size;
		}

		if ($maxtags <= -1) {
			$show_count = $this->user->lang('RH_TOPICTAGS_DISPLAYING_TOTAL_ALL');
		} else {
			$show_count = $this->user->lang('RH_TOPICTAGS_DISPLAYING_TOTAL', $maxtags);
		}

		// Ensure that the CSS for the tag cloud will be included:
		$this->template->assign_vars(array(
			'S_RH_TOPICTAGS_INCLUDE_CSS'		=> true,
			'RH_TOPICTAGS_TAGCLOUD_SHOW_COUNT'	=> $this->config[prefixes::CONFIG . '_display_tagcount_in_tagcloud'],
			'RH_TOPICTAGS_TAGCLOUD_TAG_COUNT'	=> $show_count,
		));

		// Display the tag cloud:
		foreach ($tags as $tag) {
			$css_class = $this->get_css_class($tag['count'], $maximum);
			$link = $this->helper->route('robertheim_topictags_show_tag_controller', array(
				'tags'	=> urlencode($tag['tag'])
			));

			$this->template->assign_block_vars('rh_topictags_tags', array(
				'NAME'		=> $tag['tag'],
				'LINK'		=> $link,
				'CSS_CLASS'	=> $css_class,
				'COUNT'		=> $tag['count'],
			));
		}
	}

	/**
	 * Gets the most-used tags, up to $maxtags (if positive).
	 *
	 * @param $maxtags	maximum number of results, gets all tags if <1
	 * @return array	(array('tag' => string, 'count' => int), ...)
	 */
	public function get_top_tags($maxtags)
	{
		$where = '';
		if ($maxtags > 0) {
			$where = 't.count > 0';
		}
		$sql_array = array(
			'SELECT'	=> 't.tag, t.count',
			'FROM'		=> array(
				$this->table_prefix . tables::TAGS  => 't'
			),
			'WHERE'		=> $where,
			'ORDER_BY'	=> 't.count DESC',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		if ($maxtags > 0) {
			$result = $this->db->sql_query_limit($sql, (int) $maxtags);
		} else {
			$result = $this->db->sql_query($sql);
		}
		$tags = array();
		while ($row = $this->db->sql_fetchrow($result)) {
			$tags[] = array(
				'tag'	=> $row['tag'],
				'count'	=> $row['count']
			);
		}
		return $tags;
	}

	/**
	 * Get the usage count of the tag that is used the most
	 *
	 * @return int	maximum
	 */
	private function get_maximum_tag_usage_count()
	{
		$sql_array = array(
			'SELECT'	=> 't.count',
			'FROM'		=> array(
				$this->table_prefix . tables::TAGS  => 't'
			),
			'WHERE'		=> 't.count > 0',
			'ORDER_BY'	=> 't.count DESC',
		);
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$result = $this->db->sql_query_limit($sql, 1);
		$re = (int) $this->db->sql_fetchfield('count');
		$this->db->sql_freeresult($result);
		return $re;
	}

	/**
	 * Determines the size of the tag depending on its usage count
	 *
	 * @param $count	the count of uses of a tag
	 * @param $maximum	the usage count of the most-used tag
	 * @return string	the CSS class name
	 */
	private function get_css_class($count, $maximum)
	{
		$percent = 50;
		if (0 < $maximum) {
			$percent = floor(($count / $maximum) * 100);
		}

		switch (true) {
			case $percent < 20:
				return 'rh_topictags_smallest';
			case $percent < 40:
				return 'rh_topictags_small';
			case $percent < 60:
				return 'rh_topictags_medium';
			case $percent < 80:
				return 'rh_topictags_large';
			default:
				return 'rh_topictags_largest';
		}
	}

}
