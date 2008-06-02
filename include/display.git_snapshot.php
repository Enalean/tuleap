<?php
/*
 *  display.git_snapshot.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - snapshot
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

 include_once('gitutil.git_tar_tree.php');

function git_snapshot($projectroot,$project,$hash)
{
	global $gitphp_conf;
	if (!isset($hash))
		$hash = "HEAD";
	$rname = str_replace(array("/",".git"),array("-",""),$project);
	$cmd = git_tar_tree($projectroot . $project, $hash, $rname);
	if ($gitphp_conf['bzsnapshots'] && function_exists("bzcompress")) {
		header("Content-Type: application/x-bzip2");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar.bz2");
		echo bzcompress(shell_exec($cmd),$gitphp_conf['bzblocksize']);
	} else {
		header("Content-Type: application/x-tar");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar");
		echo shell_exec($cmd);
	}
}

?>
