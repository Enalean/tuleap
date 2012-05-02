<?php
/*
 *  util.file_type.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - File type
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function file_type($octmode)
{
	$mode = octdec($octmode);
	if (($mode & 0x4000) == 0x4000)
		return "directory";
	else if (($mode & 0xA000) == 0xA000)
		return "symlink";
	else if (($mode & 0x8000) == 0x8000)
		return "file";
	return "unknown";
}

?>
