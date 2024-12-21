<?php
/**
*
* @package phpBB Extension - RH Topic Tags
* @copyright © 2014 Robert Heim; signficant overhauling and new functions © 2024 S. McCandlish (under same license).
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

/**
* Handles all functionallity regarding tags.
* This class is basically a manager (functions for cleaning and validating tags)
* and a DAO (storing tags to and retrieving them from the database).
*/
class tags_manager
{

	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var \phpbb\config\config */
	private $config;

	/** @var \phpbb\config\db_text */
	private $config_text;

	/** @var \phpbb\auth\auth */
	private $auth;

	/** @var db_helper */
	private $db_helper;

	/** @var string */
	private $table_prefix;

	public function __construct(
					\phpbb\db\driver\driver_interface $db,
					\phpbb\config\config $config,
					\phpbb\config\db_text $config_text,
					\phpbb\auth\auth $auth,
					db_helper $db_helper,
					$table_prefix
	)
	{
		$this->db			= $db;
		$this->config		= $config;
		$this->config_text	= $config_text;
		$this->auth			= $auth;
		$this->db_helper	= $db_helper;
		$this->table_prefix	= $table_prefix;
	}

	/**
	 * Remove all tags from the given (single) topic.
	 *
	 * @param $topic_id				topic ID
	 * @param $delete_unused_tags 	If set to true, unused tags are removed from the db.
	 */
	public function remove_all_tags_from_topic($topic_id, $delete_unused_tags = true)
	{
		$this->remove_all_tags_from_topics(array($topic_id), $delete_unused_tags);
	}

	/**
	 * Remove tag assignments from the given (multiple) topics.
	 *
	 * @param $topic_ids			array of topic IDs
	 * @param $delete_unused_tags	If set to true, unused tags are removed from the db.
	 */
	public function remove_all_tags_from_topics(array $topic_ids, $delete_unused_tags = true)
	{
		// remove tags from topic
		$sql = 'DELETE FROM ' . $this->table_prefix . tables::TOPICTAGS. '
			WHERE ' . $this->db->sql_in_set('topic_id', $topic_ids);
		$this->db->sql_query($sql);
		if ($delete_unused_tags) {
			$this->delete_unused_tags();
		}
		$this->calc_count_tags();
	}

	/**
	 * Gets the IDs of all tags that are not assigned to a topic.
	 */
	private function get_unused_tag_ids()
	{
		$sql = 'SELECT t.id
			FROM ' . $this->table_prefix . tables::TAGS . ' t
			WHERE NOT EXISTS (
				SELECT 1
				FROM ' . $this->table_prefix . tables::TOPICTAGS . ' tt
					WHERE tt.tag_id = t.id
			)';
		return $this->db_helper->get_ids($sql);
	}

	/**
	 * Removes all tags that are not assigned to at least one topic (garbage collection).
	 *
	 * @return integer	count of deleted tags
	 */
	public function delete_unused_tags()
	{
		$ids = $this->get_unused_tag_ids();
		if (empty($ids)) {
			// nothing to do
			return 0;
		}
		$sql = 'DELETE FROM ' . $this->table_prefix . tables::TAGS . '
			WHERE ' . $this->db->sql_in_set('id', $ids);
		$this->db->sql_query($sql);
		return $this->db->sql_affectedrows();
	}

	/**
	 * Deletes all assignments of tags that are no longer valid.
	 *
	 * @return integer	count of removed assignments
	 */
	public function delete_assignments_of_invalid_tags()
	{
		// get all tags to check them
		$tags = $this->get_existing_tags(null);

		$ids_of_invalid_tags = array();
		foreach ($tags as $tag) {
			if (!$this->is_valid_tag($tag['tag'])) {
				$ids_of_invalid_tags[] = (int) $tag['id'];
			}
		}
		if (empty($ids_of_invalid_tags)) {
			// nothing to do
			return 0;
		}

		// delete all tag-assignments where the tag is not valid
		$sql = 'DELETE FROM ' . $this->table_prefix . tables::TOPICTAGS . '
			WHERE ' . $this->db->sql_in_set('tag_id', $ids_of_invalid_tags);
		$this->db->sql_query($sql);
		$removed_count = $this->db->sql_affectedrows();

		$this->calc_count_tags();

		return $removed_count;
	}

	/**
	 * Identifies all topic tag-assignments where the topic does not exist anymore.
	 *
	 * @return array	array of "dead" tag-assignments
	 */
	private function get_assignment_ids_where_topic_does_not_exist()
	{
		$sql = 'SELECT tt.id
			FROM ' . $this->table_prefix . tables::TOPICTAGS . ' tt
			WHERE NOT EXISTS (
				SELECT 1
				FROM ' . TOPICS_TABLE . ' topics
					WHERE topics.topic_id = tt.topic_id
			)';
		return $this->db_helper->get_ids($sql);
	}

	/**
	 * Removes all topic tag-assignments where the topic does not exist anymore.
	 *
	 * @return integer	count of deleted assignments
	 */
	public function delete_assignments_where_topic_does_not_exist()
	{
		$ids = $this->get_assignment_ids_where_topic_does_not_exist();
		if (empty($ids)) {
			// nothing to do
			return 0;
		}
		// delete all tag-assignments where the topic does not exist anymore
		$sql = 'DELETE FROM ' . $this->table_prefix . tables::TOPICTAGS . '
			WHERE ' . $this->db->sql_in_set('id', $ids);
		$this->db->sql_query($sql);
		$removed_count = $this->db->sql_affectedrows();

		$this->calc_count_tags();

		return $removed_count;
	}

	/**
	 * Deletes all topic tag-assignments where the topic resides in a forum with tagging disabled.
	 *
	 * @param $forum_ids	array of forum-ids that should be checked (if null, all are checked)
	 * @return integer		count of deleted assignments
	 */
	public function delete_tags_from_tagdisabled_forums($forum_ids = null)
	{
		$forums_sql_where = '';

		if (is_array($forum_ids)) {
			if (empty($forum_ids)) {
				// Performance improvement, because we already know the result of querying the db.
				return 0;
			}
			$forums_sql_where = ' AND ' . $this->db->sql_in_set('f.forum_id', $forum_ids);
		}

		// Get IDs of all topic tag-assignments to topics that reside in a forum with tagging disabled.
		$sql = 'SELECT tt.id
			FROM ' . $this->table_prefix . tables::TOPICTAGS . ' tt
			WHERE EXISTS (
				SELECT 1
				FROM ' . TOPICS_TABLE . ' topics,
					' . FORUMS_TABLE . " f
				WHERE topics.topic_id = tt.topic_id
					AND f.forum_id = topics.forum_id
					AND f.rh_topictags_enabled = 0
					$forums_sql_where
			)";
		$delete_ids = $this->db_helper->get_ids($sql);

		if (empty($delete_ids)) {
			// nothing to do
			return 0;
		}
		// delete these assignments
		$sql = 'DELETE FROM ' . $this->table_prefix . tables::TOPICTAGS . '
			WHERE ' . $this->db->sql_in_set('id', $delete_ids);
		$this->db->sql_query($sql);
		$removed_count = $this->db->sql_affectedrows();

		$this->calc_count_tags();

		return $removed_count;
	}

	/**
	* Gets all tags assigned to a topic (and sorts them).
	*
	* @param $topic_id        a single topic ID
	* @param $casesensitive   whether to sort the tags case-sensitively
	* @return array           array of sorted tag names
	*/
	public function get_assigned_tags($topic_id, $casesensitive = false)
	{
		$topic_id = (int) $topic_id;

		// Fetch the tags, for this topic, from the database:
		$sql = 'SELECT t.tag, t.tag_lowercase
            FROM ' . $this->table_prefix . tables::TAGS . ' AS t,
                 ' . $this->table_prefix . tables::TOPICTAGS . " AS tt
            WHERE tt.topic_id = $topic_id
                AND t.id = tt.tag_id";
		$tagslist = $this->db_helper->get_array_by_fieldname($sql, ['tag', 'tag_lowercase']);

		// Run the array of tags through the sorter:
		$tagslistSorted = $this->sort_tags($tagslist, $casesensitive);

		// Flatten the array of sorted tags, to return only the tag names
		// (we have no use of the forced-lowercase versions after sorting,
		// and downstream uses, like urlencode(), expect strings from an array
		// not arrays from a meta-array):
		$tagNames = array_map(function($tag) {
			return $tag['tag']; // Extract only the 'tag' value
		}, $tagslistSorted);

		// Return the flattened array of tag names
		return $tagNames;
	}

	/**
	 * This runs the tag suggestions that pop up when you start entering a tag
	 * when editing tags in a post (e.g., you type "h", "e", "l", and if "help"
	 * already exists as a tag, it will be suggested as a match).
	 * Gets $count tags that start with $query, ordered by their usage count (desc).
	 * Note: that $query needs to be at least 3 characters long.
	 *
	 * @param $query	prefix of tags to search
	 * @param $exclude	array of tags that should be ignored
	 * @param $count	count of tags to return
	 * @return array	(array('text' => '...'), array('text' => '...'))
	 */
	public function get_tag_suggestions($query, $exclude, $count)
	{
		if (utf8_strlen($query) < 3) {
			return array();
		}
		$exclude_sql = '';
		if (!empty($exclude)) {
			$exclude_sql = ' AND ' . $this->db->sql_in_set('t.tag', $exclude, true, true);
		}
		$sql_array = array(
			// we must fetch count, because postgres needs the context for ordering
			'SELECT'	=> 't.tag, t.count',
			'FROM'		=> array(
				$this->table_prefix . tables::TAGS => 't',
			),
			'WHERE'		=> 't.tag ' . $this->db->sql_like_expression($query . $this->db->get_any_char()) . "
							$exclude_sql",
			'ORDER_BY'	=> 't.count DESC',
		);
		$sql = $this->db->sql_build_query('SELECT_DISTINCT', $sql_array);
		$result = $this->db->sql_query_limit($sql, $count);
		$tags = array();
		while ($row = $this->db->sql_fetchrow($result)) {
			$tags[] = array('text' => $row['tag']);
		}
		$this->db->sql_freeresult($result);
		return $tags;
	} // It's unclear why the min. is 3; changing it to 2 above has no effect.

	/**
	 * Assigns exactly the given valid tags to the topic (all other tags are removed from the topic and if a tag does not exist yet, it will be created).
	 *
	 * @param $topic_id
	 * @param $valid_tags	array containing valid tag-names
	 */
	public function assign_tags_to_topic($topic_id, $valid_tags)
	{
		$topic_id = (int) $topic_id;

		$this->remove_all_tags_from_topic($topic_id, false);
		$this->create_missing_tags($valid_tags);

		// get ids of tags
		$ids = $this->get_existing_tags($valid_tags, true);

		// create topic_id <-> tag_id link in TOPICTAGS_TABLE
		$sql_ary = array();
		foreach ($ids as $id) {
			$sql_ary[] = array(
				'topic_id'	=> $topic_id,
				'tag_id'	=> $id
			);
		}
		$this->db->sql_multi_insert($this->table_prefix . tables::TOPICTAGS, $sql_ary);

		// garbage collection
		$this->delete_unused_tags();

		$this->calc_count_tags();
	}

	/**
	 * Finds whether the given tags already exist and if not creates them in the db.
	 */
	private function create_missing_tags($tags)
	{
		// we will get all existing tags of $tags
		// and then subtract these from $tags
		// result contains the tags that needs to be created
		// to_create = $tags - existing

		// ensure that there isn't a tag twice in the array
		$tags = array_unique($tags);

		$existing_tags = $this->get_existing_tags($tags);

		// find all tags that are not in $existing_tags and add them to $sql_ary_new_tags
		$sql_ary_new_tags = array();
		foreach ($tags as $tag) {
			if (!$this->in_array_r($tag, $existing_tags)) {
				// tag needs to be created
				$sql_ary_new_tags[] = array(
					'tag'			=> $tag,
					'tag_lowercase'	=> utf8_strtolower($tag),
				);
			}
		}

		// create the new tags
		$this->db->sql_multi_insert($this->table_prefix . tables::TAGS, $sql_ary_new_tags);
	}

	/**
	 * Recursive in_array to check if the given (eventually multidimensional) array $haystack contains $needle.
	 */
	private function in_array_r($needle, $haystack, $strict = false)
	{
		foreach ($haystack as $item) {
			// Check if the current item matches the needle:
			if ($strict) {
				if ($item === $needle) {
					return true;
				}
			} else {
				if ($item == $needle) {
					return true;
				}
			}

			// If the item is an array, recursively search within it:
			if (is_array($item)) {
				if ($this->in_array_r($needle, $item, $strict)) {
					return true;
				}
			}
		}

		// If no match is found even after iterating through the array:
		return false;
	}

	/**
	 * Gets the existing tags, out of the tags given in $tags, or out of all
	 * existing tags if $tags == null. If $only_ids is set to true, an array
	 * containing only the IDs of the tags will be returned, instead of IDs +
	 * tag names: array(1,2,3,..)
	 *
	 * @param $tags			array of tag-names; might be null to get all existing tags
	 * @param $only_ids		whether to return only the tag IDs (true) or tag names as well (false, default)
	 * @return array		an array of the form array(array('id' => ... , 'tag' => ...), array('id' => ... , 'tag' => ...), ...); or array(1,2,3,...) if $only_ids == true
	 */
	public function get_existing_tags($tags = null, $only_ids = false)
	{
		$where = '';
		if (!is_null($tags)) {
			if (empty($tags)) {
				// Ensure that empty input array results in empty output array.
				// Note that this case is different from $tags == null where we want to get ALL existing tags.
				return array();
			}
			$where = 'WHERE ' . $this->db->sql_in_set('tag', $tags);
		}
		$sql = 'SELECT id, tag
			FROM ' . $this->table_prefix . tables::TAGS . "
			$where";
		if ($only_ids) {
			return $this->db_helper->get_ids($sql);
		}
		return $this->db_helper->get_multiarray_by_fieldnames($sql, array(
				'id',
				'tag'
			));
	} // This function, in its "all tags" mode, differs from get_all_tags() in
	  // providing only ID and "real" tagname (or IDs only, if that's chosen);
	  // the other function provides IDs, "real" tag names, lowercase versions
	  // of tag names, and count of each tag's uses; it also supports a number
	  // $limit, but not a constraint by specified tag names.

	/**
	 * Gets the topics which are tagged with any or all of the given $tags,
	 * from all forums in which tagging is enabled AND which the user is
	 * allowed to read (BUT exclusive of unapproved topics). These filtering
	 * determinations are handled by other functions called in a chain from
	 * this one.
	 *
	 * @param int $start		start for SQL query
	 * @param int $limit		limit for SQL query
	 * @param $tags				array of tags to find the topics for
	 * @param $mode				AND=all tags must be assigned, OR=at least one tag needs to be assigned
	 * @param $casesensitive	whether the search should be casesensitive (true) or not (false).
	 * @return array of topics	each containing all fields from TOPICS_TABLE
	 */
	public function get_topics_by_tags(array $tags, $start = 0, $limit, $mode = 'AND', $casesensitive = false)
	{
		$sql = $this->get_topics_build_query($tags, $mode, $casesensitive);
		$order_by = ' ORDER BY topics.topic_last_post_time DESC';
		$sql .= $order_by;
		return $this->db_helper->get_array($sql, $limit, $start);
	}

	/**
	 * Counts the topics which are tagged with any or all of the given $tags
	 * from all forums, where tagging is enabled and only those which the user
	 * is allowed to read.
	 *
	 * @param array $tags		the tags to find the topics for
	 * @param $mode				AND(default)=all tags must be assigned, OR=at least one tag needs to be assigned
	 * @param $casesensitive	search case-sensitive if true, insensitive otherwise (default).
	 * @return int				count of topics found
	 */
	public function count_topics_by_tags(array $tags, $mode = 'AND', $casesensitive = false)
	{
		if (empty($tags)) {
			return 0;
		}
		$sql = $this->get_topics_build_query($tags, $mode, $casesensitive);
		$sql = "SELECT COUNT(*) as total_results
			FROM ($sql) a";
		return (int) $this->db_helper->get_field($sql, 'total_results');
	}

	/**
	 * Generates a sql_in_set depending on $casesensitive using tag or tag_lowercase.
	 *
	 * @param array $tags				the tags to build the SQL for
	 * @param boolean $casesensitive	whether to leave the tags as-is (true) or make them lowercase (false)
	 * @return string					the sql_in string depending on $casesensitive using tag or tag_lowercase
	 */
	private function sql_in_casesensitive_tag(array $tags, $casesensitive)
	{
		$tags_copy = $tags;
		if (!$casesensitive) {
			$tag_count = sizeof($tags_copy);
			for ($i = 0; $i < $tag_count; $i++)
			{
				$tags_copy[$i] = utf8_strtolower($tags_copy[$i]);
			}
		}
		if ($casesensitive) {
			return $this->db->sql_in_set(' t.tag', $tags_copy);
		} else {
			return $this->db->sql_in_set('t.tag_lowercase', $tags_copy);
		}
	}

	/**
	 * Gets the forum IDs that the user is allowed to read.
	 *
	 * @return array	forum ids that the user is allowed to read
	 */
	private function get_readable_forums()
	{
		$forum_ary = array();
		$forum_read_ary = $this->auth->acl_getf('f_read');
		foreach ($forum_read_ary as $forum_id => $allowed) {
			if ($allowed['f_read']) {
				$forum_ary[] = (int) $forum_id;
			}
		}

		// Remove double entries
		$forum_ary = array_unique($forum_ary);
		return $forum_ary;
	}

	/**
	 * Get SQL-query source for the topics that reside in forums that the user
	 * can read and which are approved.
	 *
	 * @return string	the generated SQL
	 */
	private function sql_where_topic_access()
	{
		$forum_ary = $this->get_readable_forums();
		$sql_where_topic_access = '';
		if (empty($forum_ary)) {
			$sql_where_topic_access = ' 1=0 ';
		} else {
			$sql_where_topic_access = $this->db->sql_in_set('topics.forum_id', $forum_ary, false, true);
		}
		$sql_where_topic_access .= ' AND topics.topic_visibility = ' . ITEM_APPROVED;
		return $sql_where_topic_access;
	}

	/**
	 * Builds an SQL query that selects all topics assigned with the tags depending on $mode and $casesensitive
	 *
	 * @param $tags				array of tags
	 * @param $mode				AND or OR
	 * @param $casesensitive	false or true
	 * @return string			'SELECT topics.* FROM ' . TOPICS_TABLE . ' topics WHERE ' . [calculated where]
	 */
	public function get_topics_build_query(array $tags, $mode = 'AND', $casesensitive = false)
	{
		if (empty($tags)) {
			return 'SELECT topics.* FROM ' . TOPICS_TABLE . ' topics WHERE 0=1';
		}

		// Validate mode (force a valid value if something strange was given):
		if ($mode !== 'OR') {
			$mode = 'AND'; // default
		}

		$sql_where_tag_in = $this->sql_in_casesensitive_tag($tags, $casesensitive);
		$sql_where_topic_access = $this->sql_where_topic_access();
		$sql = '';
		if ('AND' == $mode) {
			$tag_count = sizeof($tags);
			// http://stackoverflow.com/questions/26038114/sql-select-distinct-where-exist-row-for-each-id-in-other-table
			$sql = 'SELECT topics.*
				FROM 	' . TOPICS_TABLE								. ' topics
					JOIN ' . $this->table_prefix . tables::TOPICTAGS	. ' tt ON tt.topic_id = topics.topic_id
					JOIN ' . $this->table_prefix . tables::TAGS			. ' t  ON tt.tag_id = t.id
					JOIN ' . FORUMS_TABLE								. " f  ON f.forum_id = topics.forum_id
				WHERE
					$sql_where_tag_in
					AND f.rh_topictags_enabled = 1
					AND $sql_where_topic_access
				GROUP BY topics.topic_id
				HAVING count(t.id) = $tag_count";
		} else {
			// OR mode, we produce: AND t.tag IN ('tag1', 'tag2', ...)
			$sql_array = array(
				'SELECT'	=> 'topics.*',
				'FROM'		=> array(
					TOPICS_TABLE							=> 'topics',
					$this->table_prefix . tables::TOPICTAGS	=> 'tt',
					$this->table_prefix . tables::TAGS		=> 't',
					FORUMS_TABLE							=> 'f',
				),
				'WHERE'		=> " $sql_where_tag_in
					AND topics.topic_id = tt.topic_id
					AND f.rh_topictags_enabled = 1
					AND f.forum_id = topics.forum_id
					AND $sql_where_topic_access
					AND t.id = tt.tag_id
				");
			$sql = $this->db->sql_build_query('SELECT_DISTINCT', $sql_array);
		}
		return $sql;
	}

	/**
	 * Checks whether the given tag is blacklisted.
	 *
	 * @param string $tag
	 * @return boolean		true, if the tag is on the blacklist, false otherwise
	 */
	private function is_on_blacklist($tag)
	{
		$blacklist = json_decode($this->config_text->get(prefixes::CONFIG.'_blacklist'), true);
		foreach ($blacklist as $entry) {
			if ($tag === $this->clean_tag($entry)) {
				return true;
			}
		}

	}

	/**
	 * Checks whether the given tag is whitelisted.
	 *
	 * @param string $tag
	 * @return boolean		true, if the tag is on the whitelist, false otherwise
	 */
	private function is_on_whitelist($tag)
	{
		$whitelist = $this->get_whitelist_tags();
		foreach ($whitelist as $entry) {
			if ($tag === $this->clean_tag($entry)) {
				return true;
			}
		}
	}

	/**
	 * Gets all tags from the whitelist
	 */
	public function get_whitelist_tags()
	{
		return json_decode($this->config_text->get(prefixes::CONFIG . '_whitelist'), true);
	}

	/**
	 * Checks if the given tag matches the configured regex for valid tags, Note that the tag is trimmed to 30 characters before the check!
	 * This method also checks if the tag is whitelisted and/or blacklisted if the lists are enabled.
	 *
	 * @param $tag			the tag to check
	 * @param $is_clean		whether the tag has already been cleaned or not.
	 * @return				true if the tag matches, false otherwise
	 */
	public function is_valid_tag($tag, $is_clean = false)
	{
		if (!$is_clean) {
			$tag = $this->clean_tag($tag);
		}

		$pattern = $this->config[prefixes::CONFIG.'_allowed_tags_regex'];
		$tag_is_valid = preg_match($pattern, $tag);

		if (!$tag_is_valid) {
			// non conform to regex is always invalid.
			return false;
		}

		// from here on: tag is regex conform

		// check blacklist
		if ($this->config[prefixes::CONFIG.'_blacklist_enabled']) {
			if ($this->is_on_blacklist($tag)) {
				// tag is regex-conform, but blacklisted => invalid
				return false;
			}
			// regex conform and not blacklisted => do nothing here
		}

		// here we know: tag is regex conform and not blacklisted or it's regex conform and the blacklist is disabled.

		// check whitelist
		if ($this->config[prefixes::CONFIG.'_whitelist_enabled']) {
			if ($this->is_on_whitelist($tag)) {
				// tag is regex-conform not blacklisted and in the whitelist => valid
				return true;
			}
			// not on whitelist, but whitelist enabled => invalid
			return false;
		}

		// tag is regex conform, not blacklisted and the the whitelist is disabled => valid
		return true;
	}

	/**
	 * Splits the given tags into valid and invalid ones.
	 *
	 * @param $tags		an array of potential tags
	 * @return array	array('valid' => array(), 'invalid' => array())
	 */
	public function split_valid_tags($tags)
	{
		$re = array(
			'valid'		=> array(),
			'invalid'	=> array()
		);
		foreach ($tags as $tag) {
			$tag = $this->clean_tag($tag);
			if ($this->is_valid_tag($tag, true)) {
				$type = 'valid';
			} else {
				$type = 'invalid';
			}
			$re[$type][] = $tag;
		}
		return $re;
	}

	/**
	 * Trims the tag to 30 characters and replaced spaces to "-" if configured.
	 *
	 * @param	the tag to clean
	 * @return	cleaned tag
	 */
	public function clean_tag($tag)
	{
		$tag = trim($tag);

		// db-field is max 30 characters!
		$tag = utf8_substr($tag, 0, 30);

		// might have a space at the end now, so trim again
		$tag = trim($tag);

		if ($this->config[prefixes::CONFIG.'_convert_space_to_minus']) {
			$tag = str_replace(' ', '-', $tag);
		}

		return $tag;
	}

	/**
	 * Checks if tagging is enabled in the given forum.
	 *
	 * @param $forum_id		the id of the forum
	 * @return				true if tagging is enabled in the given forum, false if not
	 */
	public function is_tagging_enabled_in_forum($forum_id)
	{
		$field = 'rh_topictags_enabled';
		$sql = "SELECT $field
			FROM " . FORUMS_TABLE . '
			WHERE ' . $this->db->sql_build_array('SELECT', array('forum_id' => (int) $forum_id));
		$status = (int) $this->db_helper->get_field($sql, $field);
		return $status > 0;
	}

	/**
	 * Enables tagging engine in all forums (not categories and links).
	 *
	 * @return	number of affected forums (should be the count of all forums (type FORUM_POST ))
	 */
	public function enable_tags_in_all_forums()
	{
		return $this->set_tags_enabled_in_all_forums(true);
	}

	/**
	 * en/disables tagging engine in all forums (not categories and links).
	 *
	 * @param boolean $enable	true to enable and false to disabl the engine
	 * @return					number of affected forums (should be the count of all forums (type FORUM_POST ))
	 */
	private function set_tags_enabled_in_all_forums($enable)
	{
		if ($enable) {
			$rh_topictags_enabled_value = 1;
		} else {
			$rh_topictags_enabled_value = 0;
		}
		
		$sql_ary = array(
			'rh_topictags_enabled' => $rh_topictags_enabled_value
		);
		
		if ($enable) {
			$rh_topictags_enabled_condition = '0';
		} else {
			$rh_topictags_enabled_condition = '1';
		}

		$sql = 'UPDATE ' . FORUMS_TABLE . '
			SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE forum_type = ' . FORUM_POST . '
				AND rh_topictags_enabled = ' . $rh_topictags_enabled_condition;
		
		$this->db->sql_query($sql);
		$affected_rows = $this->db->sql_affectedrows();
		$this->calc_count_tags();
		return (int) $affected_rows;
	}

	/**
	 * Disables tagging engine in all forums (not categories and links).
	 *
	 * @return	number of affected forums (should be the count of all forums (type FORUM_POST ))
	 */
	public function disable_tags_in_all_forums()
	{
		return $this->set_tags_enabled_in_all_forums(false);
	}

	/**
	 * Checks if all forums have the given status of the tagging engine (enabled/disabled)
	 *
	 * @param boolean $status	true to check for enabled, false to check for disabled engine
	 * @return boolean			true if for all forums tagging is in state $status
	 */
	private function is_status_in_all_forums($status)
	{
		if ($status) {
			$rh_topictags_enabled_value = '0';
		} else {
			$rh_topictags_enabled_value = '1';
		}

		$sql_array = array(
			'SELECT'	=> 'COUNT(*) as all_not_in_status',
			'FROM'		=> array(
				FORUMS_TABLE => 'f',
			),
			'WHERE'		=> 'f.rh_topictags_enabled = ' . $rh_topictags_enabled_value . '
				AND forum_type = ' . FORUM_POST,
		); // If any are disabled is_enabled_in_all_forums() will return false.
		
		$sql = $this->db->sql_build_query('SELECT', $sql_array);
		$all_not_in_status = (int) $this->db_helper->get_field($sql, 'all_not_in_status');
		return $all_not_in_status == 0;
	}

	/**
	 * Checks if tagging is enabled or for all forums (not categories and links).
	 *
	 * @return	true if for all forums tagging is enabled (type FORUM_POST ))
	 */
	public function is_enabled_in_all_forums()
	{
		return $this->is_status_in_all_forums(true);
	}

	/**
	 * Checks if tagging is disabled or for all forums (not categories and links).
	 *
	 * @return	true if for all forums tagging is disabled (type FORUM_POST ))
	 */
	public function is_disabled_in_all_forums()
	{
		return $this->is_status_in_all_forums(false);
	}

	/**
	 * Count how often each tag is used (skipping the usage in tagging-disabled forums) and store it for each tag.
	 */
	public function calc_count_tags()
	{
		$sql_array = array(
			'SELECT'	=> 'id',
			'FROM'		=> array(
				$this->table_prefix . tables::TAGS => 't',
			),
		);
		$sql = $this->db->sql_build_query('SELECT_DISTINCT', $sql_array);
		$tag_ids = $this->db->sql_query($sql);

		while ($tag = $this->db->sql_fetchrow($tag_ids)) {
			$tag_id = $tag['id'];
			$sql = 'SELECT COUNT(tt.id) as count
				FROM ' . TOPICS_TABLE . ' topics,
					' . FORUMS_TABLE . ' f,
					' . $this->table_prefix . tables::TOPICTAGS . ' tt
				WHERE tt.tag_id = ' . $tag_id . '
					AND topics.topic_id = tt.topic_id
					AND f.forum_id = topics.forum_id
					AND f.rh_topictags_enabled = 1';
			$this->db->sql_query($sql);
			$count = $this->db->sql_fetchfield('count');

			$sql = 'UPDATE ' . $this->table_prefix . tables::TAGS . '
				SET count = ' . $count . '
				WHERE id = ' . $tag_id;
			$this->db->sql_query($sql);
		}
	}

	/**
	 * Gets the topic-ids that the given tag-id is assigned to.
	 *
	 * @param int $tag_id	the id of the tag
	 * @return array		array of ints (the topic-ids)
	 */
	private function get_topic_ids_by_tag_id($tag_id)
	{
		$sql_array = array(
			'SELECT'	=> 'tt.topic_id',
			'FROM'		=> array(
				$this->table_prefix . tables::TOPICTAGS => 'tt',
			),
			'WHERE'		=> 'tt.tag_id = ' . ((int) $tag_id),
		);
		$sql = $this->db->sql_build_query('SELECT_DISTINCT', $sql_array);
		return $this->db_helper->get_ids($sql, 'topic_id');
	}

	/**
	 * Merges two tags, by assigning all topics of tag_to_delete_id to the tag_to_keep_id and then deletes the tag_to_delete_id.
	 * NOTE: Both tags must exist and this is not checked again!
	 *
	 * @param int $tag_to_delete_id		the id of the tag to delete
	 * @param string $tag_to_keep		must be valid
	 * @param int $tag_to_keep_id		the id of the tag to keep
	 * @return							the new count of assignments of the kept tag
	 */
	public function merge($tag_to_delete_id, $tag_to_keep, $tag_to_keep_id)
	{
		$tag_to_delete_id = (int) $tag_to_delete_id;
		$tag_to_keep_id = (int) $tag_to_keep_id;

		// delete assignments where the new tag is already assigned
		$topic_ids_already_assigned = $this->get_topic_ids_by_tag_id($tag_to_keep_id);
		if (!empty($topic_ids_already_assigned)) {
			$sql = 'DELETE FROM ' . $this->table_prefix . tables::TOPICTAGS. '
				WHERE ' . $this->db->sql_in_set('topic_id', $topic_ids_already_assigned) . '
					AND tag_id = ' . (int) $tag_to_delete_id;
			$this->db->sql_query($sql);
		}
		// renew assignments where the new tag is not assigned, yet
		$sql_ary = array(
			'tag_id' => $tag_to_keep_id,
		);
		$sql = 'UPDATE ' . $this->table_prefix . tables::TOPICTAGS . '
			SET  ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE tag_id = ' . (int) $tag_to_delete_id;
		$this->db->sql_query($sql);

		$this->delete_tag($tag_to_delete_id);
		$this->calc_count_tags();
		return $this->count_topics_by_tags(array($tag_to_keep), 'AND', true);
	}

	/**
	 * Deletes the given tag and all its assignments.
	 *
	 * @param int $tag_id
	 */
	public function delete_tag($tag_id)
	{
		$sql = 'DELETE FROM ' . $this->table_prefix . tables::TOPICTAGS . '
			WHERE tag_id = ' . ((int) $tag_id);
		$this->db->sql_query($sql);

		$sql = 'DELETE FROM ' . $this->table_prefix . tables::TAGS . '
			WHERE id = ' . ((int) $tag_id);
		$this->db->sql_query($sql);
	}

	/**
	 * Renames the tag
	 *
	 * @param int $tag_id				the id of the tag
	 * @param string $new_name_clean	the new name of the tag already cleaned
	 * @return int						the count of topics that are assigned to the tag
	 */
	public function rename($tag_id, $new_name_clean)
	{
		$sql_ary = array(
			'tag'			=> $new_name_clean,
			'tag_lowercase'	=> utf8_strtolower($new_name_clean),
		);
		$sql = 'UPDATE ' . $this->table_prefix . tables::TAGS . '
			SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE id = ' . ((int) $tag_id);
		$this->db->sql_query($sql);
		return $this->count_topics_by_tags(array($new_name_clean), 'AND', true);
	}

	/**
	 * Gets the corresponding tag by its id
	 *
	 * @param int $tag_id	the id of the tag
	 * @return string		the tag name
	 */
	public function get_tag_by_id($tag_id)
	{
		$sql_array = array(
			'SELECT'	=> 't.tag',
			'FROM'		=> array(
				$this->table_prefix . tables::TAGS => 't',
			),
			'WHERE'		=> 't.id = ' . ((int) $tag_id),
		);
		$sql = $this->db->sql_build_query('SELECT_DISTINCT', $sql_array);
		return $this->db_helper->get_field($sql, 'tag', 1);
	}

	/**
	 * Sorts an array of tags based on language-specific and case-sensitive/natural sorting.
	 * 
	 * @param array $tagslist		the array of tags to be sorted
	 * @param $asc					order direction; true (default) = ASC, false = DESC
	 * @param bool $casesensitive	whether to perform case-sensitive sorting
	 * @return array				array of re-sorted tags
	 */
	public function sort_tags($tagslist, $asc = true, $casesensitive = false)
	{
		/* Sorting is normal ascending order unless $asc is false.
		
		   By default, this extension defers to the current system
		   language_locale.charset alphabetization rules. Instead, you can set
		   something specific here, e.g. 'en_US.UTF-8', to conform sorting to a
		   particular language and country's norms. This might be needed in a
		   case of mismatch between server configuration and target audience,
		   like running a German board on a shared hosting provider in Sweden.
		   BE SURE that your system supports the localization you choose!
		   
		   We cannot use phpBB variables like $user_lang_name or S_USER_LANG
		   because those are simplifications like "en", not PHP-understood
		   localization names.
		*/
		// Save the current locale before changing it:
		$original_locale = setlocale(LC_COLLATE, 0);
		// 0 (WITHOUT quotation marks!) gets the current locale setting.

		setlocale(LC_COLLATE, '0');
		// Note the single quotation marks this time.
		// Or specify a locale in place of '0'; ensure your system supports it! 
		
		try {	// Keep sorting logic in a "sandbox" to protect setlocale ...

			// If someone has changed '0' above to something specific, we need to
			// check that the locale was actually set successfully since some of
			// the locale strings are complicated and someone might get one wrong.
			if (!$original_locale) {
				// Locale wasn't set successfully, so handle this case.
				// First log it:
				error_log("RH Topic Tags (service/tags_manager.php): Locale setting '$original_locale' failed. Falling back to default locale.");
				// Now set it back to system default:
				setlocale(LC_COLLATE, '0');
			}

			// Determine which field to use based on case-sensitivity:
			if ($casesensitive) {
				$tag_field = 'tag';  // "Official" tag names as saved in the db.
			} else {
				$tag_field = 'tag_lowercase';  // Db already has LC version, too.
			}

			// Perform both language-specific sorting (via strcoll) and
			// natural numeric sorting (via strnatcasecmp) in one pass:
			uasort($tagslist, function($a, $b) use ($tag_field) {
			// uasort not usort because the former ensures that the array keys
			// are preserved; they are object IDs with metadata implications,
			// not indicators of list ordering.

				// First, compare alphabetically with language-specific collation
				// (by setlocale localization above); store result in a variable:
				$collationResult = strcoll($a[$tag_field], $b[$tag_field]);

				// If alphabetically equal (strcoll returns 0), use human-friendly
				// "natural" sorting for numeric parts; replace values in variable:
				if ($collationResult === 0) {
					$collationResult = strnatcasecmp($a[$tag_field], $b[$tag_field]);
				} // Our script is "Yoda order" in `if $0 === $collationResult`
				  // to avoid silently zeroing the value of the variable, in
				  // the event a later edit of this code accidentally used `=`
				  // in place of `===`. Instead of uninteded assignment of the
				  // variable happening almost undetectably, an error would be
				  // thrown.

			return $collationResult;
			});

		} finally {
			// Always restore the original locale, even if an error occurred:
			setlocale(LC_COLLATE, $original_locale);
			// We do this because that's a global setting and various other things
			// may be making use of this in different ways. This must be done
			// before returning out of this function for any reason.
		}

		// If descending order was requested, reverse the sorted array:
		if (!$asc) {
			$tagslist = array_reverse($tagslist, true);
			// The second argument, `true`, ensures that the array keys are
			// preserved, because they are object IDs with metadata
			// implications, not indicators of list ordering.
		}

		return $tagslist;

		/* Closing notes: 
		
		   strcoll() is used because it is specifically designed to perform
		   a string comparison compliant with the current locale, respecting
		   language/regional rules for alphabetizing strings, including
		   handling special characters (like diacritics), case differences,
		   and other variations in string order. E.g. the alphabetization
		   of ö is different between German and Swedish.

		   In cases where setlocale() fails or cannot be set to a desired
		   locale, we could potentially fall back to strnatcasecmp() in place
		   of strcoll(), though this would not have the nuances of the latter's
		   language-specific collation. This would be some work, so will not
		   be implemented without clear demand for it.
		   
		   Also, it is worth noting here that the sorting is being performed on
		   the tags alone (in their original mixed-case form or their lower-
		   case normalized form), and the relationship of tag name to ID
		   number and other elements in the array will remain intact as the
		   array is reordered.

		   Finally, an attempt was made to use PHP Collation to perform the
		   sorting, which is superior even to strcoll(). But this just produced
		   Server 500 errors, so phpBB or the underlying PHP installation is
		   not working with this. Even if it's a local-config problem, we can't
		   depend on this newer approach being available. It might be possible
		   to test for it and use it if usable but fall back to strcoll() if
		   not, but so far any attempt to use it at all causes 500 error. It's
		   also not practicable to have the database itself do the collating
		   via the SQL query that fetches the tags, since the collation names
		   between MySQL, PostreSQL, etc., are not in agreement.
		*/
	}

	/**
	 * Gets ALL tags, unfiltered, from the database; sorts them in an array.
	 *
	 * @param int $start			start for SQL query
	 * @param int $limit			limit for SQL query
	 * @param $sort_field			the db column to order by; tag (default) or count
	 * @param $asc					order direction; true (default) = ASC, false = DESC
	 * @param bool $casesensitive	whether to perform case-sensitive fetching
	 * @param bool $humsort			whether to do human-friendly enhanced sorting
	 * @return array				array of tags
	 */
	public function get_all_tags($start = 0, $limit, $sort_field = 'tag', $asc = true, $casesensitive = false, $humsort = true)
	{
		// Fetch by tag name (default) or by count of uses?
		switch ($sort_field) {
			case 'count':
				$sort_field = 'count';
				break;
			case 'tag':
				// no break
			default:
				$sort_field = 'tag';
		} // If a page is asking for `count`, it's probably to list tags by use
		  // frequency, so `$asc = false` is probably also desired for that.

		// Set the fetch direction (ASC or DESC):
		if ($asc) {
			$direction = 'ASC';	// Ascending db fetch order
		} else {
			$direction = 'DESC'; // Descending db fetch order
		}

		// Define SQL query to fetch tags from the database:
		$sql = 'SELECT * FROM ' . $this->table_prefix . tables::TAGS . '
			ORDER BY ' . $sort_field . ' ' . $direction;

		// Define the field names to fetch from the database:
		$field_names = array(
			'id',
			'tag',
			'tag_lowercase',
			'count'
		);

		// Fetch the tags from the database:
		$tagslist = $this->db_helper->get_multiarray_by_fieldnames($sql, $field_names, $limit, $start);

		if ($sort_field == 'count') {
			// Mustn't be run through the sorter (even if sorting mistakenly
			// requested); it's a numeric count, not any kind of label: 
			$humsort = false;
		}
		if ($humsort) {
			// Run the array of tags through the sorter for locale-aware alphabetic
			// and human-friendly numeric sorting of tag names:
			$tagslistSorted = $this->sort_tags($tagslist, $asc, $casesensitive);
			// Return the re-sorted tags list:
			return $tagslistSorted;
		} else {
			// Just return the raw db version of the tags list, whether ASC or
			// DESC, if human-friendly sorting has been turned off.
			return $tagslist;
		}
	} // This function, in "all tags" mode, differs from get_existing_tags()
	  // in providing IDs, "real" tag names, lowercase versions of tag names,
	  // and count of each tag's uses; it also supports a numeric limit, and
	  // has some sorting options. The other function returns only ID and
	  // "real" tag name (or ID only, as an option), can limit by specified tag
	  // names but not a number, and does no re-sorting.

	/**
	 * Gets the count of ALL tags, unfiltered.
	 *
	 * @return int	the count of all tags
	 */
	public function count_tags()
	{
		$sql = 'SELECT COUNT(*) as count_tags FROM ' . $this->table_prefix . tables::TAGS;
		return (int) $this->db_helper->get_field($sql, 'count_tags');
	}
}
