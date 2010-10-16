<?php
/**
 * GitPHP ProjectListBase
 *
 * Base class that all projectlist classes extend
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

define('GITPHP_SORT_PROJECT', 'project');
define('GITPHP_SORT_DESCRIPTION', 'descr');
define('GITPHP_SORT_OWNER', 'owner');
define('GITPHP_SORT_AGE', 'age');

/**
 * ProjectListBase class
 *
 * @package GitPHP
 * @subpackage Git
 * @abstract
 */
abstract class GitPHP_ProjectListBase implements Iterator
{
	/**
	 * projects
	 *
	 * Stores array of projects internally
	 *
	 * @access protected
	 */
	protected $projects;

	/**
	 * projectConfig
	 *
	 * Stores the project configuration internally
	 *
	 * @access protected
	 */
	protected $projectConfig = null;

	/**
	 * __construct
	 *
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct()
	{
		$this->projects = array();
		$this->PopulateProjects();
		$this->Sort();
	}

	/**
	 * HasProject
	 *
	 * Test if the projectlist contains
	 * the given project
	 *
	 * @access public
	 * @return boolean true if project exists in list
	 * @param string $project the project string to find
	 */
	public function HasProject($project)
	{
		return ($this->GetProject($project) !== null);
	}

	/**
	 * GetProject
	 *
	 * Gets a particular project
	 *
	 * @access public
	 * @return mixed project object or null
	 * @param string $project the project to find
	 */
	public function GetProject($project)
	{
		if (empty($project))
			return null;

		if (isset($this->projects[$project]))
			return $this->projects[$project];

		return null;
	}

	/**
	 * GetConfig
	 *
	 * Gets the config defined for this ProjectList
	 *
	 * @access public
	 */
	public function GetConfig()
	{
		return $this->projectConfig;
	}

	/**
	 * PopulateProjects
	 *
	 * Populates the internal list of projects
	 *
	 * @access protected
	 */
	abstract protected function PopulateProjects();

	/**
	 * rewind
	 *
	 * Rewinds the iterator
	 */
	function rewind()
	{
		return reset($this->projects);
	}

	/**
	 * current
	 *
	 * Returns the current element in the array
	 */
	function current()
	{
		return current($this->projects);
	}

	/**
	 * key
	 *
	 * Returns the current key
	 */
	function key()
	{
		return key($this->projects);
	}

	/**
	 * next
	 * 
	 * Advance the pointer
	 */
	function next()
	{
		return next($this->projects);
	}

	/**
	 * valid
	 *
	 * Test for a valid pointer
	 */
	function valid()
	{
		return key($this->projects) !== null;
	}

	/**
	 * Sort
	 *
	 * Sorts the project list
	 *
	 * @access public
	 * @param string $sortBy sort method
	 */
	public function Sort($sortBy = GITPHP_SORT_PROJECT)
	{
		switch ($sortBy) {
			case GITPHP_SORT_DESCRIPTION:
				uasort($this->projects, array('GitPHP_Project', 'CompareDescription'));
				break;
			case GITPHP_SORT_OWNER:
				uasort($this->projects, array('GitPHP_Project', 'CompareOwner'));
				break;
			case GITPHP_SORT_AGE:
				uasort($this->projects, array('GitPHP_Project', 'CompareAge'));
				break;
			case GITPHP_SORT_PROJECT:
			default:
				uasort($this->projects, array('GitPHP_Project', 'CompareProject'));
				break;
		}
	}

	/**
	 * Count
	 *
	 * Gets the count of projects
	 *
	 * @access public
	 * @return integer number of projects
	 */
	public function Count()
	{
		return count($this->projects);
	}

}
