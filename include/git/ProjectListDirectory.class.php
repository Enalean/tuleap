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
		$stat = stat(GITPHP_CACHE.'ProjectList.dat');
		if ($stat !== FALSE) {
			$cache_life = '180';  //caching time, in seconds
			$filemtime = max($stat['mtime'], $stat['ctime']);
			
			if  (time() - $filemtime >= $cache_life) {
				GitPHP_Log::GetInstance()->Log('ProjectListDirCache: expired, reloading...');
			} else {

				$data = file_get_contents(GITPHP_CACHE.'ProjectList.dat');
				$projects = unserialize($data);
				if (count($projects) > 0) {
					GitPHP_Log::GetInstance()->Log('loaded '.count($projects).' projects from cache');
					$this->projects = $projects;
					return;
				}

			}
		}

		$this->RecurseDir($this->projectDir);
		$this->CacheSaveProjectList();
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

		GitPHP_Log::GetInstance()->Log(sprintf('Searching directory %1$s', $dir));

		if ($dh = opendir($dir)) {
			$trimlen = strlen($this->projectDir) + 1;
			while (($file = readdir($dh)) !== false) {
				$fullPath = $dir . '/' . $file;
				if ((strpos($file, '.') !== 0) && is_dir($fullPath)) {
					if (is_file($fullPath . '/HEAD')) {
						GitPHP_Log::GetInstance()->Log(sprintf('Found project %1$s', $fullPath));
						$projectPath = substr($fullPath, $trimlen);
						try {
							$proj = new GitPHP_Project($this->projectDir, $projectPath);
							$proj->SetCategory(trim(substr($dir, strlen($this->projectDir)), '/'));
							if ((!GitPHP_Config::GetInstance()->GetValue('exportedonly', false)) || $proj->GetDaemonEnabled()) {
								$this->projects[$projectPath] = $proj;
							}
						} catch (Exception $e) {
							GitPHP_Log::GetInstance()->Log($e->getMessage());
						}
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

	/** Save and restore project list to prevent parsing directories
	 */
	private function CacheSaveProjectList()
	{
		$data = serialize($this->projects);
		return (file_put_contents(GITPHP_CACHE.'ProjectList.dat',$data) > 0);
	}
}
