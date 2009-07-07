<?php
/*
 *  zz_DEBUG.php
 *  gitphp: A PHP git repository browser
 *  Component: i18n - zz_DEBUG
 */

$strings = array(
	/*
	 * Error message that tells the user that
	 * there were no projects found in the projectroot
	 */
	'noprojectsfound' => '{Nő prōjěcts fŏůnd•••••}',

	/*
	 * Error message that tells the user that the projectroot
	 * they have set up is not a directory
	 */
	'projectrootisnotadirectory' => '{Prŏjĕctrőőt ĩs nōt ā dĩrėctōry•••••••••}',

	/*
	 * Error message that tells the user that they have not set
	 * a projectroot
	 */
	'noprojectrootset' => '{Nŏ prőjēctrōŏt sėt•••••}',

	/*
	 * Tells the user that all searching has been disabled
	 */
	'searchhasbeendisabled' => '{Séãrch hås bêën dîsáblëd•••••••}',

	/*
	 * Tells the user that searching with files has been disabled
	 */
	'filesearchhasbeendisabled' => '{Fïle sëärch hås béèn dïsåbléd••••••••}',

	/*
	 * Tells the user that their search string must be a minimum of 2 characters
	 */
	'searchtooshort' => '{Yōu můst ėntěr sēărch tĕxt ōf āt lėåst 2 chãräctérs•••••••••••••••}',

	/*
	 * Tells the user that no matches were found for their search, where
	 * %1$s is the search string
	 */
	'nomatches' => '{‹%1$s› mătĉh nōt fŏůnd•••••}',

	/*
	 * Used to indicate multiple projects - links back to the project
	 * listing page
	 */
	'projects' => '{prőjėčts••}',

	/*
	 * Used as the header for the project name column on the project list
	 */
	'headerproject' => '{Pröjèct••}',

	/*
	 * Used as the header for the description column on the project list
	 */
	'headerdescription' => '{Dèscrîptîön•••}',

	/*
	 * Used as the header for the owner column on the project list
	 */
	'headerowner' => '{Öwnèr•}',

	/*
	 * Used as the header for the age (last time project was changed) column in the
	 * project list
	 */
	'headerlastchange' => '{Låst Chångè•••}',

	/*
	 * Used as the header for the actions column (links to various other pages for
	 * that project)
	 */
	'headeractions' => '{Åctîöns••}',

	/*
	 * Used as the name for the main project summary page
	 */
	'summary' => '{sûmmäry••}',

	/*
	 * Used as the name for the shortlog (abbreviated commit log) page
	 */
	'shortlog' => '{shõrtlög••}',

	/*
	 * Used as the name for the log (full commit log) page
	 */
	'log' => '{lõg•}',

	/*
	 * Used as the name for a single "commit" to the repository - 
	 * for the commit page, as well as on other pages when referencing
	 * a single commit object
	 */
	'commit' => '{cömmït•}',

	/*
	 * Used as the name for the commitdiff page, which shows all changes
	 * in a single commit
	 */
	'commitdiff' => '{cõmmïtdîff•••}',

	/*
	 * Used as the name for the tree page and to reference tree objects - 
	 * "trees" being a particular project's directory of files (or subdirectory)
	 * at a given revision
	 */
	'tree' => '{tréè•}',

	/*
	 * Used as the name for the snapshot action, which sends a tarball of the
	 * project at a given revision
	 */
	'snapshot' => '{snãpshöt••}',

	/*
	 * Used as the name for the tags page/section, which lists all tags for a project
	 */
	'tags' => '{tágs•}',

	/*
	 * Used as the name for the single tag page, which shows all info on a single
	 * tag object
	 */
	'tag' => '{tàg•}',

	/*
	 * Used as the name for the heads page/section, which lists all heads for a project
	 */
	'heads' => '{hèáds•}',

	/*
	 * Used as the name for the history page, which lists all commits where a particular
	 * file was changed
	 */
	'history' => '{hîstöry••}',

	/*
	 * Used as the name for the blob page, which shows the content of a file at a given
	 * revision (its 'blob' data)
	 */
	'blob' => '{blôb•}',
	
	/*
	 * Used as a link to show the differences in a file for a commit
	 */
	'diff' => '{dîff•}',

	/*
	 * Used as a link to diff a particular revision of a file to the current version
	 */
	'difftocurrent' => '{dîff tò cürrênt••••}',

	/*
	 * Used as the name for the search action, and to caption the search box
	 */
	'search' => '{sêàrch••}',

	/*
	 * Used as the caption on the 'RSS' button, which gets a feed of the most recent
	 * commits to a project
	 */
	'RSS' => '{RSS•}',

	/*
	 * Used as the caption for the 'OPML' button, which gets a list of projects in OPML format
	 */
	'OPML' => '{ÖPML•}',

	/*
	 * Used as the caption for the 'TXT' button, which gets a list of projects in plaintext
	 */
	'TXT' => '{TXT•}',

	/*
	 * Used as a link on various actions (blob, blobdiff, commitdiff, etc) to get the plaintext
	 * version of it
	 */
	'plain' => '{plåïn•}',

    /*
     * Used as an indicator that something was added
     */
    'new' => '{ñêw•}',

	/*
	 * Used as an indicator that something was deleted
	 */
	'deleted' => '{délètëd••}',

	/*
	 * Used as an indicator that something is a file - for example, that we are diffing a
	 * file or searching within files
	 */
	'file' => '{fílĕ•}',

	/*
	 * Used to denote the author of a particular commit, or that we want to search
	 * authors
	 */
	'author' => '{åûthōr••}',

	/*
	 * Used to denote the committer of a particular commit, or that we want to search
	 * committers
	 */
	'committer' => '{cōmmĩttęr••}',

	/*
	 * Used as the link to the previous page, when paginating through log entries
	 * or search results
	 */
	'prev' => '{prěv•}',

	/*
	 * Used as the link to the next page, when paginating through log entries
	 * or search results
	 */
	'next' => '{ńėxt•}',

	/*
	 * Used as the link to the first page, when paginating through search results
	 */
	'first' => '{fĩrst•}',

	/*
	 * Used as the link to the HEAD, when paginating through log entries
	 */
	'HEAD' => '{HĚĂD•}',
	
	/*
         * Used to indicate the description of the project, on the project summary page
         */
        'description' => '{dêscrïptìõn•••}',

        /*
         * Used to indicate the owner of the project, on the project summary page
         */
        'owner' => '{öwnêr•}',

       /*
        * Used to indicate the age (last change) of the project, on the project summary page
        */
        'lastchange' => '{låst chãngë•••}',

	/*
	 * Used to indicate the object that is the parent of this one (eg the commit that
	 * came right before this one)
	 */
	'parent' => '{pārėnt••}',

	/*
	 * Used to indicate the object (commit, etc) that is attached to a tag
	 */
	'object' => '{őbjěct••}',

	/*
	 * Used to indicate a new object was added, where
	 * %1$s is the type of object (file or tree)
	 */
	'newobject' => '{‹%1$s› ñėw•}',

	/*
	 * Used to indicate a new object was added, where
	 * %1$s is the type of object (file or tree) and
	 * %2$s is the new mode
	 */
	'newobjectwithmode' => '{mõdę ‹%2$s› ñėw ‹%1$s›•••••}',

	/*
	 * Used to indicate an object was deleted, where
	 * %1$s is the type of object (file or tree)
	 */
	'deletedobject' => '{‹%1$s› dėlětē••}',
	
	/*
	 * Used to indicate an object's type was changed, where
	 * %1$s is the old type and %2$s is the new type
	 */
	'changedobjecttype' => '{‹%1$s› tö ‹%2$s› chăngěd•••••}',
	
	/*
	 * Used to indicate an object's mode was changed, where
	 * %1$s is the mode change (either just one mode or
	 * mode1->mode2)
	 */
    'changedobjectmode' => '{‹%1$s› mõdêchångè••••}',

	/*
	 * Used to indicate that an object was moved/renamed,
	 * where %1$s is the original name, ande
	 * %2$d is the percentage similarity,
	 */
	'movedobjectwithsimilarity' => '{%%‹%2$d› sĩmīląrĭtŷ, mővɛd frŏm ‹%1$s›•••••••••}',
	
    /*
     * Used to indicate that an object was moved/renamed,
     * where %1$s is the original name,
     * %2$d is the percentage similarity, and
     * %3$s is the mode
     */
    'movedobjectwithsimilaritymodechange' => '{%%‹%2$d› sĩmīląrĭtŷ, mővɛd frŏm ‹%1$s› mode ‹%3$s›•••••••••}',

	/*
	 * Used to indicate something happened a certain number of
	 * years ago, where %1$d is the number of years
	 */
	'ageyearsago' => '{ŷrs ‹%1$d› ăgő•••}',

	/*
	 * Used to indicate something happened a certain number of
	 * years ago, where %1$d is the number of years
	 */
	'agemonthsago' => '{mős ‹%1$d› ăgő•••}',

	/*
	 * Used to indicate something happened a certain number of
	 * weeks ago, where %1$d is the number of weeks
	 */
	'ageweeksago' => '{wks ‹%1$d› ăgő•••}',

	/*
	 * Used to indicate something happened a certain number of
	 * days ago, where %1$d is the number of days
	 */
	'agedaysago' => '{dŷs ‹%1$d› ăgő•••}',

	/*
	 * Used to indicate something happened a certain number of
	 * hours ago, where %1$d is the number of hours
	 */
	'agehoursago' => '{hrs ‹%1$d› ăgő•••}',

	/*
	 * Used to indicate something happened a certain number of
	 * minutes ago, where %1$d is the number of minutes
	 */
	'ageminago' => '{mñs ‹%1$d› ăgő••}',

	/*
	 * Used to indicate something happened a certain number of
	 * seconds ago, where %1$d is the number of seconds
	 */
	'agesecago' => '{scs ‹%1$d› ăgő••}',

	/*
	 * Used to indicate something is happening right now
	 * (less than 3 seconds ago)
	 */
	'agerightnow' => '{rĩght nøw•••}',
	
        /*
         * Used to indicate a certain number of files were changed in a commit
         * where %1$d is the numebr of files changed
         */
        'fileschanged' => '{chângëd ‹%1$d› fĭlēs••••}',

	/*
	 * Used on a plaintext diff to indicate who it's from
	 */
	'From' => '{Fröm•}',

	/*
	 * Used on a plaintext diff to indicate the date
	 */
	'Date' => '{Dātë•}',

	/*
	 * Used on a plaintext diff to indicate the subject
	 */
	'Subject' => '{Sübjêct••}',
);

?>
