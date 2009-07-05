<?php
/*
 *  display.git_tags.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - tags
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_read_head.php');
 require_once('gitutil.git_read_refs.php');

function git_tags($projectroot,$project)
{
	global $tpl;
	$head = git_read_head($projectroot . $project);
	$tpl->assign("head",$head);
	$taglist = git_read_refs($projectroot, $project, "refs/tags");
	if (isset($taglist) && (count($taglist) > 0)) {
		$tpl->assign("taglist",$taglist);
	}
	$tpl->display("tags.tpl");
}

?>
