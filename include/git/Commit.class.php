<?php
/**
 * GitPHP Commit
 *
 * Represents a single commit
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_INCLUDEDIR . 'defs.commands.php');
require_once(GITPHP_GITOBJECTDIR . 'GitObject.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Tree.class.php');
require_once(GITPHP_GITOBJECTDIR . 'TreeDiff.class.php');

/**
 * Commit class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Commit extends GitPHP_GitObject
{

	/**
	 * dataRead
	 *
	 * Indicates whether data for this commit has been read
	 *
	 * @access protected
	 */
	protected $dataRead = false;

	/**
	 * parents
	 *
	 * Array of parent commits
	 *
	 * @access protected
	 */
	protected $parents = array();

	/**
	 * tree
	 *
	 * Tree object for this commit
	 *
	 * @access protected
	 */
	protected $tree;

	/**
	 * author
	 *
	 * Author for this commit
	 *
	 * @access protected
	 */
	protected $author;

	/**
	 * authorEpoch
	 *
	 * Author's epoch
	 *
	 * @access protected
	 */
	protected $authorEpoch;

	/**
	 * authorTimezone
	 *
	 * Author's timezone
	 *
	 * @access protected
	 */
	protected $authorTimezone;

	/**
	 * committer
	 *
	 * Committer for this commit
	 *
	 * @access protected
	 */
	protected $committer;

	/**
	 * committerEpoch
	 *
	 * Committer's epoch
	 *
	 * @access protected
	 */
	protected $committerEpoch;

	/**
	 * committerTimezone
	 *
	 * Committer's timezone
	 *
	 * @access protected
	 */
	protected $committerTimezone;

	/**
	 * title
	 *
	 * Stores the commit title
	 *
	 * @access protected
	 */
	protected $title;

	/**
	 * comment
	 *
	 * Stores the commit comment
	 *
	 * @access protected
	 */
	protected $comment = array();

	/**
	 * __construct
	 *
	 * Instantiates object
	 *
	 * @access public
	 * @param mixed $project the project
	 * @param string $hash object hash
	 * @return mixed git object
	 * @throws Exception exception on invalid hash
	 */
	public function __construct($project, $hash)
	{
		parent::__construct($project, $hash);
	}

	/**
	 * GetParent
	 *
	 * Gets the main parent of this commit
	 *
	 * @access public
	 * @return mixed commit object for parent
	 */
	public function GetParent()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if (isset($this->parents[0]))
			return $this->parents[0];
		return null;
	}

	/**
	 * GetParents
	 *
	 * Gets an array of parent objects for this commit
	 *
	 * @access public
	 * @return mixed array of commit objects
	 */
	public function GetParents()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->parents;
	}

	/**
	 * GetTree
	 *
	 * Gets the tree for this commit
	 *
	 * @access public
	 * @return mixed tree object
	 */
	public function GetTree()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->tree;
	}

	/**
	 * GetAuthor
	 *
	 * Gets the author for this commit
	 *
	 * @access public
	 * @return string author
	 */
	public function GetAuthor()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->author;
	}

	/**
	 * GetAuthorName
	 *
	 * Gets the author's name only
	 *
	 * @access public
	 * @return string author name
	 */
	public function GetAuthorName()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return preg_replace('/ <.*/', '', $this->author);
	}

	/**
	 * GetAuthorEpoch
	 *
	 * Gets the author's epoch
	 *
	 * @access public
	 * @return string author epoch
	 */
	public function GetAuthorEpoch()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->authorEpoch;
	}

	/**
	 * GetAuthorLocalEpoch
	 *
	 * Gets the author's local epoch
	 *
	 * @access public
	 * @return string author local epoch
	 */
	public function GetAuthorLocalEpoch()
	{
		$epoch = $this->GetAuthorEpoch();
		$tz = $this->GetAuthorTimezone();
		if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
			$local = $epoch + ((((int)$regs[1]) + ($regs[2]/60)) * 3600);
			return $local;
		}
		return $epoch;
	}

	/**
	 * GetAuthorTimezone
	 *
	 * Gets the author's timezone
	 *
	 * @access public
	 * @return string author timezone
	 */
	public function GetAuthorTimezone()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->authorTimezone;
	}

	/**
	 * GetCommitter
	 *
	 * Gets the author for this commit
	 *
	 * @access public
	 * @return string author
	 */
	public function GetCommitter()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->committer;
	}

	/**
	 * GetCommitterName
	 *
	 * Gets the author's name only
	 *
	 * @access public
	 * @return string author name
	 */
	public function GetCommitterName()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return preg_replace('/ <.*/', '', $this->committer);
	}

	/**
	 * GetCommitterEpoch
	 *
	 * Gets the committer's epoch
	 *
	 * @access public
	 * @return string committer epoch
	 */
	public function GetCommitterEpoch()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->committerEpoch;
	}

	/**
	 * GetCommitterLocalEpoch
	 *
	 * Gets the committer's local epoch
	 *
	 * @access public
	 * @return string committer local epoch
	 */
	public function GetCommitterLocalEpoch()
	{
		$epoch = $this->GetCommitterEpoch();
		$tz = $this->GetCommitterTimezone();
		if (preg_match('/^([+\-][0-9][0-9])([0-9][0-9])$/', $tz, $regs)) {
			$local = $epoch + ((((int)$regs[1]) + ($regs[2]/60)) * 3600);
			return $local;
		}
		return $epoch;
	}

	/**
	 * GetCommitterTimezone
	 *
	 * Gets the author's timezone
	 *
	 * @access public
	 * @return string author timezone
	 */
	public function GetCommitterTimezone()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->committerTimezone;
	}

	/**
	 * GetTitle
	 *
	 * Gets the commit title
	 *
	 * @access public
	 * @param integer $trim length to trim to (0 for no trim)
	 * @return string title
	 */
	public function GetTitle($trim = 0)
	{
		if (!$this->dataRead)
			$this->ReadData();
		
		if (($trim > 0) && (strlen($this->title) > $trim)) {
			return substr($this->title, 0, $trim) . '...';
		}

		return $this->title;
	}

	/**
	 * GetComment
	 *
	 * Gets the lines of comment
	 *
	 * @access public
	 * @return array lines of comment
	 */
	public function GetComment()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return $this->comment;
	}

	/**
	 * GetAge
	 *
	 * Gets the age of the commit
	 *
	 * @access public
	 * @return string age
	 */
	public function GetAge()
	{
		if (!$this->dataRead)
			$this->ReadData();

		if (!empty($this->committerEpoch))
			return time() - $this->committerEpoch;
		
		return '';
	}

	/**
	 * ReadData
	 *
	 * Read the data for the commit
	 *
	 * @access protected
	 */
	protected function ReadData()
	{
		$this->dataRead = true;

		/* get data from git_rev_list */
		$exe = new GitPHP_GitExe($this->project);
		$args = array();
		$args[] = '--header';
		$args[] = '--parents';
		$args[] = '--max-count=1';
		$args[] = $this->hash;
		$ret = $exe->Execute(GIT_REV_LIST, $args);
		unset($exe);

		$lines = explode("\n", $ret);

		if (!isset($lines[0]))
			return;

		/* In case we returned something unexpected */
		$tok = strtok($lines[0], ' ');
		if ($tok != $this->hash)
			return;

		/* Read all parents */
		$tok = strtok(' ');
		while ($tok !== false) {
			try {
				$this->parents[] = new GitPHP_Commit($this->project, $tok);
			} catch (Exception $e) {
			}
			$tok = strtok(' ');
		}

		foreach ($lines as $i => $line) {
			if (preg_match('/^tree ([0-9a-fA-F]{40})$/', $line, $regs)) {
				/* Tree */
				try {
					$this->tree = new GitPHP_Tree($this->project, $regs[1]);
				} catch (Exception $e) {
				}
			} else if (preg_match('/^author (.*) ([0-9]+) (.*)$/', $line, $regs)) {
				/* author data */
				$this->author = $regs[1];
				$this->authorEpoch = $regs[2];
				$this->authorTimezone = $regs[3];
			} else if (preg_match('/^committer (.*) ([0-9]+) (.*)$/', $line, $regs)) {
				/* committer data */
				$this->committer = $regs[1];
				$this->committerEpoch = $regs[2];
				$this->committerTimezone = $regs[3];
			} else {
				/* commit comment */
				if (!(preg_match('/^[0-9a-fA-F]{40}/', $line) || preg_match('/^parent [0-9a-fA-F]{40}/', $line))) {
					$trimmed = trim($line);
					if (empty($this->title) && (strlen($trimmed) > 0))
						$this->title = $trimmed;
					if (!empty($this->title)) {
						if ((strlen($trimmed) > 0) || ($i < (count($lines)-1)))
							$this->comment[] = $trimmed;
					}
				}
			}
		}

	}

	/**
	 * GetHeads
	 *
	 * Gets heads that point to this commit
	 * 
	 * @access public
	 * @return array array of heads
	 */
	public function GetHeads()
	{
		$heads = array();

		$projectHeads = $this->project->GetHeads();

		foreach ($projectHeads as $head) {
			if ($head->GetCommit()->GetHash() === $this->hash) {
				$heads[] = $head;
			}
		}

		return $heads;
	}

	/**
	 * GetTags
	 *
	 * Gets tags that point to this commit
	 *
	 * @access public
	 * @return array array of tags
	 */
	public function GetTags()
	{
		$tags = array();

		$projectTags = $this->project->GetTags();

		foreach ($projectTags as $tag) {
			if ($tag->GetObject()->GetHash() === $this->hash) {
				$tags[] = $tag;
			}
		}

		return $tags;
	}

	/**
	 * GetArchive
	 *
	 * Gets an archive of this commit
	 *
	 * @access public
	 * @return string the archive data
	 * @param format the archive format
	 */
	public function GetArchive($format)
	{
		$exe = new GitPHP_GitExe($this->project);
		$args = array();
		if ($format == GITPHP_COMPRESS_ZIP)
			$args[] = '--format=zip';
		else
			$args[] = '--format=tar';
		$args[] = '--prefix=' . $this->project->GetSlug() . '/';
		$args[] = $this->hash;
		$data = $exe->Execute(GIT_ARCHIVE, $args);
		unset($exe);

		if (($format == GITPHP_COMPRESS_BZ2) && function_exists('bzcompress')) {
			return bzcompress($data, GitPHP_Config::GetInstance()->GetValue('compresslevel', 4));
		} else if (($format == GITPHP_COMPRESS_GZ) && function_exists('gzencode')) {
			return gzencode($arc, GitPHP_Config::GetInstance()->GetValue('compresslevel', -1));
		}

		return $data;
	}

	/**
	 * DiffToParent
	 *
	 * Diffs this commit with its immediate parent
	 *
	 * @access public
	 * @return mixed Tree diff
	 */
	public function DiffToParent()
	{
		return new GitPHP_TreeDiff($this->project, $this->hash);
	}

}
