<?php
/*
 *  util.descrcmp.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - project description comparison function
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

 include_once('gitutil.git_project_descr.php');

function descrcmp($a,$b)
{
	global $gitphp_conf;
	return strcmp(git_project_descr($gitphp_conf['projectroot'],$a),git_project_descr($gitphp_conf['projectroot'],$b));
}

?>
