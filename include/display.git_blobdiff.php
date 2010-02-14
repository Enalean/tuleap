<?php
/*
 *  display.git_blobdiff.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob diff
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.prep_tmpdir.php');
 require_once('gitutil.read_info_ref.php');
 require_once('gitutil.git_path_trees.php');
 require_once('gitutil.git_diff.php');

function git_blobdiff($hash,$hashbase,$hashparent,$file)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . $hashbase . "|" . $hash . "|" . $hashparent . "|" . sha1($file);

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
		$co = $gitphp_current_project->GetCommit($hashbase);
		if ($co) {
			$tpl->assign("fullnav",TRUE);
			$tpl->assign("tree",$co->GetTree()->GetHash());
			$tpl->assign("title",$co->GetTitle());
			$refs = read_info_ref();
			if (isset($refs[$hashbase]))
				$tpl->assign("hashbaseref",$refs[$hashbase]);
		}
		$paths = git_path_trees($hashbase, $file);
		$tpl->assign("paths",$paths);
		$diffout = explode("\n",git_diff($hashparent,($file?$file:$hashparent),$hash,($file?$file:$hash)));
		$tpl->assign("diff",$diffout);
	}
	$tpl->display('blobdiff.tpl', $cachekey);
}

?>
