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
		foreach ($this->projectConfig as $projData) {
			if (is_array($projData)) {
				if (isset($projData['project'])) {
					try {
						$projObj = new GitPHP_Project($projData['project']);
						if (isset($projData['category']) && is_string($projData['category'])) {
							$projObj->SetCategory($projData['category']);
						}
						if (isset($projData['owner']) && is_string($projData['owner'])) {
							$projObj->SetOwner($projData['owner']);
						}
						if (isset($projData['description']) && is_string($projData['description'])) {
							$projObj->SetDescription($projData['description']);
						}
						if (isset($projData['cloneurl']) && is_string($projData['cloneurl'])) {
							$projObj->SetCloneUrl($projData['cloneurl']);
						}
						if (isset($projData['pushurl']) && is_string($projData['pushurl'])) {
							$projObj->SetPushUrl($projData['pushurl']);
						}
						$this->projects[] = $projObj;
					} catch (Exception $e) {
					}
				}
			}
		}
	}

}
