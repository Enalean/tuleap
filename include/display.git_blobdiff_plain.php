<?php
/*
 *  display.git_blobdiff_plain.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob diff (plaintext)
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('util.prep_tmpdir.php');
 include_once('display.git_diff_print.php');

function git_blobdiff_plain($projectroot,$project,$hash,$hashbase,$hashparent,$file)
{
	$ret = prep_tmpdir();
	if ($ret !== TRUE) {
		echo $ret;
		return;
	}
	header("Content-type: text/plain; charset=UTF-8");
	git_diff_print($projectroot . $project, $hashparent,($file?$file:$hashparent),$hash,($file?$file:$hash),"plain");
}

?>
