<?php
/*
 *  util.mode_str.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - octal mode to mode string
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
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
