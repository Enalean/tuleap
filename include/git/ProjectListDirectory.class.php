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
require_once(GITPHP_GITOBJECTDIR . 'ProjectListBase.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

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
			$trimlen = strlen(GitPHP_Config::GetInstance()->GetValue('projectroot')) + 1;
			while (($file = readdir($dh)) !== false) {
				$fullPath = $dir . '/' . $file;
				if ((strpos($file, '.') !== 0) && is_dir($fullPath)) {
					if (is_file($fullPath . '/HEAD')) {
						$projectPath = substr($fullPath, $trimlen);
						try {
							$proj = new GitPHP_Project($projectPath);
							if ($proj->GetDaemonEnabled()) {
								$this->projects[] = $proj;
							}
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
