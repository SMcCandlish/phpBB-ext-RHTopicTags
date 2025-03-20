<div align="right">
<a href="https://ko-fi.com/smccandlish"><img src="https://ko-fi.com/img/githubbutton_sm.svg" alt="Ko-Fi" /></a>
</div>
[![Build Status](https://travis-ci.org/RobertHeim/phpbb-ext-topictags.svg?branch=master)](https://travis-ci.org/RobertHeim/phpbb-ext-topictags)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/RobertHeim/phpbb-ext-topictags/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/RobertHeim/phpbb-ext-topictags/?branch=master)

This fork is not ready to use on public/production sites if this note is still present here.

The fork is intended for:

1. Test-integration of various fixes people have worked out to get the extension this working in phpBB 3.3.5 and later.  It has not been tested in this regard with 3.5.13 and later, nor with 3.3.4 or earlier.
2. Implementation of a variety of fixes as well as feature improvements. It has not been tested in this regard with 3.5.6 and later, nor with 3.3.4 or earlier.


Topic Tags 3.0.2
================

phpBB 3.3.x extension (expected to be compatible with at least 3.3.5 through 3.3.12), that adds the ability to tag topics with keywords.

**Some configuration required!** Please read the documentation carefully. This is not a fire-and-forget extension and will not work properly without some quick configuration steps.

## Features

### Common

- add searchable tags when posting a new topic
- get tag suggestions based on existing tags
- edit tags when editing first post of topic
- SEO: tags are added to meta-content keywords in viewtopic
- tags are shown in viewforum (can be disabled in ACP)
- enable tagging of topics on a per-forum basis
- responsive layout
- full UTF-8 support
- Tag cloud feature (can be disabled in ACP as to display on index or entirely)

### Search
- search topics by tag(s)
- `/tag/{tags}/{boolean}/{case-sensitivity}` shows topics tagged with all (boolean = `AND`, the default) or any (boolean = `OR`) of the given tags, where tags are comma separated tags and case-sensitivity can be `true` to search case-sensitive or `false` (the default), e.g.:
 - `/tag/tag1,tag2/OR` lists topics that are tagged with tag1 OR tag2 OR tAG2
 - `/tag/tag1,tag2/AND` lists topics that are tagged with \[tag1 AND (tag2 OR tAG2)\]
 - `/tag/tag1,tag2` lists topics that are tagged with \[tag1 AND (tag2 OR tAG2)\] (boolean=default=`AND`, case-sensitivity=default=`false`)
 - `/tag/tag1,tAG2/AND/true` lists topics that are tagged with (tag1 AND tAG2, but will not match tag2)
 - tags are essentially case-insensitive by default (if you create a tag "foo", then "Foo" or "FOO" or "fOO" will be the same tag); the case-sensitivity search feature is primarily of use for finding tags that are inappropriately upper-case ("Dogs") or inappropriately lower-case ("europe") in their database-registered form.

### Tag-Cloud
- `/tags` (plural) shows a tag cloud
- ACP option for tag cloud to be displayed on the board index page or not
- ACP option to limit count of tags shown in tag cloud on the index page
- dynamic tag-size in tag cloud depending on its usage count
- ACP option to also enable/disable display of tags' numeric usage counts in tag cloud

### Advanced configuration
- configure a regex to decide which tags are valid and which are not
- maintenance functions in ACP &gt; Extensions &gt; RH Topic Tags
- tag whitelist
- tag blacklist
- user and mod+admin permissions for who can add/edit tags
- spaces in tags are converted to "-" by default (you can disable that in ACP)
- manage existing tags in ACP
 - delete tag
 - rename tag
 - merge tags (rename one tag to the same name as another tag and they will automatically be merged)

## Compatibility

This extension is known-working in phpBB 3.3.5, and refines code working up to at least phpBB 3.3.12.  It may work in earlier or later 3.3.x versions, but has not been tested in them. This is coded with a "soft dependency" on 3.3.5. If you want to try it in an earlier version and can't despite "softness" of that requirement, just change `3.3.5` to `3.3.0` in the extension's `composer.json`.

## Installation

If you are upgrading from a previous version you **must** disable it first (ACP &gt; tab Customise &gt; Manage extensions &gt; RH Topic Tags &ngt; Disable), then remove its files entirely (e.g. move the old `topictags` extension directory to your home directory as a backup), then install the new one, and enable it. Some fairly minimal database changes are needed (should be automatically handled by the migrations files), but the code changes are significant and your board will probably break if you try to overwrite the files in-place. Some of them have moved due to changes in phpBB's namespace security, etc.

In the instructions that follow, the directory `ext/` means the phpBB extensions folder under whatever server directory path you used for your phpBB installation. It should be at the same level as your main phpBB installation directory. E.g., if most of phpBB is at `/var/httpd/webdocs/phpbb/` your extensions should be in `/var/httpd/webdocs/ext/` (but that might vary by version; if not found, try instead`/var/httpd/webdocs/phpbb/ext/`). And the path to your website's phpBB files is going to vary widely from system to system, and could be something like `/home/admin/web/yoursitename.com/public_html/phpBB/` or `/serv/web/public/yoursitename/production/phpbb3/` or `/home/yourusername/public_html/phpbb/`. So, check first and adjust the instructions below to compensate.

### 1. Clone
Clone (or download and move/upload) the repository into a subdirectory of your phpBB extensions directory, as: `ext/robertheim/topictags`:

```
cd ext/
git clone https://github.com/SMcCandlish/phpbb-ext-topictags.git robertheim/topictags/
```

We are using the original `robertheim/` path name because most of the code is his, and you cannot run this copy and the origial by RH at the same time, so there's no reason not to directly replace the original. 

This folder structure is important; do not try something like `ext/topictags/`; that will not work, as all extensions have to be subcategorized by a developer name.

### 2. Activate
Log into your board. Go to ACP (Admin Control Panel) and login again as an admin. Go next to tab Customise &gt; Manage extensions &gt; enable RH Topic Tags

Go to ACP &gt; tab Forums, and edit any forum (or create a test one); click the green gear icon to edit the forum, and under General forum settings set "Enable RH Topic Tags" to *Yes*; submit the changes with the button at the bottom of the page.

### 2.5. Purge phpBB cache

Depending on your phpBB version, you may find a "Purge the cache" option at AGP &gt; Admin Index (which is at top right of the AGP window, or click tab General to get to same page). Otherwise, you may find a "Purge cache" option in the left menu at AGP &gt; General. If you do not, you might have to login to your server via FTP/FTPS/SFTP/SSH, and delete the contents of `phpbb/cache/` *except* for `index.htm` and `.htaccess`. If you find no such files there and instead find a `driver/` subdirectory and a `service.php` file, then delete nothing (you have a different version of phpBB).

### 3. Configure

Go to ACP &gt; tab Extensions &gt; RH Topic Tags.

One **important thing to check for and fix**: at ACP &gt; Extensions &gt; RH Topic Tags &gt; Settings &gt; Tag settings, look in the regex field for "Regular Expression for allowed tags:", and if you see `\\s` change it to `\s`, and same with any other other cases of `\\` which must be changed to `\`. By default, these will be `\\p` → `\p`, `\\s` → `\s`, and `\\-` → `\-`. There seems to be a "translation" problem somewhere between PHP, JS, and SQL that is extra-escaping `\` and turning it into `\\`, which will break our character-matching intentions. 
#Special case exception: if you customized the regex in any `/robertheim/topictags/language/xx/topictags_acp.php` file to explicitly permit the `\` character in tags, that would have been coded as `\\`, which in this field may show up as `\\\\` or `\\\` and needs to be changed back to `\\`. 
#Special case exception: If you attempted to permit `/` (perhaps as `\/`), this **must be removed** because the delimiter between tags in some parts of the code is `/` (no tag itself can contain `/`). Likewise, you must not permit `,` (including as `\,`) because that character is used as the delimiter by the `https://yoursite.com/tag/tag1,tag2` search functionality. This issue may be examined in the future to see if it can be worked around and these character become permissible in tags.

For some tips on customizing your regex, see the extensive comment documentation inside `/robertheim/topictags/language/en/topictags_acp.php` (or the `es`, `fr`, or `ru` version).

When done, submit the changes with the button at the bottom of the page.

### 4. Test
Go to a forum in which you have enabled tags. Create a new test post in it (starting a new topic), and you should see a tags instructional line about the allowed characters, and below this a text-entry form field for tags. Try adding some. Save the post, and see how it is tagged. Optionally try editing the first post of an existing topic in that forum, and you should be able to add tags to it. Now re-edit a post with tags and try changing them. Next, at the bottom of your board's front page you should see a tag cloud list that includes the new tags you just created (unless you turned off that feature, in which case go to https://yoursitename.com/tags to see the tag cloud).

Back at ACP &gt; Extensions &gt; RH Topic Tags &gt; Manage Tags, you should be able to change the name of or delete any of these tags. Deleting one removes it from any post that had it.

A point of confusion to look out for: A topic's tags are *shown* on the first post of the top of every page of posts in the topic, but can only be *edited* in the first post in the entire topic, not the top post in a subsequent page of posts.

### 4.5 Troubleshooting

If the extension does not work for you, make sure you purged the cache. Next, check that the forum you are trying to use it in has permissions set to use this extension (either individually in that forum's configuration, or by turning on the feature everywhere (ACP &gt; tab Extensions &gt;  RH Topic Tags &gt; Settings &gt; Configuration &gt; Enable RH Topic Tags in all forums). Failing that, ensure that the filesystem permissions on the extension's files are correct (must be readable by whatever operating-system user the webserver runs as, and/or by its group). And ensure that the path to it is correct, at `ext/robertheim/topictags/` in your phpBB setup, not at something like `ext/topictags/`. And that you user account has permission to create/edit tags.

If you are using phpBB 3.3.8 or later, *and* it is not working properly for you, see if any recent-ish communication at https://www.phpbb.com/customise/db/extension/rh_topic_tags/support addresses the issue yet. When reporting an issue, be as specific as possible; no one can read your mind, or your logs.

Two alleged but dubious fixes for some phpBB 3.3.8 compatibility issues were posted in a 2022 third-party attempt at phpBB 3.3.8 compatibility, but based on RH's old branch for phpBB 3.1.x instead of the master (for phpBB 3.3.x). Someone later, working toward phpBB 3.3.9 compatibility, reverted the first of those suggested changes but kept the second (and cleaned up some of the directory structure). Another, for phpBB 3.3.9, reverted both (without the directory cleanup), and their change was accepted into RH's main branch, with others reporting it working with versions as late as phpBB 3.3.12. Someone later yet, for phpBB 3.3.10, also reverted both of the above changes in their own fork, but kept the cleaned-up directory structure.

This version takes the last of these approaches, and goes further, and these changes are all entirely working as far back as phpBB 3.3.5, but have not yet been tested in later versions.

### 5. Customizing for themes

The default bright colors and white form-field backgrounds in this extension do not play nice with dark themes (you're especially likely to get unreadable light tag text on an also-light tag background).

If your site uses a dark theme, and that's the only theme available, then you could just change the CSS to suit your needs. This can be quite a hassle due to the complexity of the CSS, but it is doable with some patience; the developer Inspector/Console mode in your browser should help (usually accessed with Ctrl-Shift-I, or Cmd-Shift-I on a Mac). Just remember that you have to purge the cache on the board before testing each change (you might also have to bypass your own browser cache, typically with Ctrl-Shift-R in Windows and Linux, Cmd-Shift-R in macOS). Howeover, going this route makes your changes vulnerable to overwriting if the extension is updated later. It is better to create a CSS file for your specific theme(s), as described next.

If your site has multiple themes and one or more of them are dark, then what to do for each such theme is copy and modify a key CSS file. Using a example theme named MyDarkTheme, the files for which go in a `styles/mydarktheme/` directory and subdirectories thereof, you want to copy `ext/robertheim/topictags/styles/all/theme/rh_topictags.css` to `ext/robertheim/topictags/styles/mydarktheme/theme/rh_topictags.css`. Near the top of it (after the opening comment), add the following lines (with colors that suit your needs):

```
div.tags
{
	background-color:	#000;
}

.ng-valid
{
    background-color:	#323D43;
}

.tag-item
{
	color:	#97AAAF;
}
```

Then adjust whatever other colors you need.

A sample `styles/mydarktheme/theme/rh_topictags.css` is provided in this package, with the above already done and some other colors integrated to go along with that. You can simply copy or move that `rh_topictags.css` file into your actual dark theme's `theme/` directory, see how it looks, and make adjustments from there.

Tip: This extension's default behavior is to have tags show with a particular background color in most circumstances, but change to a very different background color (and text color) when hovered-over if (but only if) you are editing the post and its tags. This can be problematic, in any theme other than the ProSilver this was designed for. The background color(s) of a tag have to work with at least four (up to six) different text colors: the tag as shown on a post when editing it; the same when hovering over it (by default, the extension changes the background color here, and only here); the linked tag as shown on a post when reading it (using your theme's default link color); and same when hovering over it (with your theme's default hovered-link color); you might also have a visited link color and a short-lived active link color (when the link was just now clicked). It is very difficult to arrive at two different background colors that work well with this array of text colors. Thus, it is most sensible to use a single background color for all of these purposes (override the extension's behavior of changing that color when hovering in edit mode); make it one that works with your site's default link colors, and then just use a grey or grey-ish "tag as shown on a post when editing it" text color, and a white or white-ish text when hovering over that (or black or black-ish text if the background is light), with the grey/grey-ish text color being fairly close to but distinct from your default link color, so that the text in every circumstance is legible on this background.

Depending on what you've done with your sites's default theme and any light themes, some tweaks in this regard might be needed for any/all of them.

## Updating to a newer version

Make backups of any files you have modified, e.g. `.css` files for custom colors, and any `language/en/topictags_acp.php` files modified for particular sets of permissible characters in tags (which is better done in the ACP section for configuring RH Topic Tags). You may need to manually merge such changes back into the extension after upgrading it.

Go to ACP &gt; tab Customise &gt; Manage extensions &gt; disable RH Topic Tags.

If you are upgrading to a newer version of the SMcCandlish fork already installed via git earlier (see way above), just do this:

```
cd ext/robertheim/topictags/
git pull
```

Or download it to your local computer and copy the files over by SFTP, SCP, or whatever means you use for this purpose.

As noted above, if you are upgrading from an old version – if you're not sure, it'll be one that has an `ext/robertheim/topictags/styles/all/angular/` directory inside it – the entire extension needs to be disabled and removed first. To disable: ACP &gt; tab Customise &gt; Manage extensions &gt; RH Topic Tags &ngt; Disable. To remove, something like:

```
cd [...]/ext/robertheim/
mv topictags ~/
```

(That's moving it to your home directory as a backup, instead of just deleting it.)

Then re-download the current version (while still in the `ext/robertheim/` directory) with:

`git clone https://github.com/SMcCandlish/phpbb-ext-topictags.git topictags/`

(or download it to your local computer and copy the files over by SFTP, SCP, or whatever means you use for this purpose).

Either way, now go to ACP &gt; tab Customise &gt; Manage extensions &gt; RH Topic Tags &ngt; enable.

Then purge the cache again as described above.

If you were already using an old version prior to RH Topic Tags v3.0.2, then the permitted-characters regex and the description of it will be "baked into" your phpBB database already, and may not agree with what is in the current appropriate `language/xx/topictags_acp.php` file. What the new defaults are will be shown in ACP &gt; tab Extensions &gt; RH Topic Tags &gt; Settings, to the left of the form fields for changing them. (Even if you already have a customized regex, it's worth reading the comment notes in that language file anyway, so you can be fully informed about your choices, which have changed somewhat.)

## Support

https://www.phpbb.com/community/viewtopic.php?f=456&t=2263616

## Credits

- RobertHeim @ GitHub.com, for the original extension. RH's development of it appears to have ceased in 2020, even with regard to accepting others' pull requests (thus this fork).
- Michael Benford (mbenford @ GitHub), for the ngTagsInput Angular.js directive that is used in the back end of RH Topic Tags: https://github.com/mbenford/ngTagsInput
- pierrdu @ phpBB.com forum, for phpBB 3.3+ Symfony upgrade patches to routing.yml and services.yml (already integrated into RobertHeim's master branch by the time I cloned it).
- sanekplus, thegioiluatsu, Naguissa, bonnp @ phpBB.com, for approaches to getting the Topic Tags version for phpBB 3.2 to work in 3.3+; while their changes were ultimately undone and the issue resolved a different way, what they tried was constructive in helping identify what the underlying problems were.
- zipurman @ phpBB.com, and Dark❶ @ phpBB.com (Dark1z @ GitHub), for a phpBB 3.3.8+ permissions fix to main_listener.php.
- Marc @ phpBB.com, for documenting phpBB 3.3.9+ changes in Twig behavior that require things like `INCLUDECSS @robertheim_topictags/rh_topictags.css` with the `@dev_extension` syntax instead of a relative path. While these changes were already integrated into RH's master codebase at the time of this fork, it has been relevant information in weeding through prior attempts to update the behavior of this extension, and helped later in resolving issues with `@rogterheim_topictags/dir/../file.ext` paths that had to change to not use `/../` syntax.
- TJK @ phpBB.com, for further suggestions toward php3.3.8+ compatibility.
- dimassamid, Lord Phobos, and SANSI @ phpBB.com, for further suggestions toward php3.3.9+ compatibility, especially the `/../` paths no longer working.
- mi1eurista @ GitHub, for integrating several of the above tweaks into a single patch (which RH never got around to integrating).
- andrigamerita and Fantabulum @ GitHub, for confirming functionality of basic fixes in phpBB 3.3.10 and 3.3.12.
- iandoug @ phpBB.com, for figuring out where the regex stuff is to control what characters are permitted in tags.
- combuster @ phpBB.com, for documenting that the phpBB cache has to be cleared or malfunctions will occur.
- Finally, S. McCandlish (SMcCandlish @ GitHub) for bashing and munging on this to behave better in 2025 onward.

## Change log for 3.0.2

- Updated `README.md` to account for all of the changes, and to just read better.
- Fixed the stupendous security/privacy problem of the tag cloud being visible (in two different ways) to non-logged-in visitors. Various sites could be using this software for sensitive information, especially in an intranet circumstance (client names, internal project code-names, etc.).
- Fixed additional security problem that tags in the cloud were not access controlled even for logged in users, such that low-access users could see names of tags that only existed in forums they had no access to, and were given tag-usage counts that includes uses in forums they had no access to; this leaked both private tag names and revealed that there were forums the user wasn't intended to even know existed.
- Tweaked `event/main_listener.php` for compatibility with later versions of phpBB 3.3.x.
- Rearranged the codebase in ways (figured out by others credited above) that resolve problems with later versions of phpBB 3.3.x (e.g. due to having files stuck in `styles/all/angular/` and called with paths that have `/../` in them).
- Rearranged the codebase in new ways that just make far more sense (e.g., files that are basic functionality of the extension, not specific to ProSilver, are no longer in `styles/prosilver/template/` but in `styles/all/template/`).
- Added a sorting function to `service/tags_manager.php`, to sort a tag list alphabetically (per localization rules), and then secondarily apply human-friendly "natural" sorting of numeric tags. This sorts tags displayed for a particular topic, tags shown in the cloud, and the default administrative display of all tags in the ACP.
- Changed `language/en/topictags_acp.php` to permit more characters as valid in tags, such as underscore, dot, and accented letters. Commented-out code blocks in there (with notes) also add support for Greek, Cyrillic & Hebrew, or for permitting letters/idiograms in any language at all, or even for dumbing it back down to forbid diacritics. Also permits tags of min. 2 characters by default, to account for common acronyms like "AI" and "UK".
- That upgrade has also been done in the interface files for French, Russian, and Spanish (the other languages supported by this extension so far). The Russian implementation defaults to the regex that permits Cyrillic letters, for obvious reasons. All the rest default to Western European (including Latin-alphabet letters with diacritics since they are common, and even used in English, e.g.: Brontë, financée, façade, jalapeño).
- Edited `language/en/topictags.php` and `language/en/topictags_acp.php` to make much more sense; there were various grammar and typographical glitches, as well as some unclear wording generally. Only some of these improvements have been propagated to the other-language versions, since some of it needs native-level fluency in those languages. One particular issue in them that's been fixed was rampant abuse of SCREAMING ALL-CAPS as a form of emphasis instead of using the standard HTML `em` and `strong` elements for semantic emphasis.
- The typography in the French and Russian versions has been improved with regard to guillements instead of English- and Spanish-style quotation marks, and other punctuation tweaks, as well as indication of Cyrillic alphabetical order in the `ru` version.
- Also replaced the awkward ⇒ arrow with the → one in a menu, in all languages, so it's easier to tell what the character actually is.
- Fixed missing internationalization strings for Russian and Spanish. And various previously untranslated strings have been translated, including for French (via Google Translate with some manual adjustments). Even the commented-out notes about various regex options, in the source of `language/xx/topictags_acp.php` files, have been translated.
- In all languages, incorrect references to minus (−) or dash (–, —) characters have been replaced with correct references to the hyphen character (-, which Unicode confusingly calls "hyphen-minus"), as the character to which spaces will (optionally) be converted in tags.
- Fixed invalid HTML in `styles/all/template/tagcloud.html`.
- Fixed trivial typos in `styles/all/theme/rh_topictags.css` and `service/tags_manager.php`.
- De-obfuscated the code of `styles/all/ng-tags-input.min.css`, since humans trying to adjust the display of their websites need to read it, and the efficiency gained by compacting this particular file is virtually non-existent. If you have a mega-busy board and need to compact all of your code, then do that with a tool for this on your production server. We don't wreck the development-side code for all humans just so one person's website runs a tiny bit faster.
- Updated/corrected `composer.json` for version, dev & requirements info. For some reason, RH's original master-branch version claimed it was v3.0.0 in this file (but nowhere else – it was otherwise labeled 1.0.1), while the stale development branch for phpBB 3.1.x was even more confusingly numbered 1.0.2. Subsequently, various forks have been called 1.0.1, 1.0.2 (possibly also 1.0.3), 3.0.0, and 3.0.1. This newest one (as of this writing) is numbered 3.0.2, since it supersedes *all* of those.
- Added `migrations/release_1_0_3.php`, and `migrations/release_3_0_0.php` through `migrations/release_3_0_2.php` files that should account for any other variants people have installed. Not sure what purposes every thing in these files serve, but them being missing would presumably be problematic somehow.
- Fixed bug in which the max. tag length (default 30 characters) was constrained to 20 when editing an existing tag, which would break things (e.g. edit a 28-character tag to fix a 1-letter typo, then not be able to save it, or save it as inappropriately truncated).
- Added additional sorting options in the ACP. You can now get alphanumeric (default), by usage frequency, and by database order (usually corresponding to tag age, unless DB optimzation tools have mangled the order). All of these has ascending and descending variants.
- Fixed bugs in the merge/rename/delete process (could cause merges to fail and result in total deletion of a tag and all its assignments!)
- Made tags case-insensitive by default; this will stop the common problem of a tag like "chicken" existing and someone typing in "Chicken" or "CHICKEN" instead, requiring manual admin intervention to merge them. They are now auto-merged. There was already some code in RH's original extension that made it clear that case sensitivity was intended to be an administrative choice. This has been extended throughout the codebase, though there is not yet an administrative selector to turn on case-sensitive tagging. That would only be of practical use on a system in which only admins/mods and selected other users were permitted to tag, and only from a predefined list of tags, otherwise the problem of unintentional duplicates would again run rampant.
- Added SQL injection attack checks to the processing of tag names. While phpBB likely has some of that built into all form processing by default, mine is almost certainly more robust, as it is based on extensive and recently updated research into the types and techniques of this sort of security breach.
- Streamlined a great deal of the code to be more efficient and less redundant, better documented in comments, clearer to read (e.g. using proper if-else statements instead of ternary notation in most circumstances, since the latter are poorly understood by non-experts), and written in better English.
- Normalized code in different functions to behave consistently and to reuse existing functionality more. The original extension was clearly developed in "spurts" of activity, without much regard to prior work. E.g. the tagcloud script was producing unnecessary direct calls to the database instead of reusing db-access utility code already written in a script for that work, and was bypassing all access and other security measures in the tagsmanager script.
- Added finer-grained tag size display in the tag cloud.
- The tag cloud was originally sorting as well as size-adjusting tags based on frequency of use, which basically robbed us of the ability to use the sorting order more sensibly; if tag size already indicates use level, then having tag order do the same thing is redundant and unhelpful. So the order has now been changed to human-friendly alphanumeric, consistent with the rest of the tag displays (in topics and in the ACP's default view).
- Fixed misspelling of lead dev's name as "Robet" at every place that occurred.
- Fixed copyright notices in file headers to use actual copyright symbol ©, instead of "(c)" which is actually (and always has been) legally meaningless nonsense. A valid copyright notice is of the form "Copyright YYYY CopyrightHolderName" or "© YYYY CopyrightHolderName" (while "Copyright © YYYY CopyrightHolderName" is redundant but not actually invalid). A string of "(c) YYYY CopyrightHolderName" is useless gibberish. Why anyone would try to use "(c)" I don't know; this is 2024 not 1984, and Unicode exists for a reason. This is GPL anyway, so it doesn't really matter much, but I'm a stickler for such things.

## Known issues and to-do list

- Migrations files not completed yet; installing and testing this means disabling an old install and removing it, then uploading/checking-out the current version, and re-enabling it, and purging cache.
- Someone on the phpBB forum reported that one or more earlier attempts at updating this extension (attempts on which the present code is partially based) did not work in phpBB 3.3.14 (currrent version as of 2024-12), but they did not provide any debugging information at all, so it's just unknown what the issue might be, or whether it's even an actual issue rather than someone's misconfiguration. No attempts to get this to work under 3.3.13 have been reported yet, only 3.3.5 through 3.3.12.
- The tags created with this extension are not searchable by the built-in search function of phpBB or any known search extension. To search for uses of a tag, you have to go to `https://yoursite.com/tag/tagname` where `tagname` is the name of the tag in question, and as noted near the top of this page there are some simplistic search parameters. There is also no wildcard version of this, just a tag cloud. This means the tagging functionality is of sharply limited utility (especially if you have turned off the tag-cloud feature), so this is something to look into improving.
- Going to `https://yoursite.com/tags/` does not work, only `https://yoursite.com/tags` with no trailing slash, but these URLs canonically should be treated as equivalent. There is a `https://yoursite.com/tags/suggest` page (loading a script that takes data from another, and is used by various functions), so `https://yoursite.com/tags/` does actually exist. Fixing this will have something to do with `controller/main.php`.
- See `add-on-comma="false"` in `adm/style/topictags_blacklist.html`, `adm/style/topictags_whitelist.html`, `styles/all/template/event/posting_editor_subject_after.html`, ``, possibly in other places. May be possible to turn on ability of comma to serve same as Enter/Return in ending a tag, which is common/expected behavior, and makes sense since commas cannot be used in tags anyway.
- There is a significant amount of code in here (e.g. in `service/tags_manager.php`) to support an option for case-sensitive tagging (or at least case-sensitive sorting and other display considerations regarding tagging; the full intent isn't clear yet). But there is no ACP option to enable such a thing, it's always defaulting to "false" or was even hard-coded that way in a few places (i.e. to be case-insensitive).
 - Case-sensitivity of actual tags would probably be undesirable for most use-cases (e.g. if someone created a "YouTube" tag and someone else wrotes it as "Youtube" or "youtube" or "YOUTUBE" you'd end up with redundant tags, and this is especially likely on a busy board with many topics and users). But a niche recipe site with tagging controlled by only a few editors, for example, might want to distinguish between Turkey the country and turkey the bird or between Chile the country and chile the pepper. So, having a way to enable this is planned future upgrade.
- In `service/tags_manager.php` there is a "Note: that $query needs to be at least 3 characters long", and code like `if (utf8_strlen($query) < 3)`; this may have to be changed entirely and properly support 2- and 1-character tags in every circumstance, though in testing so far they have been working fine anyway. Depends on exactly what circumstances this 3-char. constraint is being applied to. So far, it looks like this only affects `get_tag_suggestions()`.
- Not an "issue", per se, but the original codebase seemed to be expecting 4 spaces per tab (in most cases, but was not entirely consistent in alignment). I've thus been using 4-per-tab for this project. It may look weird if you're set for 8-per-tab.
- RH used v2.1.1 of ngTagsInput and then custom tweaked it to work around a bug, and this extension has continued with RH's "v2.1.1-1" for years. But ngTagsInput is now at v3.2.0 and apparently has the underlying issue fixed, so the code should be reworked to use that version probably. It's unclear at this point what (if anything) would break if v3.2.0 were just swapped in. Odds are that the syntax of how it is called would have to change in some way.
- Another third party has forked RH Topic Tags, and released a non-free (paid) tagging extension. I have not attenpted to use it directly, but have examined its documentation and screenshots. It is clear that A) I have fixed things and introduced features that developer has not; B) that dev has added features and constraints that I have not; and C) our visions of how a tagging extension should operate are very, very different. (There are also more trivial differences, like the shape of the displayed tags.) In summary, that dev's version appears to be aligned with a tagging model in which admins/mods apply a fixed set of tags to topic, then use drag-and-drop to priority-organize them on a per-topic basis, with tags not being otherwise sorted in such a display at all (probably just raw database order, i.e. creation date, by default). My version is geared for much more public tagging, applies alphanumeric sorting automatically, and has no concept of tags being priority-sortable on a per-topic basis (a subjective determination only a admin/mod or other topic "controller" could make). So, my version is suited for boards where tags are used the way they are on a social-media site, and indeed on most web-boards. The other person's version is geared to highly "curated" tagging by dedicated staff not by general-public users of the board.
- A feature I want to add is a new permission to edit a the first post in a topic (the one to which tags are attached) and only be able to change the tags and other post metadata (title and, if the extension for that is installed, topic description) without being able to touch the content of the post (unless able to do so already by being the original post author or by being an admin/moderator). This would great expand the utility of RH Topic Tags by making tagging a community-managed affair, instead of only something adjustable by a tiny number of overworked invidivuals.
- Another new planned feature is tag descriptions, managable in the ACP, and producing a `https://yoursite.com/taglist` page, and perhaps mouse-hover tooltips that describe the tag. This will take some significant work to implement.
- The human-friendly numeric sorting of tagnames is presently one-pass only, and needs to be recursive to handle tags with multiple numeric string groups (e.g. "Project03-Ver13.7", etc.).
