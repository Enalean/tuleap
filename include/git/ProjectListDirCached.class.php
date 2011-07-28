<?php
/**
 * GitPHP ProjectListDirCached
 *
 * Load projects from cache
 *
 * @author Tanguy Pruvot <tpruvot@github>
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_INCLUDEDIR . 'Config.class.php');
require_once(GITPHP_GITOBJECTDIR . 'ProjectListBase.class.php');
require_once(GITPHP_GITOBJECTDIR . 'Project.class.php');

/**
 * ProjectListDirCached class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectListDirCached extends GitPHP_ProjectListBase
{
	
	/**
	 * __construct
	 *
	 * constructor
	 *
	 * @access public
	 */
	public function __construct($projectArray)
	{
		parent::__construct();

		$this->projectConfig = $projectArray;

		$this->PopulateProjects();
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
		$stat = stat(GITPHP_CACHE.'ProjectList.dat');
		if ($stat !== FALSE) {
			$cache_life = '180';  //caching time, in seconds
			$filemtime = max($stat['mtime'], $stat['ctime']);
			
			if  (time() - $filemtime >= $cache_life) {
				GitPHP_Log::GetInstance()->Log('ProjectListDirCache: expired, reloading...');
				return;
			}

			$data = file_get_contents(GITPHP_CACHE.'ProjectList.dat');
			$this->projects = unserialize($data);
		}
	}
}
