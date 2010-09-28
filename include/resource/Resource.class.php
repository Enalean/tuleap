<?php
/**
 * GitPHP Resource
 *
 * Resource factory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Resource
 */

require_once(GITPHP_RESOURCEDIR . 'ResourceBase.class.php');

/**
 * Resource
 *
 * @package GitPHP
 * @subpackage Resource
 */
class GitPHP_Resource
{
	
	/**
	 * instance
	 *
	 * Stores the singleton instance of the resource provider
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
	 * @return mixed instance of resource class
	 * @throws Exception if resource provider has not been instantiated yet
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
	 * @access public
	 * @static
	 * @param string $locale locale to instantiate
	 * @throws Exception on invalid locale
	 */
	public static function Instantiate($locale)
	{
		switch ($locale) {
			case 'en_US':
				require_once(GITPHP_LOCALEDIR . 'en_US.class.php');
				self::$instance = new GitPHP_Resource_en_US();
				break;
			case 'zz_Debug':
				require_once(GITPHP_LOCALEDIR . 'zz_Debug.class.php');
				self::$instance = new GitPHP_Resource_zz_Debug();
				break;
			default:
				throw new Exception('Invalid locale: ' . $locale);
				break;
		}
	}

}
