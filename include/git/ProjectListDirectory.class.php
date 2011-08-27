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
			throw new Exception(sprintf(__('%1$s is not a directory'), $projectDir));
		}

		$this->projectDir = GitPHP_Util::AddSlash($projectDir);

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
		$key = 'projectdir|' . $this->projectDir . '|projectlist|directory';
		$cached = GitPHP_Cache::GetObjectCacheInstance()->Get($key);
		if ($cached && (count($cached) > 0)) {
			foreach ($cached as $proj) {
				$this->AddProject($proj);
			}
			GitPHP_Log::GetInstance()->Log('Loaded ' . count($this->projects) . ' projects from cache');
			return;
		}

		$this->RecurseDir($this->projectDir);

		if (count($this->projects) > 0) {
			$projects = array();
			foreach ($this->projects as $proj) {
				$projects[] = $proj->GetProject();;
			}
			GitPHP_Cache::GetObjectCacheInstance()->Set($key, $projects, GitPHP_Config::GetInstance()->GetValue('cachelifetime', 3600));
		}
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
		if (!(is_dir($dir) && is_readable($dir)))
			return;

		GitPHP_Log::GetInstance()->Log(sprintf('Searching directory %1$s', $dir));

		if ($dh = opendir($dir)) {
			$trimlen = strlen($this->projectDir) + 1;
			while (($file = readdir($dh)) !== false) {
				$fullPath = $dir . '/' . $file;
				if ((strpos($file, '.') !== 0) && is_dir($fullPath)) {
					if (is_file($fullPath . '/HEAD')) {
						GitPHP_Log::GetInstance()->Log(sprintf('Found project %1$s', $fullPath));
						$projectPath = substr($fullPath, $trimlen);
						$this->AddProject($projectPath);
					} else {
						$this->RecurseDir($fullPath);
					}
				} else {
					GitPHP_Log::GetInstance()->Log(sprintf('Skipping %1$s', $fullPath));
				}
			}
			closedir($dh);
		}
	}

	/**
	 * AddProject
	 *
	 * Add project to collection
	 *
	 * @access private
	 */
	private function AddProject($projectPath)
	{
		try {
			$proj = new GitPHP_Project($this->projectDir, $projectPath);
			$category = trim(dirname($projectPath));
			if (!(empty($category) || (strpos($category, '.') === 0))) {
				$proj->SetCategory($category);
			}
			if ((!GitPHP_Config::GetInstance()->GetValue('exportedonly', false)) || $proj->GetDaemonEnabled()) {
				$this->projects[$projectPath] = $proj;
			}
		} catch (Exception $e) {
			GitPHP_Log::GetInstance()->Log($e->getMessage());
		}
	}

}
