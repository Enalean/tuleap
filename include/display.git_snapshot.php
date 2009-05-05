<?php
/*
 *  display.git_snapshot.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - snapshot
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

require_once('defs.constants.php');
 require_once('gitutil.git_tar_tree.php');

function git_snapshot($projectroot,$project,$hash)
{
	global $gitphp_conf;
	if (!isset($hash))
		$hash = "HEAD";
	$rname = str_replace(array("/",".git"),array("-",""),$project);
	$tar = git_tar_tree($projectroot . $project, $hash, $rname);
	if (($gitphp_conf['compressformat'] == GITPHP_COMPRESS_BZ2) && function_exists("bzcompress")) {
		header("Content-Type: application/x-bzip2");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar.bz2");
		echo bzcompress($tar,(isset($gitphp_conf['compresslevel'])?$gitphp_conf['compresslevel']:4));
		return;
	} else if (($gitphp_conf['compressformat'] == GITPHP_COMPRESS_GZ) && function_exists("gzencode")) {
		header("Content-Type: application/x-gzip");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar.gz");
		echo gzencode($tar,(isset($gitphp_conf['compresslevel'])?$gitphp_conf['compresslevel']:-1));
		return;
	}

	header("Content-Type: application/x-tar");
	header("Content-Disposition: attachment; filename=" . $rname . ".tar");
	echo $tar;
}

?>
