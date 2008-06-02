<?php
/*
 *  gitutil.git_get_hash_by_path.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - get hash from a path
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

include_once('gitutil.git_ls_tree.php');

function git_get_hash_by_path($project,$base,$path,$type = null)
{
	$tree = $base;
	$parts = explode("/",$path);
	$partcount = count($parts);
	foreach ($parts as $i => $part) {
		$lsout = git_ls_tree($project, $tree);
		$entries = explode("\n",$lsout);
		foreach ($entries as $j => $line) {
			if (ereg("^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$",$line,$regs)) {
				if ($regs[4] == $part) {
					if ($i == ($partcount)-1)
						return $regs[3];
					if ($regs[2] == "tree")
						$tree = $regs[3];
					break;
				}
			}
		}
	}
}

?>
