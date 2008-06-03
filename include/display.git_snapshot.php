<?php
/*
 *  display.git_snapshot.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - snapshot
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('gitutil.git_tar_tree.php');

function git_snapshot($projectroot,$project,$hash)
{
	global $gitphp_conf;
	if (!isset($hash))
		$hash = "HEAD";
	$rname = str_replace(array("/",".git"),array("-",""),$project);
	$tar = git_tar_tree($projectroot . $project, $hash, $rname);
	if ($gitphp_conf['bzsnapshots'] && function_exists("bzcompress")) {
		header("Content-Type: application/x-bzip2");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar.bz2");
		echo bzcompress($tar,$gitphp_conf['bzblocksize']);
	} else {
		header("Content-Type: application/x-tar");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar");
		echo $tar;
	}
}

?>
