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
	$ret = prep_tmpdir();
	if ($ret !== TRUE) {
		echo $ret;
		return;
	}
	header("Content-type: text/plain; charset=UTF-8");
	echo git_diff($projectroot . $project, $hashparent,($file?"a/".$file:$hashparent),$hash,($file?"b/".$file:$hash));
}

?>
