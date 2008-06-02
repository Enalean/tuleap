<?php
/*
 *  util.prep_tmpdir.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - Prepare temporary directory
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

function prep_tmpdir()
{
	global $gitphp_conf;
	if (file_exists($gitphp_conf['gittmp'])) {
		if (is_dir($gitphp_conf['gittmp'])) {
			if (!is_writeable($gitphp_conf['gittmp']))
				return "Specified tmpdir is not writeable!";
		} else
			return "Specified tmpdir is not a directory";
	} else
		if (!mkdir($gitphp_conf['gittmp'],0700))
			return "Could not create tmpdir";
	return TRUE;
}

?>
