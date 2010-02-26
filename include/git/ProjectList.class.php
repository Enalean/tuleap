<?php
/**
 * GitPHP ProjectList
 *
 * Project list singleton instance and factory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'ProjectListDirectory.class.php');
require_once(GITPHP_GITOBJECTDIR . 'ProjectListFile.class.php');
require_once(GITPHP_GITOBJECTDIR . 'ProjectListArray.class.php');

/**
 * ProjectList class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectList
{

	/**
	 * instance
	 *
	 * Stores the singleton instance of the projectlist
	 *
	 * @access protected
	 * @static
	 */
	protected static $instance = null;

	/**
	 * GetInstance
	 *
	 * Returns the singleton instance
	 *
	 * @access public
	 * @static
	 * @return mixed instance of projectlist
	 * @throws Exception if projectlist has not been instantiated yet
	 */
	public static function GetInstance()
	{
		return self::$instance;
	}

	/**
	 * Instantiate
	 *
	 * Instantiates the singleton instance
	 *
	 * @access private
	 * @static
	 * @param string $file config file with git projects
	 * @throws Exception if there was an error reading the file
	 */
	public static function Instantiate($file = null)
	{
		if (self::$instance)
			return;

		if (!empty($file) && is_file($file) && include($file)) {
			if (is_string($git_projects)) {
					self::$instance = new GitPHP_ProjectListFile($git_projects);
					return;
			} else if (is_array($git_projects)) {
					self::$instance = new GitPHP_ProjectListArray($git_projects);
					return;
			}
		}

		self::$instance = new GitPHP_ProjectListDirectory(GitPHP_Config::GetInstance()->GetValue('projectroot'));
	}

}

