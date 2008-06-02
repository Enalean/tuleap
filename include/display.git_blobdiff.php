<?php
/*
 *  display.git_blobdiff.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob diff
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

 include_once('util.prep_tmpdir.php');
 include_once('gitutil.git_read_commit.php');
 include_once('gitutil.git_diff_print.php');

function git_blobdiff($projectroot,$project,$hash,$hashbase,$hashparent,$file)
{
	global $tpl;
	$ret = prep_tmpdir();
	if ($ret !== TRUE) {
		echo $ret;
		return;
	}
	if (isset($hashbase) && ($co = git_read_commit($projectroot . $project, $hashbase))) {
		$tpl->clear_all_assign();
		$tpl->assign("project",$project);
		$tpl->assign("hash",$hash);
		$tpl->assign("hashbase",$hashbase);
		$tpl->assign("hashparent",$hashparent);
		$tpl->assign("tree",$co['tree']);
		$tpl->assign("title",$co['title']);
		$tpl->display("blobdiff_nav.tpl");
	} else {
		$tpl->clear_all_assign();
		$tpl->assign("hash",$hash);
		$tpl->assign("hashparent",$hashparent);
		$tpl->display("blobdiff_emptynav.tpl");
	}
	$tpl->clear_all_assign();
	if (isset($file))
		$tpl->assign("file",$file);
	$tpl->assign("project",$project);
	$tpl->assign("hashparent",$hashparent);
	$tpl->assign("hashbase",$hashbase);
	$tpl->assign("hash",$hash);
	$tpl->display("blobdiff_header.tpl");
	git_diff_print($projectroot . $project, $hashparent,($file?$file:$hashparent),$hash,($file?$file:$hash));
	$tpl->clear_all_assign();
	$tpl->display("blobdiff_footer.tpl");
}

?>
