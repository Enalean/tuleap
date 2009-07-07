<?php
/*
 *  en_US.php
 *  gitphp: A PHP git repository browser
 *  Component: i18n - en_US
 */

$strings = array(
	/*
	 * Error message that tells the user that
	 * there were no projects found in the projectroot
	 */
	'noprojectsfound' => 'No projects found',

	/*
	 * Error message that tells the user that the projectroot
	 * they have set up is not a directory
	 */
	'projectrootisnotadirectory' => 'Projectroot is not a directory',

	/*
	 * Error message that tells the user that they have not set
	 * a projectroot
	 */
	'noprojectrootset' => 'No projectroot set',

	/*
	 * Tells the user that all searching has been disabled
	 */
	'searchhasbeendisabled' => 'Search has been disabled',

	/*
	 * Tells the user that searching with files has been disabled
	 */
	'filesearchhasbeendisabled' => 'File search has been disabled',

	/*
	 * Tells the user that their search string must be a minimum of 2 characters
	 */
	'searchtooshort' => 'You must enter search text of at least 2 characters',

	/*
	 * Tells the user that no matches were found for their search, where
	 * %1$s is the search string
	 */
	'nomatches' => 'No matches for \'%1$s\'.',

	/*
	 * Used to indicate multiple projects - links back to the project
	 * listing page
	 */
	'projects' => 'projects',

	/*
	 * Used as the header for the project name column on the project list
	 */
	'headerproject' => 'Project',

	/*
	 * Used as the header for the description column on the project list
	 */
	'headerdescription' => 'Description',

	/*
	 * Used as the header for the owner column on the project list
	 */
	'headerowner' => 'Owner',

	/*
	 * Used as the header for the age (last time project was changed) column in the
	 * project list
	 */
	'headerlastchange' => 'Last Change',

	/*
	 * Used as the header for the actions column (links to various other pages for
	 * that project)
	 */
	'headeractions' => 'Actions',

	/*
	 * Used as the name for the main project summary page
	 */
	'summary' => 'summary',

	/*
	 * Used as the name for the shortlog (abbreviated commit log) page
	 */
	'shortlog' => 'shortlog',

	/*
	 * Used as the name for the log (full commit log) page
	 */
	'log' => 'log',

	/*
	 * Used as the name for a single "commit" to the repository - 
	 * for the commit page, as well as on other pages when referencing
	 * a single commit object
	 */
	'commit' => 'commit',

	/*
	 * Used as the name for the commitdiff page, which shows all changes
	 * in a single commit
	 */
	'commitdiff' => 'commitdiff',

	/*
	 * Used as the name for the tree page and to reference tree objects - 
	 * "trees" being a particular project's directory of files (or subdirectory)
	 * at a given revision
	 */
	'tree' => 'tree',

	/*
	 * Used as the name for the snapshot action, which sends a tarball of the
	 * project at a given revision
	 */
	'snapshot' => 'snapshot',

	/*
	 * Used as the name for the tags page/section, which lists all tags for a project
	 */
	'tags' => 'tags',

	/*
	 * Used as the name for the single tag page, which shows all info on a single
	 * tag object
	 */
	'tag' => 'tag',

	/*
	 * Used as the name for the heads page/section, which lists all heads for a project
	 */
	'heads' => 'heads',

	/*
	 * Used as the name for the history page, which lists all commits where a particular
	 * file was changed
	 */
	'history' => 'history',

	/*
	 * Used as the name for the blob page, which shows the content of a file at a given
	 * revision (its 'blob' data)
	 */
	'blob' => 'blob',

	/*
	 * Used as a link to show the differences in a file for a commit
	 */
	'diff' => 'diff',

	/*
	 * Used as a link to diff a particular revision of a file to the current version
	 */
	'difftocurrent' => 'diff to current',

	/*
	 * Used as the name for the search action, and to caption the search box
	 */
	'search' => 'search',

	/*
	 * Used as the caption on the 'RSS' button, which gets a feed of the most recent
	 * commits to a project
	 */
	'RSS' => 'RSS',

	/*
	 * Used as the caption for the 'OPML' button, which gets a list of projects in OPML format
	 */
	'OPML' => 'OPML',

	/*
	 * Used as the caption for the 'TXT' button, which gets a list of projects in plaintext
	 */
	'TXT' => 'TXT',

	/*
	 * Used as a link on various actions (blob, blobdiff, commitdiff, etc) to get the plaintext
	 * version of it
	 */
	'plain' => 'plain',

	/*
	 * Used as an indicator that something was added
	 */
	'new' => 'new',

	/*
	 * Used as an indicator that something was deleted
	 */
	'deleted' => 'deleted',

	/*
	 * Used as an indicator that something is a file - for example, that we are diffing a
	 * file or searching within files
	 */
	'file' => 'file',

	/*
	 * Used to denote the author of a particular commit, or that we want to search
	 * authors
	 */
	'author' => 'author',

	/*
	 * Used to denote the committer of a particular commit, or that we want to search
	 * committers
	 */
	'committer' => 'committer',

	/*
	 * Used as the link to the previous page, when paginating through log entries
	 * or search results
	 */
	'prev' => 'prev',

	/*
	 * Used as the link to the next page, when paginating through log entries
	 * or search results
	 */
	'next' => 'next',

	/*
	 * Used as the link to the first page, when paginating through search results
	 */
	'first' => 'first',

	/*
	 * Used as the link to the HEAD, when paginating through log entries
	 */
	'HEAD' => 'HEAD',

	/*
	 * Used to indicate the description of the project, on the project summary page
	 */
	'description' => 'description',

	/*
	 * Used to indicate the owner of the project, on the project summary page
	 */
	'owner' => 'owner',

	/*
	 * Used to indicate the age (last change) of the project, on the project summary page
	 */
	'lastchange' => 'last change',

	/*
	 * Used to indicate the object that is the parent of this one (eg the commit that
	 * came right before this one)
	 */
	'parent' => 'parent',

	/*
	 * Used to indicate the object (commit, etc) that is attached to a tag
	 */
	'object' => 'object',

	/*
	 * Used to indicate a new object was added, where
	 * %1$s is the type of object (file or tree)
	 */
	'newobject' => 'new %1$s',

	/*
	 * Used to indicate a new object was added, where
	 * %1$s is the type of object (file or tree) and
	 * %2$s is the new mode
	 */
	'newobjectwithmode' => 'new %1$s with mode: %2$s',

	/*
	 * Used to indicate an object was deleted, where
	 * %1$s is the type of object (file or tree)
	 */
	'deletedobject' => 'deleted %1$s',

	/*
	 * Used to indicate an object's type was changed, where
	 * %1$s is the old type and %2$s is the new type
	 */
	'changedobjecttype' => 'changed from %1$s to %2$s',

	/*
	 * Used to indicate an object's mode was changed, where
	 * %1$s is the mode change (either just one mode or
	 * mode1->mode2)
	 */
	'changedobjectmode' => 'changed mode: %1$s',

	/*
	 * Used to indicate that an object was moved/renamed,
	 * where %1$s is the original name, and
	 * %2$d is the percentage similarity
	 */
	'movedobjectwithsimilarity' => 'moved from %1$s with %2$d%% similarity',

	/*
	 * Used to indicate that an object was moved/renamed,
	 * where %1$s is the original name,
	 * %2$d is the percentage similarity, and
	 * %3$s is the mode
	 */
	'movedobjectwithsimilaritymodechange' => 'moved from %1$s with %2$d%% similarity, mode: $3$s',

	/*
	 * Used to indicate something happened a certain number of
	 * years ago, where %1$d is the number of years
	 */
	'ageyearsago' => '%1$d years ago',

	/*
	 * Used to indicate something happened a certain number of
	 * years ago, where %1$d is the number of years
	 */
	'agemonthsago' => '%1$d months ago',

	/*
	 * Used to indicate something happened a certain number of
	 * weeks ago, where %1$d is the number of weeks
	 */
	'ageweeksago' => '%1$d weeks ago',

	/*
	 * Used to indicate something happened a certain number of
	 * days ago, where %1$d is the number of days
	 */
	'agedaysago' => '%1$d days ago',

	/*
	 * Used to indicate something happened a certain number of
	 * hours ago, where %1$d is the number of hours
	 */
	'agehoursago' => '%1$d hours ago',

	/*
	 * Used to indicate something happened a certain number of
	 * minutes ago, where %1$d is the number of minutes
	 */
	'ageminago' => '%1$d min ago',

	/*
	 * Used to indicate something happened a certain number of
	 * seconds ago, where %1$d is the number of seconds
	 */
	'agesecago' => '%1$d sec ago',

	/*
	 * Used to indicate something is happening right now
	 * (less than 3 seconds ago)
	 */
	'agerightnow' => 'right now',

	/*
	 * Used to indicate a certain number of files were changed in a commit
	 * where %1$d is the numebr of files changed
	 */
	'fileschanged' => '%1$d files changed',
);

?>
