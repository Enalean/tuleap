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

/**
 * ProjectListBase class
 *
 * @package GitPHP
 * @subpackage Git
 * @abstract
 */
abstract class GitPHP_ProjectListBase
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

		foreach ($this->projects as $projObj) {
			if ($projObj->GetProject() == $project) {
				return $projObj;
			}
		}

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

}
