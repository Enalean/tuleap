<?php
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

require_once(GITPHP_INCLUDEDIR . 'Config.class.php');
require_once(GITPHP_GITOBJECTDIR . 'ProjectListBase.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

/**
 * ProjectListFile class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListFile extends GitPHP_ProjectListBase
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
			throw new Exception(GitPHP_Resource::GetInstance()->Format('%1$s is not a file', $projectFile));
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
			throw new Exception(GitPHP_Resource::GetInstance()->Format('Failed to open project list file %1$s', $this->projectConfig));
		}

		$projectRoot = GitPHP_Config::GetInstance()->GetValue('projectroot');

		while (!feof($fp) && ($line = fgets($fp))) {
			$pinfo = explode(' ', $line);
			$ppath = trim($pinfo[0]);
			if (is_file($projectRoot . $ppath . '/HEAD')) {
				try {
					$projObj = new GitPHP_Project($ppath);
					if (isset($pinfo[1])) {
						$projOwner = trim($pinfo[1]);
						if (!empty($projOwner)) {
							$projObj->SetOwner($projOwner);
						}
					}
					$this->projects[] = $projObj;
				} catch (Exception $e) {
				}
			}
		}

		fclose($fp);
	}

}
