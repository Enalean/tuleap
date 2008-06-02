<?php
/*
 *  gitutil.git_rev_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - fetch revision list
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

 include_once('defs.commands.php');

function git_rev_list($proj,$head,$count = NULL,$header = FALSE,$parents = FALSE)
{
	global $gitphp_conf;
	$cmd = "env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . GIT_REV_LIST . " ";
	if ($header)
		$cmd .= "--header ";
	if ($parents)
		$cmd .= "--parents ";
	if ($count)
		$cmd .= "--max-count=" . $count;
	return shell_exec($cmd . " " . $head);
}

?>
