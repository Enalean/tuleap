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

require_once(GITPHP_INCLUDEDIR . 'defs.commands.php');
require_once(GITPHP_INCLUDEDIR . 'git/GitExe.class.php');
require_once(GITPHP_INCLUDEDIR . 'git/Commit.class.php');
require_once(GITPHP_INCLUDEDIR . 'git/Tag.class.php');

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
	 * heads
	 *
	 * Stores the heads for the project
	 *
	 * @access protected
	 */
	protected $heads = array();

	/**
	 * readHeads
	 *
	 * Stores whether heads have been read yet
	 *
	 * @access protected
	 */
	protected $readHeads = false;

	/**
	 * tags
	 *
	 * Stores the tags for the project
	 *
	 * @access protected
	 */
	protected $tags = array();

	/**
	 * readTags
	 *
	 * Stores whether tags have been read yet
	 *
	 * @access protected
	 */
	protected $readTags = false;

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
		$projectRoot = GitPHP_Config::GetInstance()->GetValue('projectroot');
		$realProjectRoot = realpath($projectRoot);
		$fullPath = realpath($projectRoot . $project);

		if (!is_dir($fullPath)) {
			throw new Exception($project . ' is not a directory.');
		}

		if (!is_file($fullPath . '/HEAD')) {
			throw new Exception($project . ' is not a git repository.');
		}

		$pathPiece = substr($fullPath, 0, strlen($realProjectRoot));

		if (strcmp($pathPiece, $realProjectRoot) !== 0) {
			throw new Exception('Project ' . $project . ' is outside of projectroot.');
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
			$uid = fileowner($this->GetPath());
			if ($uid > 0) {
				$data = posix_getpwuid($uid);
				if (isset($data['gecos']) && !empty($data['gecos'])) {
					$this->owner = $data['gecos'];
				} elseif (isset($data['name']) && !empty($data['name'])) {
					$this->owner = $data['name'];
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
	 * GetPath
	 *
	 * Gets the full project path
	 *
	 * @access public
	 * @return string project path
	 */
	public function GetPath()
	{
		return GitPHP_Config::GetInstance()->GetValue('projectroot') . $this->project;
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
			return substr($this->description, 0, $trim) . '...';
		}

		return $this->description;
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

		if (!isset($this->commitCache[$hash]))
			$this->commitCache[$hash] = new GitPHP_Commit($this, $hash);

		return $this->commitCache[$hash];
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

		if ($a->GetHeadCommit()->GetAge() === $b->GetHeadCommit()->GetAge())
			return 0;
		return ($a->GetHeadCommit()->GetAge() < $b->GetHeadCommit()->GetAge() ? -1 : 1);
	}

	/**
	 * GetRefsForHash
	 *
	 * Get refs pointing to this hash
	 *
	 * @access public
	 * @param string $hash hash
	 * @param string $type ref type
	 * @return array array of refs
	 */
	public function GetRefsForHash($hash, $type)
	{
	}

	/**
	 * GetTags
	 *
	 * Gets list of tags for this project
	 *
	 * @access public
	 * @return array array of tags
	 */
	public function GetTags()
	{
		if (!$this->readTags)
			$this->ReadTagList();

		return $this->tags;
	}

	/**
	 * ReadTagList
	 *
	 * Reads tag list
	 *
	 * @access protected
	 */
	protected function ReadTagList()
	{
		$this->readTags = true;

		$exe = new GitPHP_GitExe($this);
		$args = array();
		$args[] = '--tags';
		$ret = $exe->Execute(GIT_SHOW_REF, $args);
		unset($exe);

		$lines = explode("\n", $ret);

		foreach ($lines as $line) {
			if (preg_match('/^([0-9a-fA-F]{40}) refs\/tags\/(.+)$/', $line, $regs)) {
				try {
					$this->tags[] = new GitPHP_Tag($this, $regs[2], $regs[1]);
				} catch (Exception $e) {
				}
			}
		}

		usort($this->tags, array('GitPHP_Tag', 'CompareAge'));
	}

	/**
	 * GetHeads
	 *
	 * Gets list of heads for this project
	 *
	 * @access public
	 * @return array array of heads
	 */
	public function GetHeads()
	{
		if (!$this->readHeads)
			$this->ReadHeadList();
	}

	/**
	 * ReadHeadList
	 *
	 * Reads head list
	 *
	 * @access protected
	 */
	protected function ReadHeadList()
	{
		$this->readHeads = true;
	}


}
