<?php

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP ProjectListFile
 *
 * Lists all projects in a given file
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

/**
 * ProjectListFile class
 *
 * @package GitPHP
 * @subpackage Git
 */
class ProjectListFile extends ProjectListBase
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
		if (!($fp = fopen($this->projectConfig, 'r'))) {
			throw new \Exception(sprintf(__('Failed to open project list file %1$s'), $this->projectConfig));
		}

		$projectRoot = Util::AddSlash(Config::GetInstance()->GetValue('projectroot'));

		while (!feof($fp) && ($line = fgets($fp))) {
			if (preg_match('/^([^\s]+)(\s.+)?$/', $line, $regs)) {
				if (is_file($projectRoot . $regs[1] . '/HEAD')) {
					try {
						$projObj = new Project($projectRoot, $regs[1]);
						$this->projects[$regs[1]] = $projObj;
					} catch (\Exception $e) {
						Log::GetInstance()->Log($e->getMessage());
					}
				} else {
					Log::GetInstance()->Log(sprintf('%1$s is not a git project', $projectRoot . $regs[1]));
				}
			}
		}

		fclose($fp);
	}

}
