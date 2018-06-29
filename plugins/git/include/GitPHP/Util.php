<?php


namespace Tuleap\Git\GitPHP;

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
class Util
{

	/**
	 * AddSlash
	 *
	 * Adds a trailing slash to a directory path if necessary
	 *
	 * @access public
	 * @static
	 * @param string $path path to add slash to
	 * @return string $path with a trailing slash
	 */
	public static function AddSlash($path)
	{
		if (empty($path))
			return $path;

		$end = substr($path, -1);

		if (!(( ($end == '/') || ($end == ':')))) {
            $path .= '/';
        }

		return $path;
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