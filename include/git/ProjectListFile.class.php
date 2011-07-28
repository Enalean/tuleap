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
		if (!($fp = fopen($this->projectConfig, 'r'))) {
			throw new Exception(sprintf(__('Failed to open project list file %1$s'), $this->projectConfig));
		}

		$projectRoot = GitPHP_Util::AddSlash(GitPHP_Config::GetInstance()->GetValue('projectroot'));

		while (!feof($fp) && ($line = fgets($fp))) {
			if (preg_match('/^([^\s]+)(\s.+)?$/', $line, $regs)) {
				if (is_file($projectRoot . $regs[1] . '/HEAD')) {
					try {
						$projObj = new GitPHP_Project($projectRoot, $regs[1]);
						if (isset($regs[2]) && !empty($regs[2])) {
							$projOwner = trim($regs[2]);
							if (!empty($projOwner)) {
								$projObj->SetOwner($projOwner);
							}
						}
						$this->projects[$regs[1]] = $projObj;
					} catch (Exception $e) {
						GitPHP_Log::GetInstance()->Log($e->getMessage());
					}
				} else {
					GitPHP_Log::GetInstance()->Log(sprintf('%1$s is not a git project', $projectRoot . $regs[1]));
				}
			}
		}

		fclose($fp);
	}

}
