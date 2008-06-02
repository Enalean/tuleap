<?php
/*
 *  util.agecmp.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - project age comparison function
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

 include_once('gitutil.git_read_commit.php');
 include_once('gitutil.git_read_head.php');

function agecmp($a,$b)
{
	global $gitphp_conf;
	$ca = git_read_commit($gitphp_conf['projectroot'] . $a, git_read_head($gitphp_conf['projectroot'] . $a));
	$cb = git_read_commit($gitphp_conf['projectroot'] . $b, git_read_head($gitphp_conf['projectroot'] . $b));
	if ($ca['commit']['age'] == $cb['commit']['age'])
		return 0;
	return ($ca['commit']['age'] < $cb['commit']['age'] ? 1 : -1);
}

?>
