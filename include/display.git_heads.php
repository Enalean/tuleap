<?php
/*
 *  display.git_heads.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - heads
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function git_heads()
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject());

	if (!$tpl->is_cached('heads.tpl', $cachekey)) {
		$head = $gitphp_current_project->GetHeadCommit()->GetHash();
		$tpl->assign("head",$head);

		$headlist = $gitphp_current_project->GetHeads();
		if (isset($headlist) && (count($headlist) > 0)) {
			$tpl->assign("headlist",$headlist);
		}
	}
	$tpl->display('heads.tpl', $cachekey);
}

?>
