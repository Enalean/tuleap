<?php
/**
 * GitPHP Tree Diff
 *
 * Represents differences between two commit trees
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'FileDiff.class.php');

/**
 * TreeDiff class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_TreeDiff implements Iterator
{
	
	/**
	 * fromHash
	 *
	 * Stores the from hash
	 *
	 * @access protected
	 */
	protected $fromHash;

	/**
	 * toHash
	 *
	 * Stores the to hash
	 *
	 * @access protected
	 */
	protected $toHash;

	/**
	 * renames
	 *
	 * Stores whether to detect renames
	 *
	 * @access protected
	 */
	protected $renames;

	/**
	 * project
	 *
	 * Stores the project
	 *
	 * @access protected
	 */
	protected $project;

	/**
	 * fileDiffs
	 *
	 * Stores the individual file diffs
	 *
	 * @access protected
	 */
	protected $fileDiffs = array();

	/**
	 * dataRead
	 *
	 * Stores whether data has been read
	 *
	 * @access protected
	 */
	protected $dataRead = false;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 * @param mixed $project project
	 * @param string $toHash to commit hash
	 * @param string $fromHash from commit hash
	 * @param boolean $renames whether to detect file renames
	 * @return mixed TreeDiff object
	 * @throws Exception exception on invalid parameters
	 */
	public function __construct($project, $toHash, $fromHash = '', $renames = false)
	{
		$this->project = $project;

		$toCommit = $this->project->GetCommit($toHash);
		$this->toHash = $toHash;

		if (empty($fromHash)) {
			$parent = $toCommit->GetParent();
			if ($parent) {
				$this->fromHash = $parent->GetHash();
			}
		} else {
			$fromCommit = $this->project->GetCommit($fromHash);
			$this->fromHash = $fromHash;
		}

		$this->renames = $renames;
	}

	/**
	 * ReadData
	 *
	 * Reads the tree diff data
	 *
	 * @access private
	 */
	private function ReadData()
	{
		$this->dataRead = true;

		$this->fileDiffs = array();

		$exe = new GitPHP_GitExe($this->project);

		$args = array();

		$args[] = '-r';
		if ($this->renames)
			$args[] = '-M';

		if (empty($this->fromHash))
			$args[] = '--root';
		else
			$args[] = $this->fromHash;

		$args[] = $this->toHash;

		$diffTreeLines = explode("\n", $exe->Execute(GIT_DIFF_TREE, $args));
		foreach ($diffTreeLines as $line) {
			$trimmed = trim($line);
			if ((strlen($trimmed) > 0) && (substr_compare($trimmed, ':', 0, 1) === 0)) {
				try {
					$this->fileDiffs[] = new GitPHP_FileDiff($this->project, $trimmed);
				} catch (Exception $e) {
				}
			}
		}

		unset($exe);
	}

	/**
	 * GetFromHash
	 *
	 * Gets the from hash for this treediff
	 *
	 * @access public
	 * @return string from hash
	 */
	public function GetFromHash()
	{
		return $this->fromHash;
	}

	/**
	 * GetToHash
	 *
	 * Gets the to hash for this treediff
	 *
	 * @access public
	 * @return string to hash
	 */
	public function GetToHash()
	{
		return $this->toHash;
	}

	/**
	 * GetRenames
	 *
	 * Get whether this treediff is set to detect renames
	 *
	 * @access public
	 * @return boolean true if renames will be detected
	 */
	public function GetRenames()
	{
		return $this->renames;
	}

	/**
	 * SetRenames
	 *
	 * Set whether this treediff is set to detect renames
	 *
	 * @access public
	 * @param boolean $renames whether to detect renames
	 */
	public function SetRenames($renames)
	{
		if ($renames == $this->renames)
			return;

		$this->renames = $renames;
		$this->dataRead = false;
	}

	/**
	 * rewind
	 *
	 * Rewinds the iterator
	 */
	function rewind()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return reset($this->fileDiffs);
	}

	/**
	 * current
	 *
	 * Returns the current element in the array
	 */
	function current()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return current($this->fileDiffs);
	}

	/**
	 * key
	 *
	 * Returns the current key
	 */
	function key()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return key($this->fileDiffs);
	}

	/**
	 * next
	 *
	 * Advance the pointer
	 */
	function next()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return next($this->fileDiffs);
	}

	/**
	 * valid
	 *
	 * Test for a valid pointer
	 */
	function valid()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return key($this->fileDiffs) !== null;
	}

	/**
	 * Count
	 *
	 * Gets the number of file changes in this treediff
	 *
	 * @access public
	 * @return integer count of file changes
	 */
	public function Count()
	{
		if (!$this->dataRead)
			$this->ReadData();

		return count($this->fileDiffs);
	}

}
