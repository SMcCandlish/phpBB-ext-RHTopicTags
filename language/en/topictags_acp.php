<?php
/**
*
* @package phpBB Extension - RH Topic Tags
* @copyright © 2014 Robert Heim; heavily revised 2024 by SMcCandlish
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// These will be re-used in the definitions below; that is why we need to define and merge them first.
$lang = array_merge($lang, array(

	// Dumbed-down English version (no diacritics, underscores, or dots; has
	// a min. of 2 chars., for common acronyms like "AI", "UK", etc.):
	// 'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[a-z0-9\- ]{2,30}$/i',
	// 'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'Latin-alphabet letters, 0–9, hyphen, space (will convert to -); min.: 2, max.: 30',
	//
	// Default: Western European version (permits characters with diacritics;
	// has a min. of 2, for common acronyms like "AI", "UK", etc.):
	'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[\p{Script=Latin}0-9\-_\. ]{2,30}$/u',
	'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'Latin-alphabet letters, 0–9, hyphen, underscore, dot, space (will convert to -); min.: 2, max.: 30',
	//
	// Western Eurasia version (adds Greek, Cyrillic, and Hebrew support; has
	// a min. of 2, for common acronyms like "AI", "UK", etc.):
	// 'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[\p{Script=Latin}\p{Script=Greek}\p{Script=Cyrillic}\p{Script=Hebrew}0-9\-_\. ]{2,30}$/u',
	// 'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'letters (Latin, Greek, Cyrillic, Hebrew), 0–9, hyphen, underscore, dot, space (will convert to -); min.: 2, max.: 30',
	//
	// Fully internationalized version (has a min. of 1, because in many cases
	// a CJK ideogram for an entire phrase is a single character):
	// 'ACP_RH_TOPICTAGS_REGEX_DEFAULT'				=> '/^[\p{L}0-9\-_\. ]{1,30}$/u',
	// 'ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'	=> 'letters in any language, 0–9, hyphen, underscore, dot, space (will convert to -); min.: 1, max.: 30',
	//
	// As you can see from these, if you need to enable support for something
	// special, like linguistic or mathematical characters, you just need to
	// add specific Unicode blocks or codes that represent ranges of such
	// blocks.
	//
	// If you need additional particular characters like @ and $, just add
	// them individually, but do read up on how to write regular expressions:
	// https://stackoverflow.com/questions/399078/what-special-characters-must-be-escaped-in-regular-expressions
	// Various characters have to be \-escaped (and this is a PCRE regex, so
	// uses the PCRE escaping rules). A common case would be supporting #, for
	// hashtags. This character does not need to be escaped, just added to the
	// end of the [...] selection, e.g. '/^[\p{L}0-9\-_\. #]{1,30}$/u'
	//
	// You can also change any of these to have a min. of 1 char., as {1,30},
	// if you need to account for things like the programming language named R.
	//
	// If you change the maximum to no longer be 30, then make the same change
	// in adm/style/topictags_manage_tags.html
));

$lang = array_merge($lang, array(
	// forum settings page
	'ACP_RH_TOPICTAGS_ENABLE'								=> 'Enable RH Topic Tags',
	'ACP_RH_TOPICTAGS_ENABLE_EXP'							=> 'Whether or not to enable tagging of topics in this forum. (When disabling tagging, the tags are <em>not removed</em> from the topics in this forum – so when you enable it again, they are still there. If you really want to delete the tags, then use the “Delete tags from this forum” option.)',
	'ACP_FORUM_SETTINGS_RH_TOPICTAGS_PRUNE'					=> 'Delete tags from this forum',
	'ACP_FORUM_SETTINGS_RH_TOPICTAGS_PRUNE_EXP'				=> 'This will <strong>delete</strong> all assignments of tags to the topics of this forum. <strong>Note</strong>: To prevent accidental deletion of tags, you need to disable tagging for this forum.',
	'ACP_FORUM_SETTINGS_RH_TOPICTAGS_PRUNE_CONFIRM'			=> 'This option will <strong>delete</strong> all assignments of tags to topics in this forum. You need to disable tagging for this forum first.',
	'ACP_RH_TOPICTAGS_PRUNING_REQUIRES_TAGGING_DISABLED'	=> 'To prevent accidental deletion of tags, you need to disable tagging for this forum before deleting its tag assignments.',
	'ACP_RH_TOPICTAGS_ERROR'								=> 'Error',
	'ACP_RH_TOPICTAGS_UNKNOWN_ERROR'						=> 'Unknown error. See JavaScript console for server response.',

	// config
	'TOPICTAGS_INSTALLED'					=> 'Installed Version: v%s',

	'ACP_RH_TOPICTAGS_REGEX_EMPTY'			=> 'The regular expression cannot be left empty.',
	'ACP_RH_TOPICTAGS_EXP_FOR_USERS_EMPTY'	=> 'The explanation of which characters are allowed cannot be left empty.',

	'TOPICTAGS_CONFIG'					=> 'Configuration',
	'TOPICTAGS_CONFIG_TAGCLOUD'			=> 'Tag cloud settings',
	'TOPICTAGS_CONFIG_TAGS'				=> 'Tag settings',
	'TOPICTAGS_MAINTENANCE'				=> 'Maintenance',
	'TOPICTAGS_TITLE'					=> 'RH Topic Tags',
	'TOPICTAGS_SETTINGS_SAVED'			=> 'Configuration updated successfully.',
	'TOPICTAGS_WHITELIST_SAVED'			=> 'Whitelist updated successfully.',
	'TOPICTAGS_BLACKLIST_SAVED'			=> 'Blacklist updated successfully.',

	'TOPICTAGS_DISPLAY_TAGCLOUD_ON_INDEX'		=> 'Display tag cloud on index',
	'TOPICTAGS_DISPLAY_TAGCLOUD_ON_INDEX_EXP'	=> 'When enabled, a tag cloud is displayed on the bottom of the index page',

	'TOPICTAGS_DISPLAY_TAGCOUNT_IN_TAGCLOUD'		=> 'Display usage count of tags in tag cloud',
	'TOPICTAGS_DISPLAY_TAGCOUNT_IN_TAGCLOUD_EXP'	=> 'When enabled, the tag cloud displays for each tag how many topics are tagged with it.',

	'TOPICTAGS_MAX_TAGS_IN_TAGCLOUD'			=> 'Max tags in tag cloud',
	'TOPICTAGS_MAX_TAGS_IN_TAGCLOUD_EXP'		=> 'This limits, to the configured value, the number of tags shown in the tag cloud on the index page.<br />It has no effect on the separate tag cloud page, which shows all tags accessible to the user.',

	'TOPICTAGS_DISPLAY_TAGS_IN_VIEWFORUM'		=> 'Display tags in viewforum',
	'TOPICTAGS_DISPLAY_TAGS_IN_VIEWFORUM_EXP'	=> 'If set to yes, the assigned tags for each topic are shown in topic-lists.',

	'TOPICTAGS_ENABLE_IN_ALL_FORUMS_ALREADY'	=> 'Tagging is already enabled for all forums.',
	'TOPICTAGS_ENABLE_IN_ALL_FORUMS'			=> 'Enable RH Topic Tags in all forums',
	'TOPICTAGS_ENABLE_IN_ALL_FORUMS_EXP'		=> 'This will enable tagging in <em>all</em> forums. You can enable (or disable) it in a single forum in the settings of that forum.',
	'TOPICTAGS_ENABLE_IN_ALL_FORUMS_DONE'	=> array(
			1 => 'Tagging has been enabled for %d forum.',
			2 => 'Tagging has been enabled for %d forums.',
	),

	'TOPICTAGS_DISABLE_IN_ALL_FORUMS_ALREADY'	=> 'Tagging is already disabled for all forums.',
	'TOPICTAGS_DISABLE_IN_ALL_FORUMS'			=> 'Disable RH Topic Tags in all forums',
	'TOPICTAGS_DISABLE_IN_ALL_FORUMS_EXP'		=> 'This will disable tagging in <em>all</em> forums. You can disable (or enable) it in a single forum in the settings of that forum.',
	'TOPICTAGS_DISABLE_IN_ALL_FORUMS_DONE'	=> array(
			1 => 'Tagging has been disabled for %d forum.',
			2 => 'Tagging has been disabled for %d forums.',
	),

	'TOPICTAGS_PRUNE'					=> 'Prune tags',
	'TOPICTAGS_PRUNE_EXP'				=> 'This will delete all tags that are not used by any topic',
	'TOPICTAGS_PRUNE_CONFIRM'			=> 'This will <strong>delete</strong> all unused tags.',
	'TOPICTAGS_PRUNE_ASSIGNMENTS_DONE'	=> array(
			0 => '',
			1 => '%d topic-tag-assignment has been deleted.',
			2 => '%d topic-tag-assignments have been deleted.',
	),
	'TOPICTAGS_PRUNE_TAGS_DONE'			=> array(
			0 => 'There are no unused tags which we could delete.',
			1 => '%d unused tag has been deleted.',
			2 => '%d unused tags have been deleted.',
	),

	'TOPICTAGS_PRUNE_FORUMS'			=> 'Prune tags from forums with tagging disabled',
	'TOPICTAGS_PRUNE_FORUMS_EXP'		=> 'This will delete all assignments of tags to those topics that reside in a forum with tagging disabled.',
	'TOPICTAGS_PRUNE_FORUMS_CONFIRM'	=> 'This will <strong>delete</strong> all tags from all those threads which reside in a forum with tagging disabled.',

	'TOPICTAGS_PRUNE_INVALID_TAGS'				=> 'Prune invalid tags',
	'TOPICTAGS_PRUNE_INVALID_TAGS_EXP'			=> 'This will <strong>delete</strong> all tags (and their assignments to topics) that are no longer valid.<br />This is only required if you changed the regex and want to get rid of the invalid tags.',
	'TOPICTAGS_PRUNE_INVALID_TAGS_CONFIRM'		=> 'This will <strong>delete</strong> all tags that do not conform to the configured regex, and can delete a lot of your stuff if you are not careful!',

	'TOPICTAGS_CALC_COUNT_TAGS'					=> 'Recalculate tag counts',
	'TOPICTAGS_CALC_COUNT_TAGS_EXP'				=> 'This will recalculate how often each tag has been used.',
	'TOPICTAGS_CALC_COUNT_TAGS_DONE'			=> 'Tag ounts have been recalculated.',

	'TOPICTAGS_ENABLE_WHITELIST'				=> 'Enable tag whitelist',
	'TOPICTAGS_ENABLE_WHITELIST_EXP'			=> 'If enabled, only tags that are conform to the regex <em>and</em> are present in the whitelist below are allowed.<br /><strong>Npte 1</strong>: If the blacklist is enabled, too, and a tag is in the whitelist as well as in the blacklist, it will be rejected.<br /><strong>Note 2</strong>: To prevent accidental data loss, tags that are already in the database but are not on the whitelist are <em>not</em> deleted automatically and will be displayed as well. You must remove the existing tags by hand.',

	'TOPICTAGS_WHITELIST'						=> 'Whitelist',
	'TOPICTAGS_WHITELIST_EXP'					=> 'List of allowed tags.<br /><strong>Note</strong>: Tags must conform to the regex as well, so be sure that all these tags comply with your regex settings here (they are not checked automatically).',

	'TOPICTAGS_ENABLE_BLACKLIST'				=> 'Enable tag blacklist',
	'TOPICTAGS_ENABLE_BLACKLIST_EXP'			=> 'If enabled, the tags configured in the blacklist will be rejected even if they conform to the regex.<br /><strong>Note 1</strong>: To prevent accidental data loss, tags that are already in the database are not deleted automatically. You must remove them by hand from each topic.<br /><strong>Note 2</strong>: The blacklist is never shown to the users.',

	'TOPICTAGS_BLACKLIST'						=> 'Blacklist',
	'TOPICTAGS_BLACKLIST_EXP'					=> 'List of forbidden tags.<br /><strong>Note</strong>: All tags that do not conform to the regex are always rejected.',

	'TOPICTAGS_ALLOWED_TAGS_REGEX'				=> 'Regular Expression for allowed tags',
	'TOPICTAGS_ALLOWED_TAGS_REGEX_EXP'			=> '<strong>Warning</strong>: Do not change this if you don’t know what you’re doing.<br /><em>Tags can be 30 characters at maximum, and delimiters between them used by the extension for various purposes are “/” and “,”<br />so those characters must not be added to the regex (individually or as part of a class) as permissible in tags.</em><br />After a regex change, now-invalid tags are not searchable, but are still displayed in the topics. To prune invalid tags, see "Maintenance".<br />Default regex: <code>' . $lang['ACP_RH_TOPICTAGS_REGEX_DEFAULT'] . '</code>',

	'TOPICTAGS_CONVERT_SPACE_TO_HYPHEN'			=> 'Convert “ ” to “-”',
	'TOPICTAGS_CONVERT_SPACE_TO_HYPHEN_EXP'		=> 'If set to yes, all spaces (“ ”) are automatically converted to hyphen (“-”).<br /><strong>Note 1</strong>: In the regex, you must allow “-”; otherwise tags with spaces will be rejected.<br /><strong>Note 2</strong>: Existing tags with spaces will <em>not</em> be converted automatically.',

	'TOPICTAGS_ALLOWED_TAGS_EXP_FOR_USERS'		=> 'Explanation for users',
	'TOPICTAGS_ALLOWED_TAGS_EXP_FOR_USERS_EXP'	=> 'This text is shown to the users and should explain which tags are allowed and which not.<br />default: ' . $lang['ACP_RH_TOPICTAGS_REGEX_EXP_FOR_USERS_DEFAULT'],

	'TOPICTAGS_MANAGE_TAGS_EXP'					=> 'The table shows all existing tags. Here you can: <ul><li>delete a tag (and all its assignments);</li><li>edit a tag (double click the tag’s name for faster workflow);</li><li>merge tags by editing a tag and setting its name so that it equals another tag – they will be merged automatically.</li></ul>',
	'TOPICTAGS_NO_TAGS'							=> 'There are no tags yet.',
	'TOPICTAGS_TAG'								=> 'Tag',
	'TOPICTAGS_ASSIGNMENTS'						=> 'Assignments',
	'TOPICTAGS_NEW_TAG_NAME'					=> 'New tag name',
	'TOPICTAGS_NEW_TAG_NAME_EXP'				=> 'Please enter the new tag name.',
	'TOPICTAGS_TAG_DELETE_CONFIRM'				=> 'Are you sure that you want to delete the tag <i>%s</i>? This will delete the tag from <strong>all topics</strong> where it is assigned. <em>This cannot be reverted.</em>',
	'TOPICTAGS_TAG_DELETED'						=> 'The tag has been deleted.',
	'TOPICTAGS_MISSING_TAG_ID'					=> 'Missing tag ID.',
	'TOPICTAGS_TAG_CHANGED'						=> 'The tag has been changed.',
	'TOPICTAGS_TAG_MERGED'						=> 'The tag has been merged with tag “%s”.',
	'TOPICTAGS_MISSING_TAG_NAMES'				=> 'Missing tag-names.',
	'TOPICTAGS_TAG_INVALID'						=> 'The tag “%s” is invalid. Please check your tag-settings.',
	'TOPICTAGS_TAG_DOES_NOT_EXIST'				=> 'The tag “%s” does not exist.',
	'TOPICTAGS_NO_MODIFICATION'					=> 'The tag was not changed.',

	'TOPICTAGS_SORT_NAME_ASC'					=> 'Tag names, A&rarr;Z', // &rarr; is a right-pointing arrow (→)
	'TOPICTAGS_SORT_NAME_DESC'					=> 'Tag names, Z&rarr;A',
	'TOPICTAGS_SORT_COUNT_ASC'					=> 'Tag uses, least&rarr;most',
	'TOPICTAGS_SORT_COUNT_DESC'					=> 'Tag uses, most&rarr;least',
	'TOPICTAGS_SORT_AGE_ASC'					=> 'Tag age, oldest&rarr;newest',
	'TOPICTAGS_SORT_AGE_DESC'					=> 'Tag age, newest&rarr;oldest',

	'TOPICTAGS_AGE_NOTE'						=> 'Note: Sorting by tag age is really by order of appearance in the database, so it may not always be accurate. If an older tag is merged to a newer one, it takes on the ID and age of the newer one (and vice versa). A database optimisation utility might also upset the apparent tag ages if it re-indexed tags by some new criterion, such as alphabetically.',
));
