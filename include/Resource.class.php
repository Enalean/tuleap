<?php
/**
 * GitPHP Resource
 *
 * Resource factory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

require_once(GITPHP_BASEDIR . 'lib/php-gettext/streams.php');
require_once(GITPHP_BASEDIR . 'lib/php-gettext/gettext.php');

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
		$reader = null;

		if (!(($locale == 'en_US') || ($locale == 'en'))) {
			$reader = new FileReader(GITPHP_LOCALEDIR . $locale . '/gitphp.mo');
		}

		self::$instance = new gettext_reader($reader);
	}

}
