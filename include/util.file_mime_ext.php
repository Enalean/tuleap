<?php
/*
 *  util.file_mime_ext.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - file mimetype using file extension
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

function file_mime_ext($filename)
{
	if ($filename) {
		$dotpos = strrpos($filename, ".");
		if ($dotpos !== FALSE)
			$filename = substr($filename, $dotpos+1);
		switch ($filename) {
			case "jpg":
			case "jpeg":
			case "jpe":
				return "image/jpeg";
				break;
			case "gif":
				return "image/gif";
				break;
			case "png";
				return "image/png";
				break;
		}
	}

	return FALSE;
}

?>
