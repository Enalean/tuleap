<?php
/**
 * GitPHP Resource en_US
 *
 * en_US Resource Provider
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Resource
 */

/**
 * en_US Resource
 *
 * @package GitPHP
 * @subpackage Resource
 */
class GitPHP_Resource_en_US extends GitPHP_ResourceBase
{

	/**
	 * __construct
	 *
	 * Initializes resources
	 *
	 * @access public
	 */
	public function __construct()
	{		/*
		 * Link back to the list of projects
		 * English: projects
		 */
		$this->resources['projects'] = 'projects';
		
		/*
		 * Used as title for and link to project summary page
		 * English: summary
		 */
		$this->resources['summary'] = 'summary';
		
		/*
		 * Used as title for and link to the compact log view
		 * with one line abbreviated commits
		 * English: shortlog
		 */
		$this->resources['shortlog'] = 'shortlog';
		
		/*
		 * Used as title for and link to log view with full
		 * commit messages
		 * English: log
		 */
		$this->resources['log'] = 'log';
		
		/*
		 * Used as title for and link to a list of files
		 * in a directory, which git calls a 'tree'
		 * English: tree
		 */
		$this->resources['tree'] = 'tree';
		
		/*
		 * Used as link to download a copy of the files
		 * in a given commit
		 * English: snapshot
		 */
		$this->resources['snapshot'] = 'snapshot';
		
		/*
		 * Used as title for search page, and also is the
		 * label for the search box
		 * English: search
		 */
		$this->resources['search'] = 'search';
		
		/*
		 * Used as link to and title for the full diff of
		 * all the changes in a commit
		 * English: commitdiff
		 */
		$this->resources['commitdiff'] = 'commitdiff';
		
		/*
		 * Used as link to and title for a diff of a single file
		 * English: blobdiff
		 */
		$this->resources['blobdiff'] = 'blobdiff';
		
		/*
		 * Used as link to and title for the file history, which
		 * displays all commits that have modified a certain file
		 * English: history
		 */
		$this->resources['history'] = 'history';
		
		/*
		 * Used as link to and title for page displaying detailed
		 * info about a tag
		 * English: tag
		 */
		$this->resources['tag'] = 'tag';
		
		/*
		 * Used as link to and title for page showing all tags
		 * in a project
		 * English: tags
		 */
		$this->resources['tags'] = 'tags';
		
		/*
		 * Used as link to and title for page showing all heads
		 * in a project
		 * English: heads
		 */
		$this->resources['heads'] = 'heads';
		
		/*
		 * Used as link to and title for page displaying blame info
		 * (who last touched what line) in a file
		 * English: blame
		 */
		$this->resources['blame'] = 'blame';
		
		/*
		 * Used as link to and title for page displaying a blob,
		 * which is what git calls a single file
		 * English: blob
		 */
		$this->resources['blob'] = 'blob';
		
		/*
		 * Used as link to and title for page displaying info about
		 * a single commit in the project
		 * English: commit
		 */
		$this->resources['commit'] = 'commit';
		
		/*
		 * Used as link to diff this file version with the previous version
		 * English: diff
		 */
		$this->resources['diff'] = 'diff';
		
		/*
		 * Used as link to diff this file version with the current file
		 * English: diff to current
		 */
		$this->resources['diff to current'] = 'diff to current';
		
		
		/*
		 * Used to label the project description
		 * English: description
		 */
		$this->resources['description'] = 'description';
		
		/*
		 * Used to label the project owner
		 * English: owner
		 */
		$this->resources['owner'] = 'owner';
		
		/*
		 * Used to label the time the project was last changed
		 * (the time of the most recent commit)
		 * English: last change
		 */
		$this->resources['last change'] = 'last change';
		
		/*
		 * Used to label the url that users can use to clone the
		 * project
		 * English: clone url
		 */
		$this->resources['clone url'] = 'clone url';
		
		/*
		 * Used to label the url that users can use to push commits
		 * to the project
		 * English: push url
		 */
		$this->resources['push url'] = 'push url';
		
		/*
		 * Used to label the author of the commit, and as a field to search
		 * The author is the person who wrote the changes in the commit
		 * English: author
		 */
		$this->resources['author'] = 'author';
		
		/*
		 * Used to label the committer of the commit, and as a field to search
		 * The committer is the person who put the commit into this project
		 * English: committer
		 */
		$this->resources['committer'] = 'committer';
		
		/*
		 * Used to label the parent of this commit
		 * The parent is the commit preceding this one in the project history
		 * English: parent
		 */
		$this->resources['parent'] = 'parent';
		
		/*
		 * Used as a search type, to search the contents of files in the project
		 * English: file
		 */
		$this->resources['file'] = 'file';
		
		/*
		 * Used as a link to a plaintext version of a page
		 * English: plain
		 */
		$this->resources['plain'] = 'plain';
		
		/*
		 * Used as a link to the first page in a list of search results
		 * English: first
		 */
		$this->resources['first'] = 'first';
		
		/*
		 * Used as a link to the next page in a list of results
		 * English: next
		 */
		$this->resources['next'] = 'next';
		
		/*
		 * Used as a link to the previous page in a list of results
		 * English: prev
		 */
		$this->resources['prev'] = 'prev';
		
		/*
		 * Used as a link to the HEAD of a project for a log or file
		 * (note: HEAD is standard git terminology)
		 * English: HEAD
		 */
		$this->resources['HEAD'] = 'HEAD';
		
		/*
		 * Used when diffing a file, to indicate that it's a new file
		 * English: (new)
		 */
		$this->resources['(new)'] = '(new)';
		
		/*
		 * Used when diffing a file, to indicate that it's been deleted
		 * English: (deleted)
		 */
		$this->resources['(deleted)'] = '(deleted)';
		
		
		/*
		 * Used to indicate the number of files changed in a commit
		 * Comes before a list of files
		 * English: %1$d files changed:
		 * %1$d: the number of files
		 */
		$this->resources['%1$d files changed:'] = '%1$d files changed:';
		
		/*
		 * Used to indicate a new object was added with an access mode
		 * English: new %1$s with mode %2$s
		 * %1$s: the type of object
		 * %2$s: the mode
		 */
		$this->resources['new %1$s with mode %2$s'] = 'new %1$s with mode %2$s';
		
		/*
		 * Used to indicate a new object was added
		 * English: new %1$s
		 * %1$s: the type of object
		 */
		$this->resources['new %1$s'] = 'new %1$s';
		
		/*
		 * Used to indicate an object was deleted
		 * English: Deleted %1$s
		 * %1$s: the type of object
		 */
		$this->resources['deleted %1$s'] = 'deleted %1$s';
		
		/*
		 * Used to indicate a file type changed, including original
		 * and new file modes
		 * (when both original and new files are regular files)
		 * English: changed from %1$s to %2$s mode: %3$s -> %4$s
		 * %1$s: the original file type
		 * %2$s: the new file type
		 * %3$s: the original file mode
		 * %4$s: the new file mode
		 */
		$this->resources['changed from %1$s to %2$s mode: %3$s -> %4$s'] = 'changed from %1$s to %2$s mode: %3$s -> %4$s';
		
		/*
		 * Used to indicate a file type changed, with only new file mode
		 * (when old file type wasn't a normal file)
		 * English: changed from %1$s to %2$s mode: %3$s
		 * %1$s: the original file type
		 * %1$s: the new file type
		 * %3$s: the original file mode
		 * $4$s: the new file mode
		 */
		$this->resources['changed from %1$s to %2$s mode: %3$s'] = 'changed from %1$s to %2$s mode: %3$s';
		
		/*
		 * Used to indicate a file type changed
		 * (without any modes)
		 * English: changed from %1$s to %2$s
		 * %1$s: the original file type
		 * %2$s: the new file type
		 */
		$this->resources['changed from %1$s to %2$s'] = 'changed from %1$s to %2$s';
		
		/*
		 * Used to indicate a file mode changed
		 * (when both original and new modes are normal files)
		 * English: changed mode: %1$s -> %2$s
		 * %1$s: the original file mode
		 * %2$s: the new file mode
		 */
		$this->resources['changed mode: %1$s -> %2$s'] = 'changed mode: %1$s -> %2$s';
		
		/*
		 * Used to indicate a file mode changed
		 * (when only the new mode is a normal file)
		 * English: changed mode: %1$s
		 * %1$s: the new file mode
		 */
		$this->resources['changed mode: %1$s'] = 'changed mode: %1$s';
		
		/*
		 * Used to indicate a file mode changed, but neither the original
		 * nor new file modes are normal file modes
		 * (we don't have any more information than the fact that it changed)
		 * English: changed
		 */
		$this->resources['changed'] = 'changed';
		
		/*
		 * Used to indicate a file was moved and the file mode changed
		 * English: moved from %1$s with %2$d%% similarity, mode: %3$s
		 * %1$s: the old file
		 * %2$d: the similarity as a percent number
		 * %3$s: the new file mode
		 */
		$this->resources['moved from %1$s with %2$d%% similarity, mode: %3$s'] = 'moved from %1$s with %2$d%% similarity, mode: %3$s';
		
		/*
		 * Used to indicate a file was moved
		 * English: moved from %1$s with %2$d%% similarity
		 * %1$s: the old file
		 * %2$d: the similarity as a percent number
		 */
		$this->resources['moved from %1$s with %2$d%% similarity'] = 'moved from %1$s with %2$d%% similarity';
		
		/*
		 * Used as an alternate text on javascript "loading" images
		 * English: Loading...
		 */
		$this->resources['Loading...'] = 'Loading...';
		
		/*
		 * Used as a loading message while blame data is being pulled
		 * from the server
		 * English: Loading blame data...
		 */
		$this->resources['Loading blame data...'] = 'Loading blame data...';
		
		
		/*
		 * Age strings
		 *
		 * These are used when formatting the age
		 * (how long ago a project was modified) into
		 * a human readable string
		 */
		
		/*
		 * Used to represent an age in years
		 * English: %1$d years ago
		 * %1$d: the number of years
		 */
		$this->resources['%1$d years ago'] = '%1$d years ago';
		
		/*
		 * Used to represent an age in months
		 * English: %1$d months ago
		 * %1$d: the number of months
		 */
		$this->resources['%1$d months ago'] = '%1$d months ago';
		
		/*
		 * Used to represent an age in weeks
		 * English: %1$d weeks ago
		 * %1$d: the number of weeks
		 */
		$this->resources['%1$d weeks ago'] = '%1$d weeks ago';
		
		/*
		 * Used to represent an age in days
		 * English: %1$d days ago
		 * %1$d: the number of days
		 */
		$this->resources['%1$d days ago'] = '%1$d days ago';
		
		/*
		 * Used to represent an age in hours
		 * English: %1$d hours ago
		 * %1$d: the number of hours
		 */
		$this->resources['%1$d hours ago'] = '%1$d hours ago';
		
		/*
		 * Used to represent an age in minutes
		 * English: %1$d min ago
		 * %1$d: the number of minutes
		 */
		$this->resources['%1$d min ago'] = '%1$d min ago';
		
		/*
		 * Used to represent an age in seconds
		 * English: %1$d sec ago
		 * %1$d: the number of seconds
		 */
		$this->resources['%1$d sec ago'] = '%1$d sec ago';
		
		/*
		 * Used to represent a modification time of right now
		 * English: right now
		 */
		$this->resources['right now'] = 'right now';


		/*
		 * Project list headers
		 *
		 * These are used as the headers for columns on the
		 * home project list view
		 */
		 
		/*
		 * Used as the header for the project name column
		 * English: Project
		 */
		$this->resources['Project_Header'] = 'Project';
		
		/*
		 * Used as the header for the project description column
		 * English: Description
		 */
		$this->resources['Description_Header'] = 'Description';
		
		/*
		 * Used as the header for the column showing the person
		 * that owns the project
		 * English: Owner
		 */
		$this->resources['Owner_Header'] = 'Owner';
		
		/*
		 * Used as the header for the last change column
		 * (how long ago was the last commit)
		 * English: Last Change
		 */
		$this->resources['Last Change_Header'] = 'Last Change';
		
		/*
		 * Used as the header for the actions column, which is
		 * a list of links users can use to jump to various parts
		 * of this project
		 * English: Actions
		 */
		$this->resources['Actions_Header'] = 'Actions';
		
		
		/*
		 * Error messages
		 */
		
		/*
		 * Error message when user tries to do an action that requires a project
		 * but a project isn't specified
		 * English: Project is required
		 */
		$this->resources['Project is required'] = 'Project is required';
		
		/*
		 * Error message when user tries to access a project that doesn't exist
		 * English: Invalid project %1$s
		 * %1$s: the project the user tried to access
		 */
		$this->resources['Invalid project %1$s'] = 'Invalid project %1$s';
		
		/*
		 * Error message when a user tries to search but searching has been disabled
		 * in the config
		 * English: Search has been disabled
		 */
		$this->resources['Search has been disabled'] = 'Search has been disabled';
		
		/*
		 * Error message when a user tries to do a file search but searching files
		 * has been disabled in the config
		 * English: File search has been disabled
		 */
		$this->resources['File search has been disabled'] = 'File search has been disabled';
		
		/*
		 * Error message when a user's search query is too short
		 * English: You must enter search text of at least %1$d characters
		 * %1$d: the minimum number of characters
		 */
		$this->resources['You must enter search text of at least %1$d characters'] = 'You must enter search text of at least %1$d characters';
		
		/*
		 * Error message when a user's search didn't produce any results
		 * English: No matches for "%1$s"
		 * %1$s: the user's search string
		 */
		$this->resources['No matches for "%1$s"'] = 'No matches for "%1$s"';
		
		/*
		 * Error message when user tries to specify a file with a list of the projects,
		 * but it isn't a file
		 * English: %1$s is not a file
		 * %1$s: the path the user specified
		 */
		$this->resources['%1$s is not a file'] = '%1$s is not a file';
		
		/*
		 * Error message when user tries to specify a file with a list of the projects,
		 * but the system can't read the file
		 * English: Failed to open project list file %1$s
		 * %1$s: the file the user specified
		 */
		$this->resources['Failed to open project list file %1$s'] = 'Failed to open project list file %1$s';
		
		/*
		 * Error message when user specifies a path for a project root or project,
		 * but the path given isn't a directory
		 * English: %1$s is not a directory
		 * %1$s: the path the user specified
		 */
		$this->resources['%1$s is not a directory'] = '%1$s is not a directory';
		
		/*
		 * Error message when a hash specified in a URL isn't a valid git hash
		 * English: Invalid hash %1$s
		 * %1$s: the hash entered
		 */
		$this->resources['Invalid hash %1$s'] = 'Invalid hash %1$s';
		
		/*
		 * Error message when a temporary directory isn't specified in the config
		 * English: No temp dir defined
		 */
		$this->resources['No tmpdir defined'] = 'No tmpdir defined';
		
		/*
		 * Error message when the system attempts to create the temporary directory but can't
		 * English: Could not create tmpdir %1$s
		 * %1$s: the temp dir it's trying to create
		 */
		$this->resources['Could not create tmpdir %1$s'] = 'Could not create tmpdir %1$s';
		
		/*
		 * Error message when the temporary directory specified isn't a directory
		 * English: Specified tmpdir %1$s is not a directory
		 * %1$s: the temp dir specified
		 */
		$this->resources['Specified tmpdir %1$s is not a directory'] = 'Specified tmpdir %1$s is not a directory';
		
		/*
		 * Error message when the system can't write to the temporary directory
		 * English: Specified tmpdir %1$s is not writeable
		 * %1$s: the temp dir specified
		 */
		$this->resources['Specified tmpdir %1$s is not writeable'] = 'Specified tmpdir %1$s is not writeable';
		
		/*
		 * Error message when a path specified in the config is not a git repository
		 * English: %1$s is not a git repository
		 * %1$s: the specified path
		 */
		$this->resources['%1$s is not a git repository'] = '%1$s is not a git repository';
		
		/*
		 * Error message when a path specified is using '..' to break out of the
		 * project root (a hack attempt)
		 * English: %1$s is attempting directory traversal
		 * %1$s: The specified path
		 */
		$this->resources['%1$s is attempting directory traversal'] = '%1$s is attempting directory traversal';
		
		/*
		 * Error message when a path specified is outside of the project root
		 * English: %1$s is outside of the projectroot
		 * %1$s: The specified path
		 */
		$this->resources['%1$s is outside of the projectroot'] = '%1$s is outside of the projectroot';
		
		/*
		 * Error message when the user enters an unsupported search type
		 * English: Invalid search type
		 */
		$this->resources['Invalid search type'] = 'Invalid search type';
	}

}
