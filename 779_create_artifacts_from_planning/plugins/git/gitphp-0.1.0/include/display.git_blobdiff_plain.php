<?php
/*
 *  display.git_blobdiff_plain.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob diff (plaintext)
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.prep_tmpdir.php');
 require_once('gitutil.git_diff.php');

function git_blobdiff_plain($projectroot,$project,$hash,$hashbase,$hashparent,$file)
{
	global $tpl;

	header("Content-type: text/plain; charset=UTF-8");

	$cachekey = sha1($project) . "|" . $hashbase . "|" . $hash . "|" . $hashparent . "|" . sha1($file);

	if (!$tpl->is_cached('blobdiffplain.tpl', $cachekey)) {
		$ret = prep_tmpdir();
		if ($ret !== TRUE) {
			echo $ret;
			return;
		}
		$tpl->assign("blobdiff",git_diff($projectroot . $project, $hashparent,($file?"a/".$file:$hashparent),$hash,($file?"b/".$file:$hash)));
	}
	$tpl->display('blobdiffplain.tpl', $cachekey);
}

?>
