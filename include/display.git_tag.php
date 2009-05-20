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
 require_once('gitutil.git_read_head.php');

function git_tag($projectroot, $project, $hash)
{
	global $tpl;

	$head = git_read_head($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("head",$head);
	$tpl->display("tag_nav.tpl");

	$tag = git_read_tag($projectroot . $project, $hash);

	$tpl->clear_all_assign();
	$tpl->assign("project", $project);
	$tpl->assign("hash", $hash);
	$tpl->assign("title",$tag['name']);
	$tpl->assign("type",$tag['type']);
	$tpl->assign("object",$tag['object']);
	if (isset($tag['author'])) {
		$tpl->assign("author",$tag['author']);
		$ad = date_str($tag['epoch'],$tag['tz']);
		$tpl->assign("adrfc2822",$ad['rfc2822']);
		$tpl->assign("adhourlocal",$ad['hour_local']);
		$tpl->assign("adminutelocal",$ad['minute_local']);
		$tpl->assign("adtzlocal",$ad['tz_local']);
	}
	$tpl->assign("comment",$tag['comment']);
	$tpl->display("tag_data.tpl");
}

?>
