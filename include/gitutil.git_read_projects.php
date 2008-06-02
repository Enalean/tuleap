<?php
/*
 *  gitutil.git_read_projects.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read projects
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

function git_read_projects($projectroot,$projectlist)
{
	$projects = array();
	if (isset($projectroot)) {
		if (is_dir($projectroot)) {
			if (isset($projectlist)) {
				foreach ($projectlist as $cat => $plist) {
					if (is_array($plist)) {
						$projs = array();
						foreach ($plist as $pname => $ppath) {
							if (is_dir($projectroot . $ppath) && is_file($projectroot . $ppath . "/HEAD"))
								$projs[] = $ppath;
						}
						if (count($projs) > 0) {
							sort($projs);
							$projects[$cat] = $projs;
						}
					}
				}
			} else {
				if ($dh = opendir($projectroot)) {
					while (($file = readdir($dh)) !== false) {
						if ((strpos($file,'.') !== 0) && is_dir($projectroot . $file) && is_file($projectroot . $file . "/HEAD"))
							$projects[] = $file;
					}
					closedir($dh);
				} else
					return "Could not read project directory";
			}
		} else
			return "Projectroot is not a directory";
	} else
		return "No projectroot set";
	return $projects;
}

?>
