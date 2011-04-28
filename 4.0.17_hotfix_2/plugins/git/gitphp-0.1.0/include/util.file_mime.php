<?php
/*
 *  util.file_mime.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - file mimetype
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('util.file_mime_fileinfo.php');
require_once('util.file_mime_file.php');
require_once('util.file_mime_ext.php');

function file_mime($buffer, $file = NULL)
{
	global $gitphp_conf;

	if (!$buffer)
		return FALSE;

	/*
	 * Try finfo (PHP 5.3 / PECL fileinfo)
	 */
	$mime = file_mime_fileinfo($buffer);
	if ($mime)
		return $mime;

	/*
	 * Try file command
	 */
	$mime = file_mime_file($buffer);
	if ($mime)
		return $mime;

	/*
	 * Try file extension
	 */
	if ($file) {
		$mime = file_mime_ext($file);
		if ($mime)
			return $mime;
	}

	return FALSE;
}
