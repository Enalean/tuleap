<?php
/**
 * GitPHP Project
 * 
 * Represents a single git project
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Commit.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Head.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Tag.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Pack.class.php');
require_once(GITPHP_GITOBJECTDIR . 'GitConfig.class.php');

define('GITPHP_ABBREV_HASH_MIN', 7);

/**
 * Project class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Project
{

/* internal variables {{{1*/

	/**
	 * projectRoot
	 *
	 * Stores the project root internally
	 *
	 * @access protected
	 */
	protected $projectRoot;

	/**
	 * project
	 *
	 * Stores the project internally
	 *
	 * @access protected
	 */
	protected $project;

/* owner internal variables {{{2*/

	/**
	 * owner
	 *
	 * Stores the owner internally
	 *
	 * @access protected
	 */
	protected $owner = "";

	/**
	 * ownerRead
	 *
	 * Stores whether the file owner has been read
	 *
	 * @access protected
	 */
	protected $ownerRead = false;

/*}}}2*/

/* description internal variables {{{2*/

	/**
	 * description
	 *
	 * Stores the description internally
	 *
	 * @access protected
	 */
	protected $description;

	/**
	 * readDescription
	 *
	 * Stores whether the description has been
	 * read from the file yet
	 *
	 * @access protected
	 */
	protected $readDescription = false;

/*}}}2*/

	/**
	 * category
	 *
	 * Stores the category internally
	 *
	 * @access protected
	 */
	protected $category = '';

/* epoch internal variables {{{2*/

	/**
	 * epoch
	 *
	 * Stores the project epoch internally
	 *
	 * @access protected
	 */
	protected $epoch;

	/**
	 * epochRead
	 *
	 * Stores whether the project epoch has been read yet
	 *
	 * @access protected
	 */
	protected $epochRead = false;

/*}}}2*/

/* HEAD internal variables {{{2*/

	/**
	 * head
	 *
	 * Stores the head hash internally
	 *
	 * @access protected
	 */
	protected $head;

	/**
	 * readHeadRef
	 *
	 * Stores whether the head ref has been read yet
	 *
	 * @access protected
	 */
	protected $readHeadRef = false;

/*}}}*/

/* ref internal variables {{{2*/

	/**
	 * tags
	 *
	 * Stores the tags for the project
	 *
	 * @access protected
	 */
	protected $tags = array();

	/**
	 * heads
	 *
	 * Stores the heads for the project
	 *
	 * @access protected
	 */
	protected $heads = array();

	/**
	 * readRefs
	 *
	 * Stores whether refs have been read yet
	 *
	 * @access protected
	 */
	protected $readRefs = false;

/*}}}2*/

/* url internal variables {{{2*/

	/**
	 * cloneUrl
	 *
	 * Stores the clone url internally
	 *
	 * @access protected
	 */
	protected $cloneUrl = null;

	/**
	 * pushUrl
	 *
	 * Stores the push url internally
	 *
	 * @access protected
	 */
	protected $pushUrl = null;

/*}}}2*/

/* bugtracker internal variables {{{2*/

	/**
	 * bugUrl
	 *
	 * Stores the bug url internally
	 *
	 * @access protected
	 */
	protected $bugUrl = null;

	/**
	 * bugPattern
	 *
	 * Stores the bug pattern internally
	 *
	 * @access protected
	 */
	protected $bugPattern = null;

/*}}}2*/

	/**
	 * website
	 *
	 * Stores the website url internally
	 *
	 * @access protected
	 */
	protected $website = null;

	/**
	 * commitCache
	 *
	 * Caches fetched commit objects in case of
	 * repeated requests for the same object
	 *
	 * @access protected
	 */
	protected $commitCache = array();

/* packfile internal variables {{{2*/

	/**
	 * packs
	 *
	 * Stores the list of packs
	 *
	 * @access protected
	 */
	protected $packs = array();

	/**
	 * packsRead
	 *
	 * Stores whether packs have been read
	 *
	 * @access protected
	 */
	protected $packsRead = false;

/*}}}2*/

	/**
	 * compat
	 *
	 * Stores whether this project is running
	 * in compatibility mode
	 *
	 * @access protected
	 */
	protected $compat = null;

	/**
	 * config
	 *
	 * Stores the config reader internally
	 *
	 * @access protected
	 */
	protected $config = null;

/*}}}1*/

/* class methods {{{1*/

	/**
	 * __construct
	 *
	 * Class constructor
	 *
	 * @access public
	 * @param string $projectRoot project root
	 * @param string $project project
	 * @throws Exception if project is invalid or outside of projectroot
	 */
	public function __construct($projectRoot, $project)
	{
		$this->projectRoot = GitPHP_Util::AddSlash($projectRoot);
		$this->SetProject($project);
	}

/*}}}1*/

/* accessors {{{1*/

/* project accessors {{{2*/

	/**
	 * GetProject
	 *
	 * Gets the project
	 *
	 * @access public
	 * @return string the project
	 */
	public function GetProject()
	{
		return $this->project;
	}

	/**
	 * SetProject
	 *
	 * Attempts to set the project
	 *
	 * @access private
	 * @throws Exception if project is invalid or outside of projectroot
	 */
	private function SetProject($project)
	{
		$realProjectRoot = realpath($this->projectRoot);
		$path = $this->projectRoot . $project;
		$fullPath = realpath($path);

		if (!is_dir($fullPath)) {
			throw new Exception(sprintf(__('%1$s is not a directory'), $project));
		}

		if (!is_file($fullPath . '/HEAD')) {
			throw new Exception(sprintf(__('%1$s is not a git repository'), $project));
		}

		if (preg_match('/(^|\/)\.{0,2}(\/|$)/', $project)) {
			throw new Exception(sprintf(__('%1$s is attempting directory traversal'), $project));
		}

		$pathPiece = substr($fullPath, 0, strlen($realProjectRoot));

		if ((!is_link($path)) && (strcmp($pathPiece, $realProjectRoot) !== 0)) {
			throw new Exception(sprintf(__('%1$s is outside of the projectroot'), $project));
		}

		$this->project = $project;

	}

/*}}}2*/

	/**
	 * GetSlug
	 *
	 * Gets the project as a filename/url friendly slug
	 *
	 * @access public
	 * @return string the slug
	 */
	public function GetSlug()
	{
		$project = $this->project;

		if (substr($project, -4) == '.git')
			$project = substr($project, 0, -4);
		
		return GitPHP_Util::MakeSlug($project);
	}

	/**
	 * GetPath
	 *
	 * Gets the full project path
	 *
	 * @access public
	 * @return string project path
	 */
	public function GetPath()
	{
		return $this->projectRoot . $this->project;
	}

/* owner accessors {{{2 */

	/**
	 * GetOwner
	 *
	 * Gets the project's owner
	 *
	 * @access public
	 * @return string project owner
	 */
	public function GetOwner()
	{
		if (empty($this->owner) && !$this->ownerRead) {
			$this->ReadOwner();
		}
	
		return $this->owner;
	}

	/**
	 * ReadOwner
	 *
	 * Reads the project owner
	 *
	 * @access protected
	 */
	protected function ReadOwner()
	{
		if ($this->GetConfig()->HasValue('gitphp.owner')) {
			$this->owner = $this->GetConfig()->GetValue('gitphp.owner');
		} else if ($this->GetConfig()->HasValue('gitweb.owner')) {
			$this->owner = $this->GetConfig()->GetValue('gitweb.owner');
		}

		if (empty($this->owner) && function_exists('posix_getpwuid')) {
			$uid = fileowner($this->GetPath());
			if ($uid !== false) {
				$data = posix_getpwuid($uid);
				if (isset($data['gecos']) && !empty($data['gecos'])) {
					$this->owner = $data['gecos'];
				} elseif (isset($data['name']) && !empty($data['name'])) {
					$this->owner = $data['name'];
				}
			}
		}

		$this->ownerRead = true;
	}

	/**
	 * SetOwner
	 *
	 * Sets the project's owner (from an external source)
	 *
	 * @access public
	 * @param string $owner the owner
	 */
	public function SetOwner($owner)
	{
		$this->owner = $owner;
	}

/*}}}2*/

/* projectroot accessors {{{2*/

	/**
	 * GetProjectRoot
	 *
	 * Gets the project root
	 *
	 * @access public
	 * @return string the project root
	 */
	public function GetProjectRoot()
	{
		return $this->projectRoot;
	}

/*}}}2*/

/* description accessors {{{2*/

	/**
	 * GetDescription
	 *
	 * Gets the project description
	 *
	 * @access public
	 * @param $trim length to trim description to (0 for no trim)
	 * @return string project description
	 */
	public function GetDescription($trim = 0)
	{
		if (!$this->readDescription) {
			if ($this->GetConfig()->HasValue('gitphp.description')) {
				$this->description = $this->GetConfig()->GetValue('gitphp.description');
			} else if (file_exists($this->GetPath() . '/description')) {
				$this->description = file_get_contents($this->GetPath() . '/description');
			}
			$this->readDescription = true;
		}
		
		if (($trim > 0) && (strlen($this->description) > $trim)) {
			return substr($this->description, 0, $trim) . '…';
		}

		return $this->description;
	}

	/**
	 * SetDescription
	 *
	 * Overrides the project description
	 *
	 * @access public
	 * @param string $descr description
	 */
	public function SetDescription($descr)
	{
		$this->description = $descr;
		$this->readDescription = true;
	}

/*}}}2*/

	/**
	 * GetDaemonEnabled
	 *
	 * Returns whether gitdaemon is allowed for this project
	 *
	 * @access public
	 * @return boolean git-daemon-export-ok?
	 */
	public function GetDaemonEnabled()
	{
		return file_exists($this->GetPath() . '/git-daemon-export-ok');
	}

/* category accessors {{{2*/

	/**
	 * GetCategory
	 *
	 * Gets the project's category
	 *
	 * @access public
	 * @return string category
	 */
	public function GetCategory()
	{
		if (!empty($this->category)) {
			return $this->category;
		}

		if ($this->GetConfig()->HasValue('gitphp.category')) {
			return $this->GetConfig()->GetValue('gitphp.category');
		}

		return '';
	}

	/**
	 * SetCategory
	 * 
	 * Sets the project's category
	 *
	 * @access public
	 * @param string $category category
	 */
	public function SetCategory($category)
	{
		$this->category = $category;
	}

/*}}}2*/

/* clone url accessors {{{2*/

	/**
	 * GetCloneUrl
	 *
	 * Gets the clone URL for this repository, if specified
	 *
	 * @access public
	 * @return string clone url
	 */
	public function GetCloneUrl()
	{
		if ($this->cloneUrl !== null)
			return $this->cloneUrl;

		if ($this->GetConfig()->HasValue('gitphp.cloneurl'))
			return $this->GetConfig()->GetValue('gitphp.cloneurl');

		$cloneurl = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('cloneurl', ''), false);
		if (!empty($cloneurl))
			$cloneurl .= $this->project;

		return $cloneurl;
	}

	/**
	 * SetCloneUrl
	 *
	 * Overrides the clone URL for this repository
	 *
	 * @access public
	 * @param string $cUrl clone url
	 */
	public function SetCloneUrl($cUrl)
	{
		$this->cloneUrl = $cUrl;
	}

/*}}}2*/

/* push url accessors {{{2*/

	/**
	 * GetPushUrl
	 *
	 * Gets the push URL for this repository, if specified
	 *
	 * @access public
	 * @return string push url
	 */
	public function GetPushUrl()
	{
		if ($this->pushUrl !== null)
			return $this->pushUrl;

		if ($this->GetConfig()->HasValue('gitphp.pushurl'))
			return $this->GetConfig()->GetValue('gitphp.pushurl');

		$pushurl = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('pushurl', ''), false);
		if (!empty($pushurl))
			$pushurl .= $this->project;

		return $pushurl;
	}

	/**
	 * SetPushUrl
	 *
	 * Overrides the push URL for this repository
	 *
	 * @access public
	 * @param string $pUrl push url
	 */
	public function SetPushUrl($pUrl)
	{
		$this->pushUrl = $pUrl;
	}

/*}}}2*/

/* bugtracker accessors {{{2*/

	/**
	 * GetBugUrl
	 *
	 * Gets the bug URL for this repository, if specified
	 *
	 * @access public
	 * @return string bug url
	 */
	public function GetBugUrl()
	{
		if ($this->bugUrl != null)
			return $this->bugUrl;

		if ($this->GetConfig()->HasValue('gitphp.bugurl')) {
			return $this->GetConfig()->GetValue('gitphp.bugurl');
		}

		return GitPHP_Config::GetInstance()->GetValue('bugurl', '');
	}

	/**
	 * SetBugUrl
	 *
	 * Overrides the bug URL for this repository
	 *
	 * @access public
	 * @param string $bUrl bug url
	 */
	public function SetBugUrl($bUrl)
	{
		$this->bugUrl = $bUrl;
	}

	/**
	 * GetBugPattern
	 *
	 * Gets the bug pattern for this repository, if specified
	 *
	 * @access public
	 * @return string bug pattern
	 */
	public function GetBugPattern()
	{
		if ($this->bugPattern != null)
			return $this->bugPattern;

		if ($this->GetConfig()->HasValue('gitphp.bugpattern')) {
			return $this->GetConfig()->GetValue('gitphp.bugpattern');
		}

		return GitPHP_Config::GetInstance()->GetValue('bugpattern', '');
	}

	/**
	 * SetBugPattern
	 *
	 * Overrides the bug pattern for this repository
	 *
	 * @access public
	 * @param string $bPat bug pattern
	 */
	public function SetBugPattern($bPat)
	{
		$this->bugPattern = $bPat;
	}

/*}}}2*/

/* website accessors {{{2*/

	/**
	 * GetWebsite
	 *
	 * Gets the website for this repository, if specified
	 *
	 * @access public
	 * @return string website
	 */
	public function GetWebsite()
	{
		if (!empty($this->website)) {
			return $this->website;
		}

		if ($this->GetConfig()->HasValue('gitphp.website')) {
			return $this->GetConfig()->GetValue('gitphp.website');
		}

		return null;
	}

	/**
	 * SetWebsite
	 *
	 * Sets the website for this repository
	 *
	 * @access public
	 * @param string $site website
	 */
	public function SetWebsite($site)
	{
		$this->website = $site;
	}

/*}}}2*/

/* HEAD accessors {{{2*/

	/**
	 * GetHeadCommit
	 *
	 * Gets the head commit for this project
	 * Shortcut for getting the tip commit of the HEAD branch
	 *
	 * @access public
	 * @return mixed head commit
	 */
	public function GetHeadCommit()
	{
		if (!$this->readHeadRef)
			$this->ReadHeadCommit();

		return $this->GetCommit($this->head);
	}

	/**
	 * ReadHeadCommit
	 *
	 * Reads the head commit hash
	 *
	 * @access protected
	 */
	public function ReadHeadCommit()
	{
		$this->readHeadRef = true;

		if ($this->GetCompat()) {
			$this->ReadHeadCommitGit();
		} else {
			$this->ReadHeadCommitRaw();
		}
	}

	/**
	 * ReadHeadCommitGit
	 *
	 * Read head commit using git executable
	 *
	 * @access private
	 */
	private function ReadHeadCommitGit()
	{
		$exe = new GitPHP_GitExe($this);
		$args = array();
		$args[] = '--verify';
		$args[] = 'HEAD';
		$this->head = trim($exe->Execute(GIT_REV_PARSE, $args));
	}

	/**
	 * ReadHeadCommitRaw
	 *
	 * Read head commit using raw git head pointer
	 *
	 * @access private
	 */
	private function ReadHeadCommitRaw()
	{
		$head = trim(file_get_contents($this->GetPath() . '/HEAD'));
		if (preg_match('/^([0-9A-Fa-f]{40})$/', $head, $regs)) {
			/* Detached HEAD */
			$this->head = $regs[1];
		} else if (preg_match('/^ref: (.+)$/', $head, $regs)) {
			/* standard pointer to head */
			if (!$this->readRefs)
				$this->ReadRefList();

			if (isset($this->heads[$regs[1]])) {
				$this->head = $this->heads[$regs[1]]->GetHash();
			}
		}
	}

/*}}}2*/

/* epoch accessors {{{2*/

	/**
	 * GetEpoch
	 *
	 * Gets this project's epoch
	 * (time of last change)
	 *
	 * @access public
	 * @return integer timestamp
	 */
	public function GetEpoch()
	{
		if (!$this->epochRead)
			$this->ReadEpoch();

		return $this->epoch;
	}

	/**
	 * GetAge
	 *
	 * Gets this project's age
	 * (time since most recent change)
	 *
	 * @access public
	 * @return integer age
	 */
	public function GetAge()
	{
		if (!$this->epochRead)
			$this->ReadEpoch();

		return time() - $this->epoch;
	}

	/**
	 * ReadEpoch
	 *
	 * Reads this project's epoch
	 * (timestamp of most recent change)
	 *
	 * @access private
	 */
	private function ReadEpoch()
	{
		$this->epochRead = true;

		if ($this->GetCompat()) {
			$this->ReadEpochGit();
		} else {
			$this->ReadEpochRaw();
		}
	}

	/**
	 * ReadEpochGit
	 *
	 * Reads this project's epoch using git executable
	 *
	 * @access private
	 */
	private function ReadEpochGit()
	{
		$exe = new GitPHP_GitExe($this);

		$args = array();
		$args[] = '--format="%(committer)"';
		$args[] = '--sort=-committerdate';
		$args[] = '--count=1';
		$args[] = 'refs/heads';

		$epochstr = trim($exe->Execute(GIT_FOR_EACH_REF, $args));

		if (preg_match('/ (\d+) [-+][01]\d\d\d$/', $epochstr, $regs)) {
			$this->epoch = $regs[1];
		}

		unset($exe);
	}

	/**
	 * ReadEpochRaw
	 *
	 * Reads this project's epoch using raw objects
	 *
	 * @access private
	 */
	private function ReadEpochRaw()
	{
		if (!$this->readRefs)
			$this->ReadRefList();

		$epoch = 0;
		foreach ($this->heads as $head) {
			$commit = $head->GetCommit();
			if ($commit) {
				if ($commit->GetCommitterEpoch() > $epoch) {
					$epoch = $commit->GetCommitterEpoch();
				}
			}
		}
		if ($epoch > 0) {
			$this->epoch = $epoch;
		}
	}

/*}}}2*/

/* compatibility accessors {{{2*/

	/**
	 * GetCompat
	 *
	 * Gets whether this project is running in compatibility mode
	 *
	 * @access public
	 * @return boolean true if compatibilty mode
	 */
	public function GetCompat()
	{
		if ($this->compat !== null) {
			return $this->compat;
		}

		if ($this->GetConfig()->HasValue('gitphp.compat')) {
			return $this->GetConfig()->GetValue('gitphp.compat');
		}

		return GitPHP_Config::GetInstance()->GetValue('compat', false);
	}

	/**
	 * SetCompat
	 *
	 * Sets whether this project is running in compatibility mode
	 *
	 * @access public
	 * @param boolean true if compatibility mode
	 */
	public function SetCompat($compat)
	{
		$this->compat = $compat;
	}

/*}}}2*/

	/**
	 * GetConfig
	 *
	 * Gets the config reader instance
	 *
	 * @access public
	 * @return mixed config class
	 */
	public function GetConfig()
	{
		if (!$this->config) {
			$this->config = new GitPHP_GitConfig($this);
		}

		return $this->config;
	}

/*}}}1*/

/* data loading methods {{{1*/

/* commit loading methods {{{2*/

	/**
	 * GetCommit
	 *
	 * Get a commit for this project
	 *
	 * @access public
	 */
	public function GetCommit($hash)
	{
		if (empty($hash))
			return null;

		if ($hash === 'HEAD')
			return $this->GetHeadCommit();

		if (substr_compare($hash, 'refs/heads/', 0, 11) === 0) {
			$head = $this->GetHead(substr($hash, 11));
			if ($head != null)
				return $head->GetCommit();
			return null;
		} else if (substr_compare($hash, 'refs/tags/', 0, 10) === 0) {
			$tag = $this->GetTag(substr($hash, 10));
			if ($tag != null) {
				$obj = $tag->GetCommit();
				if ($obj != null) {
					return $obj;
				}
			}
			return null;
		}

		if (!$this->readRefs)
			$this->ReadRefList();

		if (isset($this->heads['refs/heads/' . $hash]))
			return $this->heads['refs/heads/' . $hash]->GetCommit();

		if (isset($this->tags['refs/tags/' . $hash]))
			return $this->tags['refs/tags/' . $hash]->GetCommit();

		if (preg_match('/^[0-9A-Fa-f]{4,39}$/', $hash)) {
			var_dump($hash);
			$hash = $this->ExpandHash($hash);
		}

		if (preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {

			if (!isset($this->commitCache[$hash])) {
				$cacheKey = 'project|' . $this->project . '|commit|' . $hash;
				$cached = GitPHP_Cache::GetObjectCacheInstance()->Get($cacheKey);
				if ($cached)
					$this->commitCache[$hash] = $cached;
				else
					$this->commitCache[$hash] = new GitPHP_Commit($this, $hash);
			}

			return $this->commitCache[$hash];

		}

		return null;
	}

/*}}}2*/

/* ref loading methods {{{2*/

	/**
	 * GetRefs
	 *
	 * Gets the list of refs for the project
	 *
	 * @access public
	 * @param string $type type of refs to get
	 * @return array array of refs
	 */
	public function GetRefs($type = '')
	{
		if (!$this->readRefs)
			$this->ReadRefList();

		if ($type == 'tags') {
			return $this->tags;
		} else if ($type == 'heads') {
			return $this->heads;
		}

		return array_merge($this->heads, $this->tags);
	}

	/**
	 * ReadRefList
	 *
	 * Reads the list of refs for this project
	 *
	 * @access protected
	 */
	protected function ReadRefList()
	{
		$this->readRefs = true;

		if ($this->GetCompat()) {
			$this->ReadRefListGit();
		} else {
			$this->ReadRefListRaw();
		}
	}

	/**
	 * ReadRefListGit
	 *
	 * Reads the list of refs for this project using the git executable
	 *
	 * @access private
	 */
	private function ReadRefListGit()
	{
		$exe = new GitPHP_GitExe($this);
		$args = array();
		$args[] = '--heads';
		$args[] = '--tags';
		$args[] = '--dereference';
		$ret = $exe->Execute(GIT_SHOW_REF, $args);
		unset($exe);

		$lines = explode("\n", $ret);

		foreach ($lines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40}) refs\/(tags|heads)\/([^^]+)(\^{})?$/', $line, $regs)) {
				try {
					$key = 'refs/' . $regs[2] . '/' . $regs[3];
					if ($regs[2] == 'tags') {
						if ((!empty($regs[4])) && ($regs[4] == '^{}')) {
							$derefCommit = $this->GetCommit($regs[1]);
							if ($derefCommit && isset($this->tags[$key])) {
								$this->tags[$key]->SetCommit($derefCommit);
							}
								
						} else if (!isset($this->tags[$key])) {
							$this->tags[$key] = $this->LoadTag($regs[3], $regs[1]);
						}
					} else if ($regs[2] == 'heads') {
						$this->heads[$key] = new GitPHP_Head($this, $regs[3], $regs[1]);
					}
				} catch (Exception $e) {
				}
			}
		}
	}

	/**
	 * ReadRefListRaw
	 *
	 * Reads the list of refs for this project using the raw git files
	 *
	 * @access private
	 */
	private function ReadRefListRaw()
	{
		$pathlen = strlen($this->GetPath()) + 1;

		// read loose heads
		$heads = $this->ListDir($this->GetPath() . '/refs/heads');
		for ($i = 0; $i < count($heads); $i++) {
			$key = trim(substr($heads[$i], $pathlen), "/\\");

			if (isset($this->heads[$key])) {
				continue;
			}

			$hash = trim(file_get_contents($heads[$i]));
			if (preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
				$head = substr($key, strlen('refs/heads/'));
				$this->heads[$key] = new GitPHP_Head($this, $head, $hash);
			}
		}

		// read loose tags
		$tags = $this->ListDir($this->GetPath() . '/refs/tags');
		for ($i = 0; $i < count($tags); $i++) {
			$key = trim(substr($tags[$i], $pathlen), "/\\");

			if (isset($this->tags[$key])) {
				continue;
			}

			$hash = trim(file_get_contents($tags[$i]));
			if (preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
				$tag = substr($key, strlen('refs/tags/'));
				$this->tags[$key] = $this->LoadTag($tag, $hash);
			}
		}

		// check packed refs
		if (file_exists($this->GetPath() . '/packed-refs')) {
			$packedRefs = explode("\n", file_get_contents($this->GetPath() . '/packed-refs'));

			$lastRef = null;
			foreach ($packedRefs as $ref) {

				if (preg_match('/^\^([0-9A-Fa-f]{40})$/', $ref, $regs)) {
					// dereference of previous ref
					if (($lastRef != null) && ($lastRef instanceof GitPHP_Tag)) {
						$derefCommit = $this->GetCommit($regs[1]);
						if ($derefCommit) {
							$lastRef->SetCommit($derefCommit);
						}
					}
				}

				$lastRef = null;

				if (preg_match('/^([0-9A-Fa-f]{40}) refs\/(tags|heads)\/(.+)$/', $ref, $regs)) {
					// standard tag/head
					$key = 'refs/' . $regs[2] . '/' . $regs[3];
					if ($regs[2] == 'tags') {
						if (!isset($this->tags[$key])) {
							$lastRef = $this->LoadTag($regs[3], $regs[1]);
							$this->tags[$key] = $lastRef;
						}
					} else if ($regs[2] == 'heads') {
						if (!isset($this->heads[$key])) {
							$this->heads[$key] = new GitPHP_Head($this, $regs[3], $regs[1]);
						}
					}
				}
			}
		}
	}

	/**
	 * ListDir
	 *
	 * Recurses into a directory and lists files inside
	 *
	 * @access private
	 * @param string $dir directory
	 * @return array array of filenames
	 */
	private function ListDir($dir)
	{
		$files = array();
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (($file == '.') || ($file == '..')) {
					continue;
				}
				$fullFile = $dir . '/' . $file;
				if (is_dir($fullFile)) {
					$subFiles = $this->ListDir($fullFile);
					if (count($subFiles) > 0) {
						$files = array_merge($files, $subFiles);
					}
				} else {
					$files[] = $fullFile;
				}
			}
		}
		return $files;
	}

/*}}}2*/

/* tag loading methods {{{2*/

	/**
	 * GetTags
	 *
	 * Gets list of tags for this project by age descending
	 *
	 * @access public
	 * @param integer $count number of tags to load
	 * @return array array of tags
	 */
	public function GetTags($count = 0)
	{
		if (!$this->readRefs)
			$this->ReadRefList();

		if ($this->GetCompat()) {
			return $this->GetTagsGit($count);
		} else {
			return $this->GetTagsRaw($count);
		}
	}

	/**
	 * GetTagsGit
	 *
	 * Gets list of tags for this project by age descending using git executable
	 *
	 * @access private
	 * @param integer $count number of tags to load
	 * @return array array of tags
	 */
	private function GetTagsGit($count = 0)
	{
		$exe = new GitPHP_GitExe($this);
		$args = array();
		$args[] = '--sort=-creatordate';
		$args[] = '--format="%(refname)"';
		if ($count > 0) {
			$args[] = '--count=' . $count;
		}
		$args[] = '--';
		$args[] = 'refs/tags';
		$ret = $exe->Execute(GIT_FOR_EACH_REF, $args);
		unset($exe);

		$lines = explode("\n", $ret);

		$tags = array();

		foreach ($lines as $ref) {
			if (isset($this->tags[$ref])) {
				$tags[] = $this->tags[$ref];
			}
		}

		return $tags;
	}

	/**
	 * GetTagsRaw
	 *
	 * Gets list of tags for this project by age descending using raw git objects
	 *
	 * @access private
	 * @param integer $count number of tags to load
	 * @return array array of tags
	 */
	private function GetTagsRaw($count = 0)
	{
		$tags = $this->tags;
		usort($tags, array('GitPHP_Tag', 'CompareCreationEpoch'));

		if (($count > 0) && (count($tags) > $count)) {
			$tags = array_slice($tags, 0, $count);
		}

		return $tags;
	}

	/**
	 * GetTag
	 *
	 * Gets a single tag
	 *
	 * @access public
	 * @param string $tag tag to find
	 * @return mixed tag object
	 */
	public function GetTag($tag)
	{
		if (empty($tag))
			return null;

		if (!$this->readRefs)
			$this->ReadRefList();

		$key = 'refs/tags/' . $tag;

		if (!isset($this->tags[$key])) {
			$this->tags[$key] = $this->LoadTag($tag);
		}

		return $this->tags[$key];
	}

	/**
	 * LoadTag
	 *
	 * Attempts to load a cached tag, or creates a new object
	 *
	 * @access private
	 * @param string $tag tag to find
	 * @return mixed tag object
	 */
	private function LoadTag($tag, $hash = '')
	{
		if (empty($tag))
			return;

		$cacheKey = 'project|' . $this->project . '|tag|' . $tag;
		$cached = GitPHP_Cache::GetObjectCacheInstance()->Get($cacheKey);
		if ($cached) {
			return $cached;
		} else {
			return new GitPHP_Tag($this, $tag, $hash);
		}
	}

/*}}}2*/

/* head loading methods {{{2*/

	/**
	 * GetHeads
	 *
	 * Gets list of heads for this project by age descending
	 *
	 * @access public
	 * @param integer $count number of tags to load
	 * @return array array of heads
	 */
	public function GetHeads($count = 0)
	{
		if (!$this->readRefs)
			$this->ReadRefList();

		if ($this->GetCompat()) {
			return $this->GetHeadsGit($count);
		} else {
			return $this->GetHeadsRaw($count);
		}
	}

	/**
	 * GetHeadsGit
	 *
	 * Gets the list of sorted heads using the git executable
	 *
	 * @access private
	 * @param integer $count number of tags to load
	 * @return array array of heads
	 */
	private function GetHeadsGit($count = 0)
	{
		$exe = new GitPHP_GitExe($this);
		$args = array();
		$args[] = '--sort=-committerdate';
		$args[] = '--format="%(refname)"';
		if ($count > 0) {
			$args[] = '--count=' . $count;
		}
		$args[] = '--';
		$args[] = 'refs/heads';
		$ret = $exe->Execute(GIT_FOR_EACH_REF, $args);
		unset($exe);

		$lines = explode("\n", $ret);

		$heads = array();

		foreach ($lines as $ref) {
			if (isset($this->heads[$ref])) {
				$heads[] = $this->heads[$ref];
			}
		}

		return $heads;
	}

	/**
	 * GetHeadsRaw
	 *
	 * Gets the list of sorted heads using raw git objects
	 *
	 * @access private
	 * @param integer $count number of tags to load
	 * @return array array of heads
	 */
	private function GetHeadsRaw($count = 0)
	{
		$heads = $this->heads;
		usort($heads, array('GitPHP_Head', 'CompareAge'));

		if (($count > 0) && (count($heads) > $count)) {
			$heads = array_slice($heads, 0, $count);
		}
		return $heads;
	}

	/**
	 * GetHead
	 *
	 * Gets a single head
	 *
	 * @access public
	 * @param string $head head to find
	 * @return mixed head object
	 */
	public function GetHead($head)
	{
		if (empty($head))
			return null;

		if (!$this->readRefs)
			$this->ReadRefList();

		$key = 'refs/heads/' . $head;

		if (!isset($this->heads[$key])) {
			$this->heads[$key] = new GitPHP_Head($this, $head);
		}

		return $this->heads[$key];
	}

/*}}}2*/

/* log methods {{{2*/

	/**
	 * GetLogHash
	 *
	 * Gets log entries as an array of hashes
	 *
	 * @access private
	 * @param string $hash hash to start the log at
	 * @param integer $count number of entries to get
	 * @param integer $skip number of entries to skip
	 * @return array array of hashes
	 */
	private function GetLogHash($hash, $count = 50, $skip = 0)
	{
		return $this->RevList($hash, $count, $skip);
	}

	/**
	 * GetLog
	 *
	 * Gets log entries as an array of commit objects
	 *
	 * @access public
	 * @param string $hash hash to start the log at
	 * @param integer $count number of entries to get
	 * @param integer $skip number of entries to skip
	 * @return array array of commit objects
	 */
	public function GetLog($hash, $count = 50, $skip = 0)
	{
		if ($this->GetCompat() || ($skip > GitPHP_Config::GetInstance()->GetValue('largeskip', 200)) ) {
			return $this->GetLogGit($hash, $count, $skip);
		} else {
			return $this->GetLogRaw($hash, $count, $skip);
		}
	}

	/**
	 * GetLogGit
	 *
	 * Gets log entries using git exe
	 *
	 * @access private
	 * @param string $hash hash to start the log at
	 * @param integer $count number of entries to get
	 * @param integer $skip number of entries to skip
	 * @return array array of commit objects
	 */
	private function GetLogGit($hash, $count = 50, $skip = 0)
	{
		$log = $this->GetLogHash($hash, $count, $skip);
		$len = count($log);
		for ($i = 0; $i < $len; ++$i) {
			$log[$i] = $this->GetCommit($log[$i]);
		}
		return $log;
	}

	/**
	 * GetLogRaw
	 *
	 * Gets log entries using raw git objects
	 * Based on history walking code from glip
	 *
	 * @access private
	 */
	private function GetLogRaw($hash, $count = 50, $skip = 0)
	{
		$total = $count + $skip;

		$inc = array();
		$num = 0;
		$queue = array($this->GetCommit($hash));
		while (($commit = array_shift($queue)) !== null) {
			$parents = $commit->GetParents();
			foreach ($parents as $parent) {
				if (!isset($inc[$parent->GetHash()])) {
					$inc[$parent->GetHash()] = 1;
					$queue[] = $parent;
					$num++;
				} else {
					$inc[$parent->GetHash()]++;
				}
			}
			if ($num >= $total)
				break;
		}

		$queue = array($this->GetCommit($hash));
		$log = array();
		$num = 0;
		while (($commit = array_pop($queue)) !== null) {
			array_push($log, $commit);
			$num++;
			if ($num == $total) {
				break;
			}
			$parents = $commit->GetParents();
			foreach ($parents as $parent) {
				if (isset($inc[$parent->GetHash()])) {
					if (--$inc[$parent->GetHash()] == 0) {
						$queue[] = $parent;
					}
				}
			}
		}

		if ($skip > 0) {
			$log = array_slice($log, $skip, $count);
		}
		usort($log, array('GitPHP_Commit', 'CompareAge'));
		return $log;
	}

/*}}}2*/

/* blob loading methods {{{2*/

	/**
	 * GetBlob
	 *
	 * Gets a blob from this project
	 *
	 * @access public
	 * @param string $hash blob hash
	 */
	public function GetBlob($hash)
	{
		if (empty($hash))
			return null;

		$cacheKey = 'project|' . $this->project . '|blob|' . $hash;
		$cached = GitPHP_Cache::GetObjectCacheInstance()->Get($cacheKey);
		if ($cached)
			return $cached;

		return new GitPHP_Blob($this, $hash);
	}

/*}}}2*/

/* tree loading methods {{{2*/

	/**
	 * GetTree
	 *
	 * Gets a tree from this project
	 *
	 * @access public
	 * @param string $hash tree hash
	 */
	public function GetTree($hash)
	{
		if (empty($hash))
			return null;

		$cacheKey = 'project|' . $this->project . '|tree|' . $hash;
		$cached = GitPHP_Cache::GetObjectCacheInstance()->Get($cacheKey);
		if ($cached)
			return $cached;

		return new GitPHP_Tree($this, $hash);
	}

/*}}}2*/

/* raw object loading methods {{{2*/

	/**
	 * GetObject
	 *
	 * Gets the raw content of an object
	 *
	 * @access public
	 * @param string $hash object hash
	 * @return string object data
	 */
	public function GetObject($hash, &$type = 0)
	{
		if (!preg_match('/^[0-9A-Fa-f]{40}$/', $hash)) {
			return false;
		}

		// first check if it's unpacked
		$path = $this->GetPath() . '/objects/' . substr($hash, 0, 2) . '/' . substr($hash, 2);
		if (file_exists($path)) {
			list($header, $data) = explode("\0", gzuncompress(file_get_contents($path)), 2);
			sscanf($header, "%s %d", $typestr, $size);
			switch ($typestr) {
				case 'commit':
					$type = GitPHP_Pack::OBJ_COMMIT;
					break;
				case 'tree':
					$type = GitPHP_Pack::OBJ_TREE;
					break;
				case 'blob':
					$type = GitPHP_Pack::OBJ_BLOB;
					break;
				case 'tag':
					$type = GitPHP_Pack::OBJ_TAG;
					break;
			}
			return $data;
		}

		if (!$this->packsRead) {
			$this->ReadPacks();
		}

		// then try packs
		foreach ($this->packs as $pack) {
			$data = $pack->GetObject($hash, $type);
			if ($data !== false) {
				return $data;
			}
		}

		return false;
	}

	/**
	 * ReadPacks
	 *
	 * Read the list of packs in the repository
	 *
	 * @access private
	 */
	private function ReadPacks()
	{
		$dh = opendir($this->GetPath() . '/objects/pack');
		if ($dh !== false) {
			while (($file = readdir($dh)) !== false) {
				if (preg_match('/^pack-([0-9A-Fa-f]{40})\.idx$/', $file, $regs)) {
					$this->packs[] = new GitPHP_Pack($this, $regs[1]);
				}
			}
		}
		$this->packsRead = true;
	}

/*}}}2*/

/* hash management methods {{{2*/

	/**
	 * AbbreviateHash
	 *
	 * Calculates the unique abbreviated hash for a full hash
	 *
	 * @param string $hash hash to abbreviate
	 * @return string abbreviated hash
	 */
	public function AbbreviateHash($hash)
	{
		if (!(preg_match('/[0-9A-Fa-f]{40}/', $hash))) {
			return $hash;
		}

		if ($this->GetCompat()) {
			return $this->AbbreviateHashGit($hash);
		} else {
			return $this->AbbreviateHashRaw($hash);
		}
	}

	/**
	 * AbbreviateHashGit
	 *
	 * Abbreviates a hash using the git executable
	 *
	 * @param string $hash hash to abbreviate
	 * @return string abbreviated hash
	 */
	private function AbbreviateHashGit($hash)
	{
		$exe = new GitPHP_GitExe($this);
		$args = array();
		$args[] = '-1';
		$args[] = '--format=format:%h';
		$args[] = $hash;

		$abbrevData = explode("\n", $exe->Execute(GIT_REV_LIST, $args));
		if (empty($abbrevData[0])) {
			return $hash;
		}
		if (substr_compare(trim($abbrevData[0]), 'commit', 0, 6) !== 0) {
			return $hash;
		}

		if (empty($abbrevData[1])) {
			return $hash;
		}

		return trim($abbrevData[1]);
	}

	/**
	 * AbbreviateHashRaw
	 *
	 * Abbreviates a hash using raw git objects
	 *
	 * @param string $hash hash to abbreviate
	 * @return string abbreviated hash
	 */
	private function AbbreviateHashRaw($hash)
	{
		$abbrevLen = GITPHP_ABBREV_HASH_MIN;

		if ($this->GetConfig()->HasValue('core.abbrev')) {
			$abbrevLen = max(4, min($this->GetConfig()->GetValue('core.abbrev'), 40));
		}

		$prefix = substr($hash, 0, $abbrevLen);

		if (!GitPHP_Config::GetInstance()->GetValue('uniqueabbrev', false)) {
			return $prefix;
		}

		$hashMap = array();

		$matches = $this->FindHashObjects($prefix);
		foreach ($matches as $matchingHash) {
			$hashMap[$matchingHash] = 1;
		}

		if (!$this->packsRead) {
			$this->ReadPacks();
		}

		foreach ($this->packs as $pack) {
			$matches = $pack->FindHashes($prefix);
			foreach ($matches as $matchingHash) {
				$hashMap[$matchingHash] = 1;
			}
		}

		if (count($hashMap) == 0) {
			return $hash;
		}

		if (count($hashMap) == 1) {
			return $prefix;
		}

		for ($len = $abbrevLen+1; $len < 40; $len++) {
			$prefix = substr($hash, 0, $len);

			foreach ($hashMap as $matchingHash => $val) {
				if (substr_compare($matchingHash, $prefix, 0, $len) !== 0) {
					unset($hashMap[$matchingHash]);
				}
			}

			if (count($hashMap) == 1) {
				return $prefix;
			}
		}

		return $hash;
	}

	/**
	 * ExpandHash
	 *
	 * Finds the full hash for an abbreviated hash
	 *
	 * @param string $abbrevHash abbreviated hash
	 * @return string full hash
	 */
	public function ExpandHash($abbrevHash)
	{
		if (!(preg_match('/[0-9A-Fa-f]{4,39}/', $abbrevHash))) {
			return $abbrevHash;
		}

		if ($this->GetCompat()) {
			return $this->ExpandHashGit($abbrevHash);
		}  else {
			return $this->ExpandHashRaw($abbrevHash);
		}
	}

	/**
	 * ExpandHashGit
	 *
	 * Expands a hash using the git executable
	 *
	 * @param string $abbrevHash
	 * @return string full hash
	 */
	private function ExpandHashGit($abbrevHash)
	{
		$exe = new GitPHP_GitExe($this);
		$args = array();
		$args[] = '-1';
		$args[] = '--format=format:%H';
		$args[] = $abbrevHash;

		$fullData = explode("\n", $exe->Execute(GIT_REV_LIST, $args));
		if (empty($fullData[0])) {
			return $abbrevHash;
		}
		if (substr_compare(trim($fullData[0]), 'commit', 0, 6) !== 0) {
			return $abbrevHash;
		}

		if (empty($fullData[1])) {
			return $abbrevHash;
		}

		return trim($fullData[1]);
	}

	/**
	 * ExpandGitRaw
	 *
	 * Expands a hash using raw git objects
	 *
	 * @param string $abbrevHash abbreviated hash
	 * @return string full hash
	 */
	private function ExpandHashRaw($abbrevHash)
	{
		$matches = $this->FindHashObjects($abbrevHash);
		if (count($matches) > 0) {
			return $matches[0];
		}

		if (!$this->packsRead) {
			$this->ReadPacks();
		}

		foreach ($this->packs as $pack) {
			$matches = $pack->FindHashes($abbrevHash);
			if (count($matches) > 0) {
				return $matches[0];
			}
		}

		return $abbrevHash;
	}

	/**
	 * FindHashObjects
	 *
	 * Finds loose hash files matching a given prefix
	 *
	 * @access private
	 * @param string $prefix hash prefix
	 * @return array array of hash objects
	 */
	private function FindHashObjects($prefix)
	{
		$matches = array();
		if (empty($prefix)) {
			return $matches;
		}

		$subdir = substr($prefix, 0, 2);
		$fulldir = $this->GetPath() . '/objects/' . $subdir;
		if (!is_dir($fulldir)) {
			return $matches;
		}

		$prefixlen = strlen($prefix);
		$dh = opendir($fulldir);
		if ($dh !== false) {
			while (($file = readdir($dh)) !== false) {
				$fullhash = $subdir . $file;
				if (substr_compare($fullhash, $prefix, 0, $prefixlen) === 0) {
					$matches[] = $fullhash;
				}
			}
		}
		return $matches;
	}

/*}}}2*/

/*}}}1*/

/* search methods {{{1*/

	/**
	 * SearchCommit
	 *
	 * Gets a list of commits with commit messages matching the given pattern
	 *
	 * @access public
	 * @param string $pattern search pattern
	 * @param string $hash hash to start searching from
	 * @param integer $count number of results to get
	 * @param integer $skip number of results to skip
	 * @return array array of matching commits
	 */
	public function SearchCommit($pattern, $hash = 'HEAD', $count = 50, $skip = 0)
	{
		if (empty($pattern))
			return;

		$args = array();

		$exe = new GitPHP_GitExe($this);
		if ($exe->CanIgnoreRegexpCase())
			$args[] = '--regexp-ignore-case';
		unset($exe);

		$args[] = '--grep=\'' . $pattern . '\'';

		$ret = $this->RevList($hash, $count, $skip, $args);
		$len = count($ret);

		for ($i = 0; $i < $len; ++$i) {
			$ret[$i] = $this->GetCommit($ret[$i]);
		}
		return $ret;
	}

	/**
	 * SearchAuthor
	 *
	 * Gets a list of commits with authors matching the given pattern
	 *
	 * @access public
	 * @param string $pattern search pattern
	 * @param string $hash hash to start searching from
	 * @param integer $count number of results to get
	 * @param integer $skip number of results to skip
	 * @return array array of matching commits
	 */
	public function SearchAuthor($pattern, $hash = 'HEAD', $count = 50, $skip = 0)
	{
		if (empty($pattern))
			return;

		$args = array();

		$exe = new GitPHP_GitExe($this);
		if ($exe->CanIgnoreRegexpCase())
			$args[] = '--regexp-ignore-case';
		unset($exe);

		$args[] = '--author=\'' . $pattern . '\'';

		$ret = $this->RevList($hash, $count, $skip, $args);
		$len = count($ret);

		for ($i = 0; $i < $len; ++$i) {
			$ret[$i] = $this->GetCommit($ret[$i]);
		}
		return $ret;
	}

	/**
	 * SearchCommitter
	 *
	 * Gets a list of commits with committers matching the given pattern
	 *
	 * @access public
	 * @param string $pattern search pattern
	 * @param string $hash hash to start searching from
	 * @param integer $count number of results to get
	 * @param integer $skip number of results to skip
	 * @return array array of matching commits
	 */
	public function SearchCommitter($pattern, $hash = 'HEAD', $count = 50, $skip = 0)
	{
		if (empty($pattern))
			return;

		$args = array();

		$exe = new GitPHP_GitExe($this);
		if ($exe->CanIgnoreRegexpCase())
			$args[] = '--regexp-ignore-case';
		unset($exe);

		$args[] = '--committer=\'' . $pattern . '\'';

		$ret = $this->RevList($hash, $count, $skip, $args);
		$len = count($ret);

		for ($i = 0; $i < $len; ++$i) {
			$ret[$i] = $this->GetCommit($ret[$i]);
		}
		return $ret;
	}

/*}}}1*/

/* private utilities {{{1*/

	/**
	 * RevList
	 *
	 * Common code for using rev-list command
	 *
	 * @access private
	 * @param string $hash hash to list from
	 * @param integer $count number of results to get
	 * @param integer $skip number of results to skip
	 * @param array $args args to give to rev-list
	 * @return array array of hashes
	 */
	private function RevList($hash, $count = 50, $skip = 0, $args = array())
	{
		if ($count < 1)
			return;

		$exe = new GitPHP_GitExe($this);

		$canSkip = true;
		
		if ($skip > 0)
			$canSkip = $exe->CanSkip();

		if ($canSkip) {
			$args[] = '--max-count=' . $count;
			if ($skip > 0) {
				$args[] = '--skip=' . $skip;
			}
		} else {
			$args[] = '--max-count=' . ($count + $skip);
		}

		$args[] = $hash;

		$revlist = explode("\n", $exe->Execute(GIT_REV_LIST, $args));

		if (!$revlist[count($revlist)-1]) {
			/* the last newline creates a null entry */
			array_splice($revlist, -1, 1);
		}

		if (($skip > 0) && (!$exe->CanSkip())) {
			return array_slice($revlist, $skip, $count);
		}

		return $revlist;
	}

/*}}}1*/

/* static utilities {{{1*/

	/**
	 * CompareProject
	 *
	 * Compares two projects by project name
	 *
	 * @access public
	 * @static
	 * @param mixed $a first project
	 * @param mixed $b second project
	 * @return integer comparison result
	 */
	public static function CompareProject($a, $b)
	{
		$catCmp = strcmp($a->GetCategory(), $b->GetCategory());
		if ($catCmp !== 0)
			return $catCmp;

		return strcmp($a->GetProject(), $b->GetProject());
	}

	/**
	 * CompareDescription
	 *
	 * Compares two projects by description
	 *
	 * @access public
	 * @static
	 * @param mixed $a first project
	 * @param mixed $b second project
	 * @return integer comparison result
	 */
	public static function CompareDescription($a, $b)
	{
		$catCmp = strcmp($a->GetCategory(), $b->GetCategory());
		if ($catCmp !== 0)
			return $catCmp;

		return strcmp($a->GetDescription(), $b->GetDescription());
	}

	/**
	 * CompareOwner
	 *
	 * Compares two projects by owner
	 *
	 * @access public
	 * @static
	 * @param mixed $a first project
	 * @param mixed $b second project
	 * @return integer comparison result
	 */
	public static function CompareOwner($a, $b)
	{
		$catCmp = strcmp($a->GetCategory(), $b->GetCategory());
		if ($catCmp !== 0)
			return $catCmp;

		return strcmp($a->GetOwner(), $b->GetOwner());
	}

	/**
	 * CompareAge
	 *
	 * Compares two projects by age
	 *
	 * @access public
	 * @static
	 * @param mixed $a first project
	 * @param mixed $b second project
	 * @return integer comparison result
	 */
	public static function CompareAge($a, $b)
	{
		$catCmp = strcmp($a->GetCategory(), $b->GetCategory());
		if ($catCmp !== 0)
			return $catCmp;

		if ($a->GetAge() === $b->GetAge())
			return 0;
		return ($a->GetAge() < $b->GetAge() ? -1 : 1);
	}

/*}}}1*/

}
