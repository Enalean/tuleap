<?php
/*
 *  display.git_tags.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - tags
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_read_refs.php');

function git_tags()
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject());

	if (!$tpl->is_cached('tags.tpl', $cachekey)) {
		$head = $gitphp_current_project->GetHeadCommit()->GetHash();
		$tpl->assign("head",$head);
		$taglist = git_read_refs("refs/tags");
		if (isset($taglist) && (count($taglist) > 0)) {
			$tpl->assign("taglist",$taglist);
		}
	}
	$tpl->display('tags.tpl', $cachekey);
}

?>
