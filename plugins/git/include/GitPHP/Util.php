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
	 * @param $filesystem true if this is a filesystem path (to also check for backslash for windows paths)
	 * @return string $path with a trailing slash
	 */
	public static function AddSlash($path, $filesystem = true)
	{
		if (empty($path))
			return $path;

		$end = substr($path, -1);

		if (!(( ($end == '/') || ($end == ':')) || ($filesystem && GitPHP_Util::IsWindows() && ($end == '\\')))) {
			if (GitPHP_Util::IsWindows() && $filesystem) {
				$path .= '\\';
			} else {
				$path .= '/';
			}
		}

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
	public static function Is64Bit()
	{
		return (strpos(php_uname('m'), '64') !== false);
	}

	/**
	 * MakeSlug
	 *
	 * Turn a string into a filename-friendly slug
	 *
	 * @access public
	 * @param string $str string to slugify
	 * @static
	 * @return string slug
	 */
	public static function MakeSlug($str)
	{
		$from = array(
			'/'
		);
		$to = array(
			'-'
		);
		return str_replace($from, $to, $str);
	}

}
