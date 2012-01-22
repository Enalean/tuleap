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

require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');
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
	 * readTree
	 *
	 * Stores whether tree filenames have been read
	 *
	 * @access protected
	 */
	protected $readTree = false;

	/**
	 * blobPaths
	 *
	 * Stores blob hash to path mappings
	 *
	 * @access protected
	 */
	protected $blobPaths = array();

	/**
	 * treePaths
	 *
	 * Stores tree hash to path mappings
	 *
	 * @access protected
	 */
	protected $treePaths = array();

	/**
	 * hashPathsRead
	 *
	 * Stores whether hash paths have been read
	 *
	 * @access protected
	 */
	protected $hashPathsRead = false;

	/**
	 * containingTag
	 *
	 * Stores the tag containing the changes in this commit
	 *
	 * @access protected
	 */
	protected $containingTag = null;

	/**
	 * containingTagRead
	 *
	 * Stores whether the containing tag has been looked up
	 *
	 * @access public
	 */
	protected $containingTagRead = false;

	/**
	 * parentsReferenced
	 *
	 * Stores whether the parents have been referenced into pointers
	 *
	 * @access private
	 */
	private $parentsReferenced = false;

	/**
	 * treeReferenced
	 *
	 * Stores whether the tree has been referenced into a pointer
	 *
	 * @access private
	 */
	private $treeReferenced = false;

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
	 * GetHash
	 *
	 * Gets the hash for this commit (overrides base)
	 *
	 * @access public
	 * @param boolean $abbreviate true to abbreviate hash
	 * @return string object hash
	 */
	public function GetHash($abbreviate = false)
	{
		if ($this->GetProject()->GetCompat() && $abbreviate) {
			// abbreviated hash is loaded as part of commit data in compat mode
			if (!$this->dataRead)
				$this->ReadData();
		}

		return parent::GetHash($abbreviate);
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

		if ($this->parentsReferenced)
			$this->DereferenceParents();

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

		if ($this->parentsReferenced)
			$this->DereferenceParents();

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

		if ($this->treeReferenced)
			$this->DereferenceTree();

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

		if ($trim > 0) {
			if (function_exists('mb_strimwidth')) {
				return mb_strimwidth($this->title, 0, $trim, '…');
			} else if (strlen($this->title) > $trim) {
				return substr($this->title, 0, $trim) . '…';
			}
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
	 * SearchComment
	 *
	 * Gets the lines of the comment matching the given pattern
	 *
	 * @access public
	 * @param string $pattern pattern to find
	 * @return array matching lines of comment
	 */
	public function SearchComment($pattern)
	{
		if (empty($pattern))
			return $this->GetComment();

		if (!$this->dataRead)
			$this->ReadData();

		return preg_grep('/' . $pattern . '/i', $this->comment);
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
	 * IsMergeCommit
	 *
	 * Returns whether this is a merge commit
	 *
	 * @access pubilc
	 * @return boolean true if merge commit
	 */
	public function IsMergeCommit()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return count($this->parents) > 1;
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

		$lines = null;

		if ($this->GetProject()->GetCompat()) {

			/* get data from git_rev_list */
			$exe = new GitPHP_GitExe($this->GetProject());
			$args = array();
			$args[] = '--header';
			$args[] = '--parents';
			$args[] = '--max-count=1';
			$args[] = '--abbrev-commit';
			$args[] = $this->hash;
			$ret = $exe->Execute(GIT_REV_LIST, $args);
			unset($exe);

			$lines = explode("\n", $ret);

			if (!isset($lines[0]))
				return;

			/* In case we returned something unexpected */
			$tok = strtok($lines[0], ' ');
			if ((strlen($tok) == 0) || (substr_compare($this->hash, $tok, 0, strlen($tok)) !== 0)) {
				return;
			}
			$this->abbreviatedHash = $tok;
			$this->abbreviatedHashLoaded = true;

			array_shift($lines);

		} else {
			
			$data = $this->GetProject()->GetObject($this->hash);
			if (empty($data))
				return;

			$lines = explode("\n", $data);

		}

		$linecount = count($lines);
		$i = 0;
		$encoding = null;

		/* Commit header */
		for ($i = 0; $i < $linecount; $i++) {
			$line = $lines[$i];
			if (preg_match('/^tree ([0-9a-fA-F]{40})$/', $line, $regs)) {
				/* Tree */
				try {
					$tree = $this->GetProject()->GetTree($regs[1]);
					if ($tree) {
						$tree->SetCommit($this);
						$this->tree = $tree;
					}
				} catch (Exception $e) {
				}
			} else if (preg_match('/^parent ([0-9a-fA-F]{40})$/', $line, $regs)) {
				/* Parent */
				try {
					$this->parents[] = $this->GetProject()->GetCommit($regs[1]);
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
			} else if (preg_match('/^encoding (.+)$/', $line, $regs)) {
				$gitEncoding = trim($regs[1]);
				if ((strlen($gitEncoding) > 0) && function_exists('mb_list_encodings')) {
					$supportedEncodings = mb_list_encodings();
					$encIdx = array_search(strtolower($gitEncoding), array_map('strtolower', $supportedEncodings));
					if ($encIdx !== false) {
						$encoding = $supportedEncodings[$encIdx];
					}
				}
				$encoding = trim($regs[1]);
			} else if (strlen($line) == 0) {
				break;
			}
		}
		
		/* Commit body */
		for ($i += 1; $i < $linecount; $i++) {
			$trimmed = trim($lines[$i]);

			if ((strlen($trimmed) > 0) && (strlen($encoding) > 0) && function_exists('mb_convert_encoding')) {
				$trimmed = mb_convert_encoding($trimmed, 'UTF-8', $encoding);
			}

			if (empty($this->title) && (strlen($trimmed) > 0))
				$this->title = $trimmed;
			if (!empty($this->title)) {
				if ((strlen($trimmed) > 0) || ($i < ($linecount-1)))
					$this->comment[] = $trimmed;
			}
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
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

		$projectRefs = $this->GetProject()->GetRefs('heads');

		foreach ($projectRefs as $ref) {
			if ($ref->GetHash() == $this->hash) {
				$heads[] = $ref;
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

		$projectRefs = $this->GetProject()->GetRefs('tags');

		foreach ($projectRefs as $ref) {
			if (($ref->GetType() == 'tag') || ($ref->GetType() == 'commit')) {
				if ($ref->GetCommit()->GetHash() === $this->hash) {
					$tags[] = $ref;
				}
			}
		}

		return $tags;
	}

	/**
	 * GetContainingTag
	 *
	 * Gets the tag that contains the changes in this commit
	 *
	 * @access public
	 * @return tag object
	 */
	public function GetContainingTag()
	{
		if (!$this->containingTagRead)
			$this->ReadContainingTag();

		return $this->containingTag;
	}

	/**
	 * ReadContainingTag
	 *
	 * Looks up the tag that contains the changes in this commit
	 *
	 * @access private
	 */
	public function ReadContainingTag()
	{
		$this->containingTagRead = true;

		$exe = new GitPHP_GitExe($this->GetProject());
		$args = array();
		$args[] = '--tags';
		$args[] = $this->hash;
		$revs = explode("\n", $exe->Execute(GIT_NAME_REV, $args));

		foreach ($revs as $revline) {
			if (preg_match('/^([0-9a-fA-F]{40})\s+tags\/(.+)(\^[0-9]+|\~[0-9]+)$/', $revline, $regs)) {
				if ($regs[1] == $this->hash) {
					$this->containingTag = $this->GetProject()->GetTag($regs[2]);
					break;
				}
			}
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
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
		return new GitPHP_TreeDiff($this->GetProject(), $this->hash);
	}

	/**
	 * PathToHash
	 *
	 * Given a filepath, get its hash
	 *
	 * @access public
	 * @param string $path path
	 * @return string hash
	 */
	public function PathToHash($path)
	{
		if (empty($path))
			return '';

		if (!$this->hashPathsRead)
			$this->ReadHashPaths();

		if (isset($this->blobPaths[$path])) {
			return $this->blobPaths[$path];
		}

		if (isset($this->treePaths[$path])) {
			return $this->treePaths[$path];
		}

		return '';
	}

	/**
	 * ReadHashPaths
	 *
	 * Read hash to path mappings
	 *
	 * @access private
	 */
	private function ReadHashPaths()
	{
		$this->hashPathsRead = true;

		if ($this->GetProject()->GetCompat()) {
			$this->ReadHashPathsGit();
		} else {
			$this->ReadHashPathsRaw($this->GetTree());
		}

		GitPHP_Cache::GetObjectCacheInstance()->Set($this->GetCacheKey(), $this);
	}

	/**
	 * ReadHashPathsGit
	 *
	 * Reads hash to path mappings using git exe
	 *
	 * @access private
	 */
	private function ReadHashPathsGit()
	{
		$exe = new GitPHP_GitExe($this->GetProject());

		$args = array();
		$args[] = '--full-name';
		$args[] = '-r';
		$args[] = '-t';
		$args[] = $this->hash;

		$lines = explode("\n", $exe->Execute(GIT_LS_TREE, $args));

		foreach ($lines as $line) {
			if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$/", $line, $regs)) {
				switch ($regs[2]) {
					case 'tree':
						$this->treePaths[trim($regs[4])] = $regs[3];
						break;
					case 'blob';
						$this->blobPaths[trim($regs[4])] = $regs[3];
						break;
				}
			}
		}
	}

	/**
	 * ReadHashPathsRaw
	 *
	 * Reads hash to path mappings using raw objects
	 *
	 * @access private
	 */
	private function ReadHashPathsRaw($tree)
	{
		if (!$tree) {
			return;
		}

		$contents = $tree->GetContents();

		foreach ($contents as $obj) {
			if ($obj instanceof GitPHP_Blob) {
				$hash = $obj->GetHash();
				$path = $obj->GetPath();
				$this->blobPaths[trim($path)] = $hash;
			} else if ($obj instanceof GitPHP_Tree) {
				$hash = $obj->GetHash();
				$path = $obj->GetPath();
				$this->treePaths[trim($path)] = $hash;
				$this->ReadHashPathsRaw($obj);
			}
		}
	}

	/**
	 * SearchFilenames
	 *
	 * Returns array of objects matching pattern
	 *
	 * @access public
	 * @param string $pattern pattern to find
	 * @return array array of objects
	 */
	public function SearchFilenames($pattern)
	{
		if (empty($pattern))
			return;

		if (!$this->hashPathsRead)
			$this->ReadHashPaths();

		$results = array();

		foreach ($this->treePaths as $path => $hash) {
			if (preg_match('/' . preg_quote($pattern, '/') . '/i', $path)) {
				$obj = $this->GetProject()->GetTree($hash);
				$obj->SetCommit($this);
				$results[$path] = $obj;
			}
		}

		foreach ($this->blobPaths as $path => $hash) {
			if (preg_match('/' . preg_quote($pattern, '/') . '/i', $path)) {
				$obj = $this->GetProject()->GetBlob($hash);
				$obj->SetCommit($this);
				$results[$path] = $obj;
			}
		}

		ksort($results);

		return $results;
	}

	/**
	 * SearchFileContents
	 *
	 * Searches for a pattern in file contents
	 *
	 * @access public
	 * @param string $pattern pattern to search for
	 * @return array multidimensional array of results
	 */
	public function SearchFileContents($pattern)
	{
		if (empty($pattern))
			return;

		$exe = new GitPHP_GitExe($this->GetProject());

		$args = array();
		$args[] = '-I';
		$args[] = '--full-name';
		$args[] = '--ignore-case';
		$args[] = '-n';
		$args[] = '-e';
		$args[] = '\'' . preg_quote($pattern) . '\'';
		$args[] = $this->hash;

		$lines = explode("\n", $exe->Execute(GIT_GREP, $args));

		$results = array();

		foreach ($lines as $line) {
			if (preg_match('/^[^:]+:([^:]+):([0-9]+):(.+)$/', $line, $regs)) {
				if (!isset($results[$regs[1]]['object'])) {
					$hash = $this->PathToHash($regs[1]);
					if (!empty($hash)) {
						$obj = $this->GetProject()->GetBlob($hash);
						$obj->SetCommit($this);
						$results[$regs[1]]['object'] = $obj;
					}
				}
				$results[$regs[1]]['lines'][(int)($regs[2])] = $regs[3];
			}
		}

		return $results;
	}

	/**
	 * SearchFiles
	 *
	 * Searches filenames and file contents for a pattern
	 *
	 * @access public
	 * @param string $pattern pattern to search
	 * @param integer $count number of results to get
	 * @param integer $skip number of results to skip
	 * @return array array of results
	 */
	public function SearchFiles($pattern, $count = 100, $skip = 0)
	{
		if (empty($pattern))
			return;

		$grepresults = $this->SearchFileContents($pattern);

		$nameresults = $this->SearchFilenames($pattern);

		/* Merge the results together */
		foreach ($nameresults as $path => $obj) {
			if (!isset($grepresults[$path]['object'])) {
				$grepresults[$path]['object'] = $obj;
			}
		}

		ksort($grepresults);

		return array_slice($grepresults, $skip, $count, true);
	}

	/**
	 * ReferenceParents
	 *
	 * Turns the list of parents into reference pointers
	 *
	 * @access private
	 */
	private function ReferenceParents()
	{
		if ($this->parentsReferenced)
			return;

		if ((!isset($this->parents)) || (count($this->parents) < 1))
			return;

		for ($i = 0; $i < count($this->parents); $i++) {
			$this->parents[$i] = $this->parents[$i]->GetHash();
		}

		$this->parentsReferenced = true;
	}

	/**
	 * DereferenceParents
	 *
	 * Turns the list of parent pointers back into objects
	 *
	 * @access private
	 */
	private function DereferenceParents()
	{
		if (!$this->parentsReferenced)
			return;

		if ((!$this->parents) || (count($this->parents) < 1))
			return;

		for ($i = 0; $i < count($this->parents); $i++) {
			$this->parents[$i] = $this->GetProject()->GetCommit($this->parents[$i]);
		}

		$this->parentsReferenced = false;
	}

	/**
	 * ReferenceTree
	 *
	 * Turns the tree into a reference pointer
	 *
	 * @access private
	 */
	private function ReferenceTree()
	{
		if ($this->treeReferenced)
			return;

		if (!$this->tree)
			return;

		$this->tree = $this->tree->GetHash();

		$this->treeReferenced = true;
	}

	/**
	 * DereferenceTree
	 *
	 * Turns the tree pointer back into an object
	 *
	 * @access private
	 */
	private function DereferenceTree()
	{
		if (!$this->treeReferenced)
			return;

		if (empty($this->tree))
			return;

		$this->tree = $this->GetProject()->GetTree($this->tree);

		if ($this->tree)
			$this->tree->SetCommit($this);

		$this->treeReferenced = false;
	}

	/**
	 * __sleep
	 *
	 * Called to prepare the object for serialization
	 *
	 * @access public
	 * @return array list of properties to serialize
	 */
	public function __sleep()
	{
		if (!$this->parentsReferenced)
			$this->ReferenceParents();

		if (!$this->treeReferenced)
			$this->ReferenceTree();

		$properties = array('dataRead', 'parents', 'tree', 'author', 'authorEpoch', 'authorTimezone', 'committer', 'committerEpoch', 'committerTimezone', 'title', 'comment', 'readTree', 'blobPaths', 'treePaths', 'hashPathsRead', 'parentsReferenced', 'treeReferenced');
		return array_merge($properties, parent::__sleep());
	}

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key to use for this object
	 *
	 * @access public
	 * @return string cache key
	 */
	public function GetCacheKey()
	{
		$key = parent::GetCacheKey();
		if (!empty($key))
			$key .= '|';

		$key .= 'commit|' . $this->hash;

		return $key;
	}

	/**
	 * CompareAge
	 *
	 * Compares two commits by age
	 *
	 * @access public
	 * @static
	 * @param mixed $a first commit
	 * @param mixed $b second commit
	 * @return integer comparison result
	 */
	public static function CompareAge($a, $b)
	{
		if ($a->GetAge() === $b->GetAge()) {
			// fall back on author epoch
			return GitPHP_Commit::CompareAuthorEpoch($a, $b);
		}
		return ($a->GetAge() < $b->GetAge() ? -1 : 1);
	}

	/**
	 * CompareAuthorEpoch
	 *
	 * Compares two commits by author epoch
	 *
	 * @access public
	 * @static
	 * @param mixed $a first commit
	 * @param mixed $b second commit
	 * @return integer comparison result
	 */
	public static function CompareAuthorEpoch($a, $b)
	{
		if ($a->GetAuthorEpoch() === $b->GetAuthorEpoch()) {
			return 0;
		}
		return ($a->GetAuthorEpoch() > $b->GetAuthorEpoch() ? -1 : 1);
	}

}
