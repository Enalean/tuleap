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
		 * Used as a link to a plaintext version of a page
		 * English: plain
		 */
		$this->resources['plain'] = '{plain••}';
		
		/*
		 * Used as a link to the first page in a list of search results
		 * English: first
		 */
		$this->resources['first'] = '{fĩrst••}';
		
		/*
		 * Used as a link to the next page in a list of results
		 * English: next
		 */
		$this->resources['next'] = '{nėxt•}';
		
		/*
		 * Used as a link to the previous page in a list of results
		 * English: prev
		 */
		$this->resources['prev'] = '{prēv•}';
		
		/*
		 * Used as a link to the HEAD of a project for a log or file
		 * (note: HEAD is standard git terminology)
		 * English: HEAD
		 */
		$this->resources['HEAD'] = '{HĒĂD•}';
		
		/*
		 * Used when diffing a file, to indicate that it's a new file
		 * English: (new)
		 */
		$this->resources['(new)'] = '{(nęw)•}';
		
		/*
		 * Used when diffing a file, to indicate that it's been deleted
		 * English: (deleted)
		 */
		$this->resources['(deleted)'] = '{(dělȇtȩd)•••}';
		
		
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
		 * Used to indicate a file type changed, including original
		 * and new file modes
		 * (when both original and new files are regular files)
		 * English: changed from %1$s to %2$s mode: %3$s -> %4$s
		 * %1$s: the original file type
		 * %2$s: the new file type
		 * %3$s: the original file mode
		 * %4$s: the new file mode
		 */
		$this->resources['changed from %1$s to %2$s mode: %3$s -> %4$s'] = '{tō *%2$s* frŏm *%1$s* mōde *%3$s* -> *%4$s*•••••}';
		
		/*
		 * Used to indicate a file type changed, with only new file mode
		 * (when old file type wasn't a normal file)
		 * English: changed from %1$s to %2$s mode: %3$s
		 * %1$s: the original file type
		 * %1$s: the new file type
		 * %3$s: the original file mode
		 * $4$s: the new file mode
		 */
		$this->resources['changed from %1$s to %2$s mode: %3$s'] = '{tō *%2$s* frŏm *%1$s* mōde: *%3$s*•••••}';
		
		/*
		 * Used to indicate a file type changed
		 * (without any modes)
		 * English: changed from %1$s to %2$s
		 * %1$s: the original file type
		 * %2$s: the new file type
		 */
		$this->resources['changed from %1$s to %2$s'] = '{tŏ *%2$s* frōm *%1$s*••••}';
		
		/*
		 * Used to indicate a file mode changed
		 * (when both original and new modes are normal files)
		 * English: changed mode: %1$s -> %2$s
		 * %1$s: the original file mode
		 * %2$s: the new file mode
		 */
		$this->resources['changed mode: %1$s -> %2$s'] = '{mōdę chǡngē *%1$s* -> *%2$s*••••}';
		
		/*
		 * Used to indicate a file mode changed
		 * (when only the new mode is a normal file)
		 * English: changed mode: %1$s
		 * %1$s: the new file mode
		 */
		$this->resources['changed mode: %1$s'] = '{mōdė chăngē *%1$s*•••}';
		
		/*
		 * Used to indicate a file mode changed, but neither the original
		 * nor new file modes are normal file modes
		 * (we don't have any more information than the fact that it changed)
		 * English: changed
		 */
		$this->resources['changed'] = '{chăngėd••}';
		
		/*
		 * Used to indicate a file was moved and the file mode changed
		 * English: moved from %1$s with %2$d%% similarity, mode: %3$s
		 * %1$s: the old file
		 * %2$d: the similarity as a percent number
		 * %3$s: the new file mode
		 */
		$this->resources['moved from %1$s with %2$d%% similarity, mode: %3$s'] = '{wąs *%1$s* wĩth *%2$d*%% sīmĭlārĩty mōdę: *%3$s*•••••••}';
		
		/*
		 * Used to indicate a file was moved
		 * English: moved from %1$s with %2$d%% similarity
		 * %1$s: the old file
		 * %2$d: the similarity as a percent number
		 */
		$this->resources['moved from %1$s with %2$d%% similarity'] = '{wās *%1$s* wĩth *%2$d*%% sımįlārīty•••••••}';
		
		
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
		
		
		/*
		 * Error messages
		 */
		
		/*
		 * Error message when user tries to do an action that requires a project
		 * but a project isn't specified
		 * English: Project is required
		 */
		$this->resources['Project is required'] = '{Prōjĕct is rēquĩrěd••••}';
		
		/*
		 * Error message when user tries to access a project that doesn't exist
		 * English: Invalid project %1$s
		 * %1$s: the project the user tried to access
		 */
		$this->resources['Invalid project %1$s'] = '{İnvălıd prōjĕct *%1$s*•••}';
		
		/*
		 * Error message when a user tries to search but searching has been disabled
		 * in the config
		 * English: Search has been disabled
		 */
		$this->resources['Search has been disabled'] = '{Sęārch hăs bėęn dĭsăblėd••••}';
		
		/*
		 * Error message when a user tries to do a file search but searching files
		 * has been disabled in the config
		 * English: File search has been disabled
		 */
		$this->resources['File search has been disabled'] = '{Fīlĕ sēărch hąs bėęn dĭsąblēd•••••}';
		
		/*
		 * Error message when a user's search query is too short (less than 2 characters)
		 * English: You must enter search text of at least 2 characters
		 */
		$this->resources['You must enter search text of at least 2 characters'] = '{Yōu mũst ēntĕr sėärch tęxt ōf åt lēäst 2 chårãctĕrs••••••••••••}';
		
		/*
		 * Error message when a user's search didn't produce any results
		 * English: No matches for "%1$s"
		 * %1$s: the user's search string
		 */
		$this->resources['No matches for "%1$s"'] = '{Nō mątchēs fōr "*%1$s*"•••••••}';
		
		/*
		 * Error message when user tries to specify a file with a list of the projects,
		 * but it isn't a file
		 * English: %1$s is not a file
		 * %1$s: the path the user specified
		 */
		$this->resources['%1$s is not a file'] = '{*%1$s* ĩs nōt ă fįlě••••••}';
		
		/*
		 * Error message when user tries to specify a file with a list of the projects,
		 * but the system can't read the file
		 * English: Failed to open project list file %1$s
		 * %1$s: the file the user specified
		 */
		$this->resources['Failed to open project list file %1$s'] = '{Făılĕd tō ŏpėn prōjĕct lĩst fĭle *%1$s*•••••••}';
		
		/*
		 * Error message when user specifies a path for a project root or project,
		 * but the path given isn't a directory
		 * English: %1$s is not a directory
		 * %1$s: the path the user specified
		 */
		$this->resources['%1$s is not a directory'] = '{*%1$s* ĭs nōt ă dīrěctōry••••••}';
		
		/*
		 * Error message when a hash specified in a URL isn't a valid git hash
		 * English: Invalid hash %1$s
		 * %1$s: the hash entered
		 */
		$this->resources['Invalid hash %1$s'] = '{Ĭnvălıd hāsh *%1$s*•••••}';
		
		/*
		 * Error message when a temporary directory isn't specified in the config
		 * English: No temp dir defined
		 */
		$this->resources['No tmpdir defined'] = '{Nō tmpdĩr dēfĩněd••••••}';
		
		/*
		 * Error message when the system attempts to create the temporary directory but can't
		 * English: Could not create tmpdir %1$s
		 * %1$s: the temp dir it's trying to create
		 */
		$this->resources['Could not create tmpdir %1$s'] = '{Cōūld nōt crĕatę tmpdĭr *%1$s*••••}';
		
		/*
		 * Error message when the temporary directory specified isn't a directory
		 * English: Specified tmpdir %1$s is not a directory
		 * %1$s: the temp dir specified
		 */
		$this->resources['Specified tmpdir %1$s is not a directory'] = '{Spĕcĩfīėd tmpdır *%1$s* ĭs nōt ã dīréctōry•••••}';
		
		/*
		 * Error message when the system can't write to the temporary directory
		 * English: Specified tmpdir %1$s is not writeable
		 * %1$s: the temp dir specified
		 */
		$this->resources['Specified tmpdir %1$s is not writeable'] = '{Spĕcĭfīęd tmpdır %1$s īs nōt wrĩtėáblé•••••}';
		
		/*
		 * Error message when a path specified in the config is not a git repository
		 * English: %1$s is not a git repository
		 * %1$s: the specified path
		 */
		$this->resources['%1$s is not a git repository'] = '{*%1$s* ís nøt ā gīt rępōsıtōry•••••}';
		
		/*
		 * Error message when a path specified is using '..' to break out of the
		 * project root (a hack attempt)
		 * English: %1$s is attempting directory traversal
		 * %1$s: The specified path
		 */
		$this->resources['%1$s is attempting directory traversal'] = '{*%1$s* ıs āttěmptĭng dīrėctöry trãvêrsál•••••}';
		
		/*
		 * Error message when a path specified is outside of the project root
		 * English: %1$s is outside of the projectroot
		 * %1$s: The specified path
		 */
		$this->resources['%1$s is outside of the projectroot'] = '{*%1$s* ìs ōūtsıde ōf thĕ prōjēctrōŏt•••••}';
		
		/*
		 * Error message when the user enters an unsupported search type
		 * English: Invalid search type
		 */
		$this->resources['Invalid search type'] = '{Invălĩd sėąrch typė••••••}';
	}
	
	/*
	 * GetResource
	 *
	 * Overridde GetResource to gibberize resources
	 *
	 * @access public
	 * @param string $resource resource to fetch
	 * @param string $domain domain of string (for multiple translations of the same string)
	 * @return string resource
	 */
//	public function GetResource($resource, $domain = '')
//	{
//		return $this->Gibberize($resource);
//	}
	
	/*
	 * characterMap
	 *
	 * Maps regular characters to gibberish characters
	 *
	 * @access private
	 */
	private $characterMap = array(
		'a' => array('à', 'á', 'â', 'ã', 'ä', 'å', 'ā', 'ă', 'ą', 'ǎ', 'ǟ', 'ǡ', 'ǻ', 'ȁ', 'ȃ', 'ȧ', 'ḁ', 'ạ', 'ả', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ'),
		'b' => array('ḃ', 'ḅ', 'ḇ'),
		'c' => array('ç', 'ć', 'ĉ', 'ċ', 'č', 'ḉ'),
		'd' => array('ḋ', 'ḍ', 'ḏ', 'ḑ', 'ḓ'),
		'e' => array('è', 'é', 'ê', 'ë', 'ē', 'ĕ', 'ė', 'ę', 'ě', 'ȅ', 'ȇ', 'ȩ', 'ḕ', 'ḗ', 'ḙ', 'ḛ', 'ḝ', 'ẹ', 'ẻ', 'ẽ', 'ế', 'ề', 'ể', 'ễ', 'ệ'),
		'f' => array('ḟ'),
		'g' => array('ĝ', 'ğ', 'ġ', 'ģ', 'ǧ', 'ǵ', 'ḡ'),
		'h' => array('ĥ', 'ȟ', 'ḣ', 'ḥ', 'ḧ', 'ḩ', 'ḫ', 'ẖ'),
		'i' => array('ì', 'í', 'î', 'ï', 'ĩ', 'ī', 'ĭ', 'į', 'ı', 'ǐ', 'ȉ', 'ȋ', 'ḭ', 'ḯ', 'ỉ', 'ị'),
		'j' => array('ĵ', 'ǰ'),
		'k' => array('ǩ', 'ḱ', 'ḳ'),
		'l' => array('ĺ', 'ļ', 'ľ', 'ḷ', 'ḹ', 'ḻ', 'ḽ'),
		'm' => array('ḿ', 'ṁ', 'ṃ'),
		'n' => array('ñ', 'ń', 'ņ', 'ň', 'ǹ', 'ṅ', 'ṇ', 'ṉ', 'ṋ'),
		'o' => array('ò', 'ó', 'ô', 'õ', 'ö', 'ō', 'ŏ', 'ő', 'ơ', 'ǒ', 'ǫ', 'ǭ', 'ȍ', 'ȏ', 'ȫ', 'ȭ', 'ȯ', 'ȱ', 'ṍ', 'ṏ', 'ṑ', 'ṓ', 'ọ', 'ỏ', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ'),
		'p' => array('ṕ', 'ṗ'),
		//'q' => array(),
		'r' => array('ŕ', 'ŗ', 'ř', 'ȑ', 'ȓ', 'ṙ', 'ṛ', 'ṝ', 'ṟ'),
		's' => array('ś', 'ŝ', 'ş', 'š', 'ș', 'ṡ', 'ṣ', 'ṥ', 'ṧ', 'ṩ'),
		't' => array('ţ', 'ť', 'ț', 'ṭ', 'ṯ', 'ṱ', 'ẗ'),
		'u' => array('ù', 'ú', 'û', 'ü', 'ũ', 'ū', 'ŭ', 'ů', 'ű', 'ų', 'ư', 'ǔ', 'ǖ', 'ǘ', 'ǚ','ǜ', 'ȕ', 'ȗ', 'ṳ', 'ṵ', 'ṷ', 'ṹ', 'ṻ', 'ụ', 'ủ', 'ứ', 'ừ', 'ử', 'ữ', 'ự'),
		'v' => array('ṽ', 'ṿ'),
		'w' => array('ŵ', 'ẁ', 'ẃ', 'ẅ', 'ẇ', 'ẉ', 'ẘ'),
		'x' => array('ẋ', 'ẍ'),
		'y' => array('ý', 'ÿ', 'ŷ', 'ȳ', 'ẏ', 'ẙ', 'ỳ', 'ỵ'),
		'z' => array('ź', 'ż', 'ž', 'ẑ', 'ẓ', 'ẕ'),
		'A' => array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ā', 'Ă', 'Ą', 'Ǎ', 'Ǟ', 'Ǡ', 'Ǻ', 'Ȁ', 'Ȃ', 'Ȧ', 'Ḁ', 'Ạ', 'Ả', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Å'),
		'B' => array('Ḃ', 'Ḅ', 'Ḇ'),
		'C' => array('Ç', 'Ć', 'Ĉ', 'Ċ', 'Č', 'Ḉ'),
		'D' => array('Ď', 'Ḋ', 'Ḍ', 'Ḏ', 'Ḑ', 'Ḓ'),
		'E' => array('È', 'É', 'Ê', 'Ë', 'Ē', 'Ĕ', 'Ė', 'Ę', 'Ě', 'Ȅ', 'Ȇ', 'Ȩ', 'Ḕ', 'Ḗ', 'Ḙ', 'Ḛ', 'Ḝ', 'Ẹ', 'Ẻ', 'Ẽ', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ'),
		'F' => array('Ḟ'),
		'G' => array('Ĝ', 'Ğ', 'Ġ', 'Ģ', 'Ǧ', 'Ǵ', 'Ḡ'),
		'H' => array('Ĥ', 'Ȟ', 'Ḣ', 'Ḥ', 'Ḧ', 'Ḩ', 'Ḫ'),
		'I' => array('Ì', 'Í', 'Î', 'Ï', 'Ĩ', 'Ī', 'Ĭ', 'Į', 'İ', 'Ǐ', 'Ȉ', 'Ȋ', 'Ḯ', 'Ỉ', 'Ị'),
		'J' => array('Ĵ'),
		'K' => array('Ķ', 'Ǩ', 'Ḱ', 'Ḳ', 'Ḵ'),
		'L' => array('Ĺ', 'Ļ', 'Ľ', 'Ḷ', 'Ḹ', 'Ḻ', 'Ḽ'),
		'M' => array('Ḿ', 'Ṁ', 'Ṃ'),
		'N' => array('Ñ', 'Ń', 'Ņ', 'Ň', 'Ǹ', 'Ṅ', 'Ṇ', 'Ṉ', 'Ṋ'),
		'O' => array('Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ō', 'Ŏ', 'Ő', 'Ơ', 'Ǒ', 'Ǫ', 'Ǭ', 'Ȍ', 'Ȏ', 'Ȫ', 'Ȭ', 'Ȯ', 'Ȱ', 'Ṍ', 'Ṏ', 'Ỗ', 'Ổ', 'Ồ', 'Ố', 'Ỏ', 'Ọ', 'Ṓ', 'Ṑ', 'Ộ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ'),
		'P' => array('Ṕ', 'Ṗ'),
		//'Q' => array(),
		'R' => array('Ŕ', 'Ŗ', 'Ř', 'Ȑ', 'Ȓ', 'Ṙ', 'Ṛ', 'Ṝ', 'Ṟ'),
		'S' => array('Ś', 'Ŝ', 'Ş', 'Š', 'Ș', 'Ṡ', 'Ṣ', 'Ṥ', 'Ṧ', 'Ṩ'),
		'T' => array('Ţ', 'Ť', 'Ț', 'Ṫ', 'Ṭ', 'Ṯ', 'Ṱ'),
		'U' => array('Ù', 'Ú', 'Û', 'Ü', 'Ũ', 'Ū', 'Ŭ', 'Ů', 'Ű', 'Ų', 'Ư', 'Ǔ', 'Ǖ', 'Ǘ', 'Ǚ', 'Ǜ', 'Ȕ', 'Ȗ', 'Ṳ', 'Ṵ', 'Ṷ', 'Ṹ', 'Ṻ', 'Ụ', 'Ủ', 'Ứ', 'Ừ', 'Ử', 'Ữ'),
		'V' => array('Ṽ', 'Ṿ'),
		'W' => array('Ŵ', 'Ẁ', 'Ẃ', 'Ẅ', 'Ẇ', 'Ẉ'),
		'X' => array('Ẋ', 'Ẍ'),
		'Y' => array('Ý', 'Ŷ', 'Ÿ', 'Ȳ', 'Ẏ', 'Ỳ', 'Ỵ', 'Ỷ', 'Ỹ'),
		'Z' => array('Ź', 'Ż', 'Ž', 'Ẑ', 'Ẓ', 'Ẕ')
	);
	
	/*
	 * Gibberize
	 *
	 * Translates string into readable gibberish for testing i18n
	 *
	 * @access private
	 * @param string $resource string to gibberize
	 * @return string gibberized string
	 */
	private function Gibberize($resource)
	{
		if (empty($resource))
			return '';
			
		$len = strlen($resource);
		
		/*
		 * Wrap tokens so you can see where inserted content is
		 */
		$newstr = preg_replace('/(\%[1-9]+\$[a-z])/', '*\1*', $resource);
		
		/*
		 * Replace characters with readable characters from beyond standard ascii
		 */
		foreach ($this->characterMap as $letter => $replacements) {
			$replacements[] = $letter;
			$newstr = preg_replace('/([^\$]|^)' . $letter . '/', '\1' . $replacements[array_rand($replacements)], $newstr);
		}
		
		/*
		 * Add translation padding, since some languages translate the same
		 * sentence using more characters - 30% is about average
		 */
		$newstr .= str_repeat('•', (int)($len * 0.3));
		
		/*
		 * Wrap string in braces to catch sentence construction
		 */
		$newstr = '{' . $newstr . '}';
		
		return $newstr;
	}

}
