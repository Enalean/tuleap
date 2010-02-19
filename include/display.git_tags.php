<?php
/*
 *  display.git_tags.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - tags
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function git_tags()
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject());

	if (!$tpl->is_cached('tags.tpl', $cachekey)) {
		$head = $gitphp_current_project->GetHeadCommit()->GetHash();
		$tpl->assign("head",$head);

		$taglist = $gitphp_current_project->GetTags();
		if (isset($taglist) && (count($taglist) > 0)) {
			$tpl->assign("taglist",$taglist);
		}
	}
	$tpl->display('tags.tpl', $cachekey);
}

?>
