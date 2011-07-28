<?php
/**
 * GitPHP ProjectListScmManager
 *
 * Lists all projects in an scm-manager config file
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_INCLUDEDIR . 'Config.class.php');
require_once(GITPHP_GITOBJECTDIR . 'ProjectListBase.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

/**
 * ProjectListScmManager class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListScmManager extends GitPHP_ProjectListBase
{
	/**
	 * __construct
	 *
	 * constructor
	 *
	 * @param string $projectFile file to read
	 * @throws Exception if parameter is not a readable file
	 * @access public
	 */
	public function __construct($projectFile)
	{
		if (!(is_string($projectFile) && is_file($projectFile))) {
			throw new Exception(sprintf(__('%1$s is not a file'), $projectFile));
		}

		$this->projectConfig = $projectFile;

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

		$use_errors = libxml_use_internal_errors(true);

		$xml = simplexml_load_file($this->projectConfig);

		libxml_clear_errors();
		libxml_use_internal_errors($use_errors);

		if (!$xml) {
			throw new Exception(sprintf('Could not load SCM manager config %1$s', $this->projectConfig));
		}

		foreach ($xml->repositories->repository as $repository) {

			if ($repository->type != 'git') {
				GitPHP_Log::GetInstance()->Log(sprintf('%1$s is not a git project', $repository->name));
				continue;
			}

			if ($repository->public != 'true') {
				GitPHP_Log::GetInstance()->Log(sprintf('%1$s is not public', $repository->name));
				continue;
			}

			$projName = trim($repository->name);
			if (empty($projName))
				continue;

			if (is_file($projectRoot . $projName . '/HEAD')) {
				try {
					$projObj = new GitPHP_Project($projectRoot, $projName);
					$projOwner = trim($repository->contact);
					if (!empty($projOwner)) {
						$projObj->SetOwner($projOwner);
					}
					$projDesc = trim($repository->description);
					if (!empty($projDesc)) {
						$projObj->SetDescription($projDesc);
					}
					$this->projects[$projName] = $projObj;
				} catch (Exception $e) {
					GitPHP_Log::GetInstance()->Log($e->getMessage());
				}
			} else {
				GitPHP_Log::GetInstance()->Log(sprintf('%1$s is not a git project', $projName));
			}
		}
	}

	/**
	 * IsSCMManager
	 *
	 * Tests if this file is an SCM manager config file
	 *
	 * @access protected
	 * @returns true if file is an SCM manager config
	 */
	public static function IsSCMManager($file)
	{
		if (empty($file))
			return false;

		if (!(is_string($file) && is_file($file)))
			return false;

		$use_errors = libxml_use_internal_errors(true);

		$xml = simplexml_load_file($file);

		libxml_clear_errors();
		libxml_use_internal_errors($use_errors);

		if (!$xml)
			return false;

		if ($xml->getName() !== 'repository-db')
			return false;

		return true;
	}

}
