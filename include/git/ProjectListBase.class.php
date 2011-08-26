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
	 * projectSettings
	 *
	 * Stores the project settings internally
	 *
	 * @access protected
	 */
	protected $projectSettings = null;

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
	 * GetSettings
	 *
	 * Gets the settings applied to this projectlist
	 *
	 * @access public
	 */
	public function GetSettings()
	{
		return $this->projectSettings;
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

	/**
	 * Filter
	 *
	 * Returns a filtered list of projects
	 *
	 * @access public
	 * @param string $filter filter pattern
	 * @return array array of filtered projects
	 */
	public function Filter($pattern = null)
	{
		if (empty($pattern))
			return $this->projects;

		$matches = array();

		foreach ($this->projects as $proj) {
			if ((stripos($proj->GetProject(), $pattern) !== false) ||
			    (stripos($proj->GetDescription(), $pattern) !== false) ||
			    (stripos($proj->GetOwner(), $pattern) !== false)) {
			    	$matches[] = $proj;
			}
		}

		return $matches;
	}

	/**
	 * ApplyProjectSettings
	 *
	 * Applies override settings for a project
	 *
	 * @access protected
	 * @param string $project the project path
	 * @param array $projData project data array
	 */
	protected function ApplyProjectSettings($project, $projData)
	{
		if (empty($project)) {
			if (isset($projData['project']) && !empty($projData['project']))
				$project = $projData['project'];
			else
				return;
		}

		$projectObj = $this->GetProject($project);
		if (!$projectObj)
			return;

		if (isset($projData['category']) && is_string($projData['category'])) {
			$projectObj->SetCategory($projData['category']);
		}
		if (isset($projData['owner']) && is_string($projData['owner'])) {
			$projectObj->SetOwner($projData['owner']);
		}
		if (isset($projData['description']) && is_string($projData['description'])) {
			$projectObj->SetDescription($projData['description']);
		}
		if (isset($projData['cloneurl']) && is_string($projData['cloneurl'])) {
			$projectObj->SetCloneUrl($projData['cloneurl']);
		}
		if (isset($projData['pushurl']) && is_string($projData['pushurl'])) {
			$projectObj->SetPushUrl($projData['pushurl']);
		}
		if (isset($projData['bugpattern']) && is_string($projData['bugpattern'])) {
			$projectObj->SetBugPattern($projData['bugpattern']);
		}
		if (isset($projData['bugurl']) && is_string($projData['bugurl'])) {
			$projectObj->SetBugUrl($projData['bugurl']);
		}
		if (isset($projData['compat'])) {
			$projectObj->SetCompat($projData['compat']);
		}
		if (isset($projData['website']) && is_string($projData['website'])) {
			$projectObj->SetWebsite($projData['website']);
		}
	}

	/**
	 * ApplySettings
	 *
	 * Applies a list of settings to the project list
	 *
	 * @access protected
	 * @param array $settings the array of settings
	 */
	public function ApplySettings($settings)
	{
		if ((!$settings) || (count($settings) < 1))
			return;

		foreach ($settings as $proj => $setting) {
			$this->ApplyProjectSettings($proj, $setting);
		}

		$this->projectSettings = $settings;
	}

}
