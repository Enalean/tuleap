<?php
/**
 * GitPHP Blob
 *
 * Represents a single blob
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */

require_once(GITPHP_GITOBJECTDIR . 'GitObject.class.php');

/**
 * Commit class
 *
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_Blob extends GitPHP_GitObject
{
	
	/**
	 * FileType
	 *
	 * Gets a file type from its octal mode
	 *
	 * @access public
	 * @static
	 * @param string $octMode octal mode
	 * @return string file type
	 */
	public static function FileType($octMode)
	{
		$mode = octdec($octMode);
		if (($mode & 0x4000) == 0x4000)
			return 'directory';
		else if (($mode & 0xA000) == 0xA000)
			return 'symlink';
		else if (($mode & 0x8000) == 0x8000)
			return 'file';
		return 'unknown';
	}

}
