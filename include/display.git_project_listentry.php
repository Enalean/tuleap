<?php
/*
 *  display.git_project_listentry.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - single project list item
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('gitutil.git_project_descr.php');
 include_once('gitutil.git_project_owner.php');
 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_commit.php');

function git_project_listentry($projectroot,$project,$class,$indent)
{
	global $tpl;
	$tpl->clear_all_assign();
	$tpl->assign("class",$class);
	$tpl->assign("project",$project);
	$tpl->assign("descr",git_project_descr($projectroot,$project,TRUE));
	$tpl->assign("owner",git_project_owner($projectroot,$project));
	if ($indent)
		$tpl->assign("idt",TRUE);
	$head = git_read_head($projectroot . $project);
	$commit = git_read_commit($projectroot . $project,$head);
	if ($commit['age'] < 60*60*24*2)
		$tpl->assign("age_colored",TRUE);
	if ($commit['age'] < 60*60*2)
		$tpl->assign("age_bold",TRUE);
	$tpl->assign("age_string",$commit['age_string']);
	$tpl->display("projlist_item.tpl");
}

?>
