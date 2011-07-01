<?php
/**
 * GitPHP Util
 *
 * Utility functions
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

/**
 * Util class
 *
 * @package GitPHP
 */
class GitPHP_Util
{

	/**
	 * AddSlash
	 *
	 * Adds a trailing slash to a directory path if necessary
	 *
	 * @access public
	 * @static
	 * @param string $path path to add slash to
	 * @param $backslash true to also check for backslash (windows paths)
	 * @return string $path with a trailing slash
	 */
	public static function AddSlash($path, $backslash = true)
	{
		if (empty($path))
			return $path;

		$end = substr($path, -1);

		if (!(( ($end == '/') || ($end == ':')) || ($backslash && (strtoupper(substr(PHP_OS, 0, 3))) && ($end == '\\'))))
			$path .= '/';

		return $path;
	}

	/**
	 * IsWindows
	 *
	 * Tests if this is running on windows
	 *
	 * @access public
	 * @static
	 * @return bool true if on windows
	 */
	public static function IsWindows()
	{
		return (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
	}

	/**
	 * Is64Bit
	 *
	 * Tests if this is a 64 bit machine
	 *
	 * @access public
	 * @static
	 * @return bool true if on 64 bit
	 */
	public function Is64Bit()
	{
		return (strpos(php_uname('m'), '64') !== false);
	}

}
