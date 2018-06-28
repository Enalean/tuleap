<?php
/**
 * GitPHP GitObject
 *
 * Base class for all hash objects in a git repository
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * Git Object class
 *
 * @abstract
 * @package GitPHP
 * @subpackage Git
 */
abstract class GitPHP_GitObject
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
	 * hash
	 *
	 * Stores the hash of the object internally
	 *
	 * @access protected
	 */
	protected $hash;

	/**
	 * projectReferenced
	 *
	 * Stores whether the project has been referenced into a pointer
	 *
	 * @access protected
	 */
	protected $projectReferenced = false;

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
		$this->project = $project;
		$this->SetHash($hash);
	}

	/**
	 * GetProject
	 *
	 * Gets the project
	 *
	 * @access public
	 * @return mixed project
	 */
	public function GetProject()
	{
		if ($this->projectReferenced)
			$this->DereferenceProject();

		return $this->project;
	}

	/**
	 * GetHash
	 *
	 * Gets the hash
	 *
	 * @access public
	 * @return string object hash
	 */
	public function GetHash()
	{
		return $this->hash;
	}

	/**
	 * SetHash
	 *
	 * Attempts to set the hash of this object
	 *
	 * @param string $hash the hash to set
	 * @throws Exception on invalid hash
	 * @access protected
	 */
	protected function SetHash($hash)
	{
		if (!(preg_match('/[0-9a-f]{40}/i', $hash))) {
			throw new Exception(sprintf(__('Invalid hash %1$s'), $hash));
		}
		$this->hash = $hash;
	}

	/**
	 * ReferenceProject
	 *
	 * Turns the project object into a reference pointer
	 *
	 * @access private
	 */
	private function ReferenceProject()
	{
		if ($this->projectReferenced)
			return;

		$this->project = $this->project->GetProject();

		$this->projectReferenced = true;
	}

	/**
	 * DereferenceProject
	 *
	 * Turns the project reference pointer back into an object
	 *
	 * @access private
	 */
	private function DereferenceProject()
	{
		if (!$this->projectReferenced)
			return;

		$this->project = GitPHP_ProjectList::GetInstance()->GetProject($this->project);

		$this->projectReferenced = false;
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
		if (!$this->projectReferenced)
			$this->ReferenceProject();

		return array('project', 'hash', 'projectReferenced');
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
		$projKey = 'project|';
		if ($this->projectReferenced)
			$projKey .= $this->project;
		else
			$projKey .= $this->project->GetProject();

		return $projKey;
	}

}
