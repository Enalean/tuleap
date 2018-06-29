<?php

namespace Tuleap\Git\GitPHP;

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

/**
 * ProjectList class
 *
 * @package GitPHP
 * @subpackage Git
 */
class ProjectList
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
	 * @param boolean $legacy true if this is the legacy project config
	 * @throws Exception if there was an error reading the file
	 */
	public static function Instantiate($file = null, $legacy = false)
	{
		if (self::$instance)
			return;


		if (!empty($file) && is_file($file) && include($file)) {
			if (isset($git_projects)) {
				if (is_string($git_projects)) {
					if (function_exists('simplexml_load_file') && ProjectListScmManager::IsSCMManager($git_projects)) {
						self::$instance = new ProjectListScmManager($git_projects);
					} else {
						self::$instance = new ProjectListFile($git_projects);
					}
				} else if (is_array($git_projects)) {
					if ($legacy) {
						self::$instance = new ProjectListArrayLegacy($git_projects);
					} else {
						self::$instance = new ProjectListArray($git_projects);
					}
				}
			}
		}

		if (!self::$instance) {

			self::$instance = new ProjectListDirectory(Config::GetInstance()->GetValue('projectroot'));
		}

		if (isset($git_projects_settings) && !$legacy)
			self::$instance->ApplySettings($git_projects_settings);
	}

}

