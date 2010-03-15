<?php
/*
 *  display.git_heads.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - heads
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_read_head.php');
 require_once('gitutil.git_read_refs.php');

function git_heads($projectroot,$project)
{
	global $tpl;

	$cachekey = sha1($project);

	if (!$tpl->is_cached('heads.tpl', $cachekey)) {
		$head = git_read_head($projectroot . $project);
		$tpl->assign("head",$head);
		$headlist = git_read_refs($projectroot, $project, "refs/heads");
		$tpl->assign("headlist",$headlist);
	}
	$tpl->display('heads.tpl', $cachekey);
}

?>
