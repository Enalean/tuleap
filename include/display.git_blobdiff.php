<?php
/*
 *  display.git_blobdiff.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob diff
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.prep_tmpdir.php');
 require_once('gitutil.git_read_commit.php');
 require_once('gitutil.read_info_ref.php');
 require_once('gitutil.git_path_trees.php');
 require_once('display.git_diff_print.php');

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
		$tpl->assign("file",$file);
		$refs = read_info_ref($projectroot . $project);
		if (isset($refs[$hashbase]))
			$tpl->assign("hashbaseref",$refs[$hashbase]);
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
	$paths = git_path_trees($projectroot . $project, $hashbase, $file);
	$tpl->assign("paths",$paths);
	$tpl->display("blobdiff_header.tpl");
	git_diff_print($projectroot . $project, $hashparent,($file?$file:$hashparent),$hash,($file?$file:$hash));
	$tpl->clear_all_assign();
	$tpl->display("blobdiff_footer.tpl");
}

?>
