<?php
/*
 *  util.mode_str.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - octal mode to mode string
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function mode_str($octmode)
{
	$mode = octdec($octmode);
	if (($mode & 0x4000) == 0x4000)
		return "drwxr-xr-x";
	else if (($mode & 0xA000) == 0xA000)
		return "lrwxrwxrwx";
	else if (($mode & 0x8000) == 0x8000) {
		if (($mode & 0x0040) == 0x0040)
			return "-rwxr-xr-x";
		else
			return "-rw-r--r--";
	}
	return "----------";
}

?>
