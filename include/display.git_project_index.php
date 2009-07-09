<?php
/*
 *  display.git_project_index.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - project index
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_read_projects.php');

function git_project_index($projectroot, $projectlist)
{
	global $tpl, $git_projects;

	header("Content-type: text/plain; charset=utf-8");
	header("Content-Disposition: inline; filename=\"index.aux\"");

	$cachekey = sha1(serialize($projectlist));

	if (!$tpl->is_cached('projectindex.tpl', $cachekey)) {
		if (isset($git_projects))
			$tpl->assign("categorized", TRUE);
		$projlist = git_read_projects($projectroot, $projectlist);
		$tpl->assign("projlist", $projlist);
	}
	$tpl->display('projectindex.tpl', $cachekey);
}

?>
