<?php
/*
 *  gitutil.read_info_ref.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read info on a ref
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

function read_info_ref($project, $type = "")
{
	$refs = array();
	$lines = file($project);
	foreach ($lines as $no => $line) {
		if (ereg("^([0-9a-fA-F]{40})\t.*" . $type . "/([^\^]+)",$line,$regs)) {
			if ($isset($refs[$regs[1]]))
				$refs[$regs[1]] .= " / " . $regs[2];
			else
				$refs[$regs[1]] = $regs[2];
		}
	}
	return $refs;
}

?>
