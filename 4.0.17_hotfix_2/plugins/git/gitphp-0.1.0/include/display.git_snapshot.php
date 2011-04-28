<?php
/*
 *  display.git_snapshot.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - snapshot
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

require_once('defs.constants.php');
 require_once('gitutil.git_archive.php');

function git_snapshot($projectroot,$project,$hash)
{
	global $gitphp_conf, $tpl;

	if (!isset($hash))
		$hash = "HEAD";

	$cachekey = sha1($project) . "|" . $hash;

	$bzcompress = false;
	$gzencode = false;

	$rname = str_replace(array("/",".git"),array("-",""),$project);
	if ($gitphp_conf['compressformat'] == GITPHP_COMPRESS_ZIP) {
		header("Content-Type: application/x-zip");
		header("Content-Disposition: attachment; filename=" . $rname . ".zip");
	} else if (($gitphp_conf['compressformat'] == GITPHP_COMPRESS_BZ2) && function_exists("bzcompress")) {
		$bzcompress = true;
		header("Content-Type: application/x-bzip2");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar.bz2");
	} else if (($gitphp_conf['compressformat'] == GITPHP_COMPRESS_GZ) && function_exists("gzencode")) {
		$gzencode = true;
		header("Content-Type: application/x-gzip");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar.gz");
	} else {
		header("Content-Type: application/x-tar");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar");
	}

	if (!$tpl->is_cached('snapshot.tpl', $cachekey)) {

		$arc = git_archive($projectroot . $project, $hash, $rname,
			(($gitphp_conf['compressformat'] == GITPHP_COMPRESS_ZIP) ? "zip" : "tar"));

		if (($gitphp_conf['compressformat'] == GITPHP_COMPRESS_BZ2) && $bzcompress) {
			$arc = bzcompress($arc,(isset($gitphp_conf['compresslevel'])?$gitphp_conf['compresslevel']:4));
		} else if (($gitphp_conf['compressformat'] == GITPHP_COMPRESS_GZ) && $gzencode) {
			$arc = gzencode($arc,(isset($gitphp_conf['compresslevel'])?$gitphp_conf['compresslevel']:-1));
		}
		$tpl->assign("archive",$arc);
	}
	$tpl->display('snapshot.tpl', $cachekey);
}

?>