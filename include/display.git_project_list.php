<?php
/*
 *  display.git_project_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - project list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function git_project_list($order = "project")
{
	global $tpl;

	$projectlist = GitPHP_ProjectList::GetInstance();

	$cachekey = sha1(serialize($projectlist->GetConfig())) . "|" . sha1($order);

	if (!$tpl->is_cached('projectlist.tpl', $cachekey)) {
		if ($order)
			$tpl->assign("order",$order);
		$projectlist->Sort($order);
		if ($projectlist->Count() > 0)
			$tpl->assign("projectlist", $projectlist);
	}
	$tpl->display('projectlist.tpl', $cachekey);
}

?>
