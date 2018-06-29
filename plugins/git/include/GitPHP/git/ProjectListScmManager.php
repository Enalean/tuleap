<?php

namespace Tuleap\Git\GitPHP;

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

/**
 * ProjectListScmManager class
 *
 * @package GitPHP
 * @subpackage Git
 */
class ProjectListScmManager extends ProjectListBase
{
	/**
	 * __construct
	 *
	 * constructor
	 *
	 * @param string $projectFile file to read
	 * @throws \Exception if parameter is not a readable file
	 * @access public
	 */
	public function __construct($projectFile)
	{
		if (!(is_string($projectFile) && is_file($projectFile))) {
			throw new \Exception(sprintf(__('%1$s is not a file'), $projectFile));
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
		$projectRoot = Util::AddSlash(Config::GetInstance()->GetValue('projectroot'));

		$use_errors = libxml_use_internal_errors(true);

		$xml = simplexml_load_file($this->projectConfig);

		libxml_clear_errors();
		libxml_use_internal_errors($use_errors);

		if (!$xml) {
			throw new \Exception(sprintf('Could not load SCM manager config %1$s', $this->projectConfig));
		}

		foreach ($xml->repositories->repository as $repository) {

			if ($repository->type != 'git') {
				Log::GetInstance()->Log(sprintf('%1$s is not a git project', $repository->name));
				continue;
			}

			if ($repository->public != 'true') {
				Log::GetInstance()->Log(sprintf('%1$s is not public', $repository->name));
				continue;
			}

			$projName = trim($repository->name);
			if (empty($projName))
				continue;

			if (is_file($projectRoot . $projName . '/HEAD')) {
				try {
					$projObj = new Project($projectRoot, $projName);
					$this->projects[$projName] = $projObj;
				} catch (\Exception $e) {
					Log::GetInstance()->Log($e->getMessage());
				}
			} else {
				Log::GetInstance()->Log(sprintf('%1$s is not a git project', $projName));
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
