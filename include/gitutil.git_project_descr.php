<?php
/*
 *  gitutil.git_project_descr.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - project description
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

function git_project_descr($projectroot,$project,$trim = FALSE)
{
	$desc = file_get_contents($projectroot . $project . "/description");
	if ($trim && (strlen($desc) > 50))
		$desc = substr($desc,0,50) . " ...";
	return $desc;
}

?>
