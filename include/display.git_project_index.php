<?php
/*
 *  display.git_project_index.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - project index
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function git_project_index()
{
	global $tpl;

	header("Content-type: text/plain; charset=utf-8");
	header("Content-Disposition: inline; filename=\"index.aux\"");

	$cachekey = sha1(serialize(GitPHP_ProjectList::GetInstance()->GetConfig()));

	if (!$tpl->is_cached('projectindex.tpl', $cachekey)) {
		$tpl->assign("projlist", GitPHP_ProjectList::GetInstance());
	}
	$tpl->display('projectindex.tpl', $cachekey);
}

?>
