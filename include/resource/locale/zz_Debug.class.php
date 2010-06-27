<?php
/**
 * GitPHP Resource zz_Debug
 *
 * zz_Debug Test Resource Provider
 *
 * @author Christopher Han
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Resource
 */

/**
 * zz_Debug Resource
 *
 * @package GitPHP
 * @subpackage Resource
 */
class GitPHP_Resource_zz_Debug extends GitPHP_ResourceBase
{
	/**
	 * __construct
	 *
	 * Initializes resources
	 *
	 * @access public
	 */
	public function __construct()
	{
		/*
		 * Link back to the list of projects
		 * English: projects
		 */
		$this->resources['projects'] = '{pŕŏjēĉts•••}';
		
		/*
		 * Used as title for and link to project summary page
		 * English: summary
		 */
		$this->resources['summary'] = '{sȕmmǡry••}';
		
		/*
		 * Used as title for and link to the compact log view
		 * with one line abbreviated commits
		 * English: shortlog
		 */
		$this->resources['shortlog'] = '{shǫrtlǭg•••}';
		
		/*
		 * Used as title for and link to log view with full
		 * commit messages
		 * English: log
		 */
		$this->resources['log'] = '{lǿg•}';
		
		/*
		 * Used as title for and link to a list of files
		 * in a directory, which git calls a 'tree'
		 * English: tree
		 */
		$this->resources['tree'] = '{trëé•}';
		
		/*
		 * Used as link to download a copy of the files
		 * in a given commit
		 * English: snapshot
		 */
		$this->resources['snapshot'] = '{snápshŏt•••}';
		
		/*
		 * Used as title for search page, and also is the
		 * label for the search box
		 * English: search
		 */
		$this->resources['search'] = '{sėąrch••}';
		
		/*
		 * Used as link to and title for the full diff of
		 * all the changes in a commit
		 * English: commitdiff
		 */
		$this->resources['commitdiff'] = '{cŏmmĭtdĩff••••}';
		
		/*
		 * Used as link to and title for a diff of a single file
		 * English: blobdiff
		 */
		$this->resources['blobdiff'] = '{blōbdīff•••}';
		
		/*
		 * Used as link to and title for the file history, which
		 * displays all commits that have modified a certain file
		 * English: history
		 */
		$this->resources['history'] = '{hįstőry••}';
		
		/*
		 * Used as link to and title for page displaying detailed
		 * info about a tag
		 * English: tag
		 */
		$this->resources['tag'] = '{tàg•}';
		
		/*
		 * Used as link to and title for page showing all tags
		 * in a project
		 * English: tags
		 */
		$this->resources['tags'] = '{tãgs•}';
		
		/*
		 * Used as link to and title for page showing all heads
		 * in a project
		 * English: heads
		 */
		$this->resources['heads'] = '{hêåds•}';
		
		/*
		 * Used as link to and title for page displaying blame info
		 * (who last touched what line) in a file
		 * English: blame
		 */
		$this->resources['blame'] = '{bläme•}';
		
		/*
		 * Used as link to and title for page displaying a blob,
		 * which is what git calls a single file
		 * English: blob
		 */
		$this->resources['blob'] = '{blōb•}';
		
		/*
		 * Used as link to and title for page displaying info about
		 * a single commit in the project
		 * English: commit
		 */
		$this->resources['commit'] = '{cŏmmıt••}';
		
		/*
		 * Used as link to diff this file version with the previous version
		 * English: diff
		 */
		$this->resources['diff'] = '{dĩff•}';
		
		/*
		 * Used as link to diff this file version with the current file
		 * English: diff to current
		 */
		$this->resources['diff to current'] = '{dĭff tō cũrrēnt•••••}';
		
		
		/*
		 * Used to label the project description
		 * English: description
		 */
		$this->resources['description'] = '{dėscrĩptįon••••}';
		
		/*
		 * Used to label the project owner
		 * English: owner
		 */
		$this->resources['owner'] = '{ōwnęr•}';
		
		/*
		 * Used to label the time the project was last changed
		 * (the time of the most recent commit)
		 * English: last change
		 */
		$this->resources['last change'] = '{låst chängë••••}';
		
		/*
		 * Used to label the url that users can use to clone the
		 * project
		 * English: clone url
		 */
		$this->resources['clone url'] = '{clône ũrl•••}';
		
		/*
		 * Used to label the url that users can use to push commits
		 * to the project
		 * English: push url
		 */
		$this->resources['push url'] = '{půsh ŭrl••}';
		
		/*
		 * Used to label the author of the commit, and as a field to search
		 * The author is the person who wrote the changes in the commit
		 * English: author
		 */
		$this->resources['author'] = '{åüthōr••}';
		
		/*
		 * Used to label the committer of the commit, and as a field to search
		 * The committer is the person who put the commit into this project
		 * English: committer
		 */
		$this->resources['committer'] = '{cōmmĭttęr•••}';
		
		/*
		 * Used to label the parent of this commit
		 * The parent is the commit preceding this one in the project history
		 * English: parent
		 */
		$this->resources['parent'] = '{păręnt••}';
		
		/*
		 * Used as a search type, to search the contents of files in the project
		 * English: file
		 */
		$this->resources['file'] = '{fìlé•}';
		
		
		/*
		 * Used to indicate the number of files changed in a commit
		 * Comes before a list of files
		 * English: %1$d files changed:
		 * %1$d: the number of files
		 */
		$this->resources['%1$d files changed:'] = '{chängëd *%1$d*••••}';
		
		/*
		 * Used to indicate a new object was added with an access mode
		 * English: new %1$s with mode %2$s
		 * %1$s: the type of object
		 * %2$s: the mode
		 */
		$this->resources['new %1$s with mode %2$s'] = '{*%2$s* nēw *%1$s*•••••}';
		
		/*
		 * Used to indicate a new object was added
		 * English: new %1$s
		 * %1$s: the type of object
		 */
		$this->resources['new %1$s'] = '{*%1$s* nēw•••}';
		
		/*
		 * Used to indicate an object was deleted
		 * English: Deleted %1$s
		 * %1$s: the type of object
		 */
		$this->resources['deleted %1$s'] = '{*%1$s* dĕlętēd•••}';
		
		
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
		$this->resources['%1$d years ago'] = '{yęărs: *%1$d*•••}';
		
		/*
		 * Used to represent an age in months
		 * English: %1$d months ago
		 * %1$d: the number of months
		 */
		$this->resources['%1$d months ago'] = '{mōnths: *%1$d*•••}';
		
		/*
		 * Used to represent an age in weeks
		 * English: %1$d weeks ago
		 * %1$d: the number of weeks
		 */
		$this->resources['%1$d weeks ago'] = '{wěēks: *%1$d*•••}';
		
		/*
		 * Used to represent an age in days
		 * English: %1$d days ago
		 * %1$d: the number of days
		 */
		$this->resources['%1$d days ago'] = '{dāys: *%1$d*••}';
		
		/*
		 * Used to represent an age in hours
		 * English: %1$d hours ago
		 * %1$d: the number of hours
		 */
		$this->resources['%1$d hours ago'] = '{hőůrs: *%1$d*•••}';
		
		/*
		 * Used to represent an age in minutes
		 * English: %1$d min ago
		 * %1$d: the number of minutes
		 */
		$this->resources['%1$d min ago'] = '{mĭnũtęs: *%1$d*•••}';
		
		/*
		 * Used to represent an age in seconds
		 * English: %1$d sec ago
		 * %1$d: the number of seconds
		 */
		$this->resources['%1$d sec ago'] = '{sĕcōnds: *%1$d*•••}';
		
		/*
		 * Used to represent a modification time of right now
		 * English: right now
		 */
		$this->resources['right now'] = '{thıs ĩnstąnt}';


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
		$this->resources['Project_Header'] = '{Prŏjěct••}';
		
		/*
		 * Used as the header for the project description column
		 * English: Description
		 */
		$this->resources['Description_Header'] = '{Dėscrīptĭŏn••••}';
		
		/*
		 * Used as the header for the column showing the person
		 * that owns the project
		 * English: Owner
		 */
		$this->resources['Owner_Header'] = '{Ŏwnēr••}';
		
		/*
		 * Used as the header for the last change column
		 * (how long ago was the last commit)
		 * English: Last Change
		 */
		$this->resources['Last Change_Header'] = '{Låst Chángè•••}';
		
		/*
		 * Used as the header for the actions column, which is
		 * a list of links users can use to jump to various parts
		 * of this project
		 * English: Actions
		 */
		$this->resources['Actions_Header'] = '{Åctĩøns••}';
	}

}
