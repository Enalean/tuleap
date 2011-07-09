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

/**
 * Project class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Project
{

	/**
	 * project
	 *
	 * Stores the project internally
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * owner
	 *
	 * Stores the owner internally
	 *
	 * @access protected
	 */
	protected $owner = "";

	/**
	 * readOwner
	 *
	 * Stores whether the file owner has been read
	 *
	 * @access protected
	 */
	protected $readOwner = false;

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

	/**
	 * category
	 *
	 * Stores the category internally
	 *
	 * @access protected
	 */
	protected $category = '';

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

	/**
	 * commitCache
	 *
	 * Caches fetched commit objects in case of
	 * repeated requests for the same object
	 *
	 * @access protected
	 */
	protected $commitCache = array();

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

	/**
	 * __construct
	 *
	 * Class constructor
	 *
	 * @access public
	 * @throws Exception if project is invalid or outside of projectroot
	 */
	public function __construct($project)
	{
		$this->SetProject($project);
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
		$projectRoot = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('projectroot'));

		$realProjectRoot = realpath($projectRoot);
		$path = $projectRoot . $project;
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
		if (empty($this->owner) && !$this->readOwner) {

			$exe = new GitPHP_GitExe($this);
			$args = array();
			$args[] = 'gitweb.owner';
			$this->owner = $exe->Execute(GIT_CONFIG, $args);
			unset($exe);
			
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

			$this->readOwner = true;
		}
	
		return $this->owner;
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
	 * GetSlug
	 *
	 * Gets the project as a filename/url friendly slug
	 *
	 * @access public
	 * @return string the slug
	 */
	public function GetSlug()
	{
		$from = array(
			'/',
			'.git'
		);
		$to = array(
			'-',
			''
		);
		return str_replace($from, $to, $this->project);
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
		$projectRoot = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('projectroot'));

		return $projectRoot . $this->project;
	}

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
			$this->description = file_get_contents($this->GetPath() . '/description');
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
		return $this->category;
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

		$exe = new GitPHP_GitExe($this);
		$args = array();
		$args[] = '--verify';
		$args[] = 'HEAD';
		$this->head = trim($exe->Execute(GIT_REV_PARSE, $args));
	}

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

		if (preg_match('/[0-9a-f]{40}/i', $hash)) {

			if (!isset($this->commitCache[$hash])) {
				$cacheKey = 'project|' . $this->project . '|commit|' . $hash;
				$cached = GitPHP_Cache::GetInstance()->Get($cacheKey);
				if ($cached)
					$this->commitCache[$hash] = $cached;
				else
					$this->commitCache[$hash] = new GitPHP_Commit($this, $hash);
			}

			return $this->commitCache[$hash];

		}

		if (!$this->readRefs)
			$this->ReadRefList();

		if (isset($this->heads['refs/heads/' . $hash]))
			return $this->heads['refs/heads/' . $hash]->GetCommit();

		if (isset($this->tags['refs/tags/' . $hash]))
			return $this->tags['refs/tags/' . $hash]->GetCommit();

		return null;
	}

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
	public function ReadRefList()
	{
		$this->readRefs = true;

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
		$cached = GitPHP_Cache::GetInstance()->Get($cacheKey);
		if ($cached) {
			return $cached;
		} else {
			return new GitPHP_Tag($this, $tag, $hash);
		}
	}

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

		$key = 'refs/heads/' . $head;

		if (!isset($this->heads[$key])) {
			$this->heads[$key] = new GitPHP_Head($this, $head);
		}

		return $this->heads[$key];
	}

	/**
	 * GetLogHash
	 *
	 * Gets log entries as an array of hashes
	 *
	 * @access public
	 * @param string $hash hash to start the log at
	 * @param integer $count number of entries to get
	 * @param integer $skip number of entries to skip
	 * @return array array of hashes
	 */
	public function GetLogHash($hash, $count = 50, $skip = 0)
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
		$log = $this->GetLogHash($hash, $count, $skip);
		$len = count($log);
		for ($i = 0; $i < $len; ++$i) {
			$log[$i] = $this->GetCommit($log[$i]);
		}
		return $log;
	}

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
		$cached = GitPHP_Cache::GetInstance()->Get($cacheKey);
		if ($cached)
			return $cached;

		return new GitPHP_Blob($this, $hash);
	}

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
		$cached = GitPHP_Cache::GetInstance()->Get($cacheKey);
		if ($cached)
			return $cached;

		return new GitPHP_Tree($this, $hash);
	}

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

}
