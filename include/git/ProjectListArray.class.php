<?php
/**
 * GitPHP ProjectListArray
 *
 * Lists all projects in a multidimensional array
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'ProjectListBase.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

/**
 * ProjectListArray class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListArray extends GitPHP_ProjectListBase
{

	/**
	 * __construct
	 *
	 * constructor
	 *
	 * @param mixed $projectArray array to read
	 * @throws Exception if parameter is not an array
	 * @access public
	 */
	public function __construct($projectArray)
	{
		if (!is_array($projectArray)) {
			throw new Exception('An array of projects is required');
		}

		$this->projectConfig = $projectArray;

		parent::__construct();
	}

	/**
	 * PopulateProjects
	 *
	 * Populates the internal list of projects
	 *
	 * @access protected
	 * @throws Exception if file cannot be read
	 */
	protected function PopulateProjects()
	{
		$projectRoot = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('projectroot'));

		foreach ($this->projectConfig as $proj => $projData) {
			try {
				if (is_string($projData)) {
					// Just flat array of project paths
					$projObj = new GitPHP_Project($projectRoot, $projData);
					$this->projects[$projData] = $projObj;
				} else if (is_array($projData)) {
					if (is_string($proj) && !empty($proj)) {
						// Project key pointing to data array
						$projObj = new GitPHP_Project($projectRoot, $proj);
						$this->projects[$proj] = $projObj;
						$this->ApplyProjectSettings($proj, $projData);
					} else if (isset($projData['project'])) {
						// List of data arrays with projects inside
						$projObj = new GitPHP_Project($projectRoot, $projData['project']);
						$this->projects[$projData['project']] = $projObj;
						$this->ApplyProjectSettings(null, $projData);
					}
				}
			} catch (Exception $e) {
				GitPHP_Log::GetInstance()->Log($e->getMessage());
			}
		}
	}

}
