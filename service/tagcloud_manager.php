<?php
/**
*
* @package phpBB Extension - RH Topic Tags
* @copyright © 2014 Robert Heim; significant overhauling and new functions © 2025 S. McCandlish (under same license).
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace robertheim\topictags\service;

/**
 * @ignore
 */
use robertheim\topictags\tables;
use robertheim\topictags\prefixes;
use robertheim\topictags\service\db_helper;
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

	/** @var db_helper */
	private $db_helper;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\config\config $config,
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\controller\helper $helper,
		$table_prefix,
		tags_manager $tags_manager,
		db_helper $db_helper
	)
	{
		$this->db = $db;
		$this->config = $config;
		$this->template = $template;
		$this->user = $user;
		$this->helper = $helper;
		$this->table_prefix = $table_prefix;
		$this->tags_manager = $tags_manager;
		$this->db_helper	= $db_helper;
	}

	/**
	 * Assigns all required data for the tag cloud to the template so that
	 * including tagcloud.html can display the tag cloud.
	 * @param $maxtags	The maximum number of assigned tags to display. If 0
	 *  (default), the config limit is used; if $maxtags <= -1 all tags will
	 *  be shown; $maxtags otherwise.
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
		 //This script uses "Yoda order" in `if $0 == $maxtags` to avoid
		 // silently zeroing the value of the variable, in the event a later
		 // edit of this code accidentally used `=` in place of `==`.
		 // Instead of unintended variable assignment happening almost
		 // undetectably, an error would be thrown.
			$tags = array();
		} else {
			$tags = $this->get_top_tags($maxtags);
		}

		$max_count = $this->get_maximum_tag_usage_count($tags);

		$result_size = count($tags);
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
			$css_class = $this->get_css_class($tag['count'], $max_count);
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
	 * Gets the most-used tags (from topics the user actually has access to),
	 * up to $maxtags (if positive).
	 *
	 * @param $maxtags	maximum number of results, gets all tags if <1
	 * @return array	(array('tag' => string, 'count' => int), ...)
	 */
	public function get_top_tags($maxtags)
	{
		// Get the query for permissible topics for the current user
		$topic_query = $this->tags_manager->get_topics_build_query([], 'AND', false, true);
		// This is telling that function to return a list of all tagged topics
		// (`true`) that the user can access, case-insensitively (`false`),
		// rather than those tagged with a specific tag (`[]` empty array).
		// The `AND` must be passed just to fill that parameter slot, but has
		// no impact on functionality in this case.

		// Build the SQL array, appending the topic query directly:
		$sql_array = array(
			'SELECT'	=> 't.tag, t.tag_lowercase, COUNT(tt.tag_id) AS count',
			'FROM'		=> array(
				$this->table_prefix . tables::TAGS		=> 't',
				$this->table_prefix . tables::TOPICTAGS	=> 'tt',
				'(' . $topic_query . ')' => 'topics'  // Query as derived table
			),
			'WHERE'		=> 'tt.tag_id = t.id AND tt.topic_id = topics.topic_id',
			'GROUP_BY'  => 't.id, t.tag, t.tag_lowercase'
		);

		// Conditionally add a HAVING clause (if applicable) to filter by tag
		// associations with (accessible) topics, and only include tags with
		// at least one associated topic:
		if ($maxtags > 0) {
			$sql_array['HAVING'] = 'COUNT(tt.tag_id) > 0';
		}

		$sql_array['ORDER_BY'] = 'count DESC';

		$sql = $this->db->sql_build_query('SELECT', $sql_array);

		// Initialize array we use later:
		$tags = array();

		// Use the proper query method depending on $maxtags:
		if ($maxtags > 0) {
			$result = $this->db->sql_query_limit($sql, (int) $maxtags);
		} else {
			$result = $this->db->sql_query($sql);
		}

		// If query failed or is empty, return early with empty $tags array:
		if (!$result) {
			return $tags;
		}

		// Otherwise, fetch the tags and prepare them for display:
		while ($row = $this->db->sql_fetchrow($result)) {
			$tags[] = array(
				'tag'			=> $row['tag'],
				'tag_lowercase'	=> $row['tag_lowercase'],
				'count'			=> $row['count']
			);
		}

		// If no tags were found, return early with empty $tags array:
		if (empty($tags)) {
			return $tags;
		}

		// Otherwise, return the (user-accessible) tags, sorted using our
		// human-friendly algorithm:
		return $this->tags_manager->sort_tags($tags, false, true);
	}

	/**
	 * Get the usage count of the tag that is used the most (in topics
	 * accessible to the user). 
	 *
	 * @param array $tags	List of accessible tags with their count data
	 * @return int			Usage count of the most-used (accessible) tag
	 */
	private function get_maximum_tag_usage_count($tags)
	{
		// If no tags are available, there's no tag usage to count:
		if (empty($tags)) {
			return 0;
		}

		// Find the highest count among the tags
		$max_count = 0;
		foreach ($tags as $tag) {
			if ($tag['count'] > $max_count) {
				$max_count = $tag['count'];
			}
		}

		return $max_count;
	}

	/**
	 * Determines the size of the tag depending on its usage count
	 *
	 * @param $count		The count of uses of a tag
	 * @param $max_count	The usage count of the most-used tag
	 * @return string		The CSS class name
	 */
	private function get_css_class($count, $max_count)
	{
		$percent = 50;
		if (0 < $max_count) {
			$percent = floor(($count / $max_count) * 100);
		}

		switch (true) {
			case $percent < 10:
				return 'rh_topictags_smallest';
			case $percent < 20:
				return 'rh_topictags_smaller';
			case $percent < 40:
				return 'rh_topictags_small';
			case $percent < 60:
				return 'rh_topictags_medium';
			case $percent < 80:
				return 'rh_topictags_large';
			case $percent < 90:
				return 'rh_topictags_larger';
			default:
				return 'rh_topictags_largest';
		}
	}

	/**
	 * Returns only tags (in array) with a use count of 1 or greater
	 *
	 * @param $tags		array of tags (with at least column names 'tag' and 'count')
	 * @return string	array of tags, minus original array members without a positive count
	 */
	public function discard_zero_use_tags(array $tags)
	{
		// Filter out any tags with a count of 0 before returning the result
		return array_filter($tags, function($tag) {
			return $tag['count'] > 0;
		});
	} /* This is a utility function that might come in handy later, maybe
		 for testing or admin purposes, but is not presently employed.
		 We note that the ACP for this extension includes options to purge
		 various forms of invalid or unused tags that should have been
		 auto-removed, suggesting that undesirable tag-related states can
		 happen, so this function may have use in preventing display
		 of tags resulting from database-cleanup-related issues. */

}
