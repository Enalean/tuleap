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
 require_once('gitutil.git_diff.php');

function git_blobdiff($projectroot,$project,$hash,$hashbase,$hashparent,$file)
{
	global $tpl;

	$cachekey = sha1($project) . "|" . $hashbase . "|" . $hash . "|" . $hashparent . "|" . sha1($file);

	if (!$tpl->is_cached('blobdiff.tpl', $cachekey)) {
		$ret = prep_tmpdir();
		if ($ret !== TRUE) {
			echo $ret;
			return;
		}
		$tpl->assign("hash",$hash);
		$tpl->assign("hashparent",$hashparent);
		$tpl->assign("hashbase",$hashbase);
		if (isset($file))
			$tpl->assign("file",$file);
		if ($co = git_read_commit($projectroot . $project, $hashbase)) {
			$tpl->assign("fullnav",TRUE);
			$tpl->assign("tree",$co['tree']);
			$tpl->assign("title",$co['title']);
			$refs = read_info_ref($projectroot . $project);
			if (isset($refs[$hashbase]))
				$tpl->assign("hashbaseref",$refs[$hashbase]);
		}
		$paths = git_path_trees($projectroot . $project, $hashbase, $file);
		$tpl->assign("paths",$paths);
		$diffout = explode("\n",git_diff($projectroot . $project, $hashparent,($file?$file:$hashparent),$hash,($file?$file:$hash)));
		$tpl->assign("diff",$diffout);
	}
	$tpl->display('blobdiff.tpl', $cachekey);
}

?>
