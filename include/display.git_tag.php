<?php
/*
 *  display.git_tag.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - tag
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.date_str.php');
 require_once('gitutil.git_read_tag.php');

function git_tag($hash)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . $hash;

	if (!$tpl->is_cached('tag.tpl', $cachekey)) {

		$head = $gitphp_current_project->GetHeadCommit()->GetHash();
		$tpl->assign("head",$head);
		$tpl->assign("hash", $hash);

		$tag = git_read_tag($hash);

		$tpl->assign("tag",$tag);
		if (isset($tag['author'])) {
			$ad = date_str($tag['epoch'],$tag['tz']);
			$tpl->assign("datedata",$ad);
		}
	}
	$tpl->display('tag.tpl', $cachekey);
}

?>
