<?php
/*
 *  display.git_snapshot.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - snapshot
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function git_snapshot($projectroot,$project,$hash)
{
	global $tpl, $gitphp_current_project;

	$commit = null;

	if (!isset($hash))
		$commit = $gitphp_current_project->GetHeadCommit();
	else
		$commit = $gitphp_current_project->GetCommit($hash);

	$cachekey = sha1($project) . "|" . $hash;

	$compressformat = GitPHP_Config::GetInstance()->GetValue('compressformat', GITPHP_COMPRESS_ZIP);

	$rname = $gitphp_current_project->GetSlug();;
	if ($compressformat == GITPHP_COMPRESS_ZIP) {
		header("Content-Type: application/x-zip");
		header("Content-Disposition: attachment; filename=" . $rname . ".zip");
	} else if (($compressformat == GITPHP_COMPRESS_BZ2) && function_exists("bzcompress")) {
		header("Content-Type: application/x-bzip2");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar.bz2");
	} else if (($compressformat == GITPHP_COMPRESS_GZ) && function_exists("gzencode")) {
		header("Content-Type: application/x-gzip");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar.gz");
	} else {
		header("Content-Type: application/x-tar");
		header("Content-Disposition: attachment; filename=" . $rname . ".tar");
	}

	if (!$tpl->is_cached('snapshot.tpl', $cachekey)) {
		$tpl->assign("archive", $commit->GetArchive($compressformat));
	}
	$tpl->display('snapshot.tpl', $cachekey);
}

?>
