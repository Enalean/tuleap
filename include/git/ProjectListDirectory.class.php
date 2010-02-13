<?php
/**
 * GitPHP ProjectListDirectory
 *
 * Lists all projects in a given directory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_INCLUDEDIR . 'Config.class.php');
require_once(GITPHP_INCLUDEDIR . 'git/ProjectListBase.class.php');
require_once(GITPHP_INCLUDEDIR . 'git/Project.class.php');

/**
 * ProjectListDirectory class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListDirectory extends GitPHP_ProjectListBase
{
	
	/**
	 * projectDir
	 *
	 * Stores projectlist directory internally
	 *
	 * @access protected
	 */
	protected $projectDir;

	/**
	 * __construct
	 *
	 * constructor
	 *
	 * @param string $projectDir directory to search
	 * @throws Exception if parameter is not a directory
	 * @access public
	 */
	public function __construct($projectDir)
	{
		if (!is_dir($projectDir)) {
			throw new Exception($projectDir . ' is not a directory.');
		}

		$this->projectDir = $projectDir;

		parent::__construct();
	}

	/**
	 * PopulateProjects
	 *
	 * Populates the internal list of projects
	 *
	 * @access protected
	 */
	protected function PopulateProjects()
	{
		$this->RecurseDir($this->projectDir);
	}

	/**
	 * RecurseDir
	 *
	 * Recursively searches for projects
	 *
	 * @param string $dir directory to recurse into
	 */
	private function RecurseDir($dir)
	{
		if (!is_dir($dir))
			return;

		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				$fullPath = $dir . '/' . $file;
				if ((strpos($file, '.') !== 0) && is_dir($fullPath)) {
					if (is_file($fullPath . '/HEAD')) {
						$projectPath = substr($fullPath, strlen(GitPHP_Config::GetInstance()->GetValue('projectroot')) + 1);
						try {
							$this->projects[] = new GitPHP_Project($projectPath);
						} catch (Exception $e) {
						}
					} else {
						$this->RecurseDir($fullPath);
					}
				}
			}
			closedir($dh);
		}
	}

}
