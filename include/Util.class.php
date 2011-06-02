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

}
