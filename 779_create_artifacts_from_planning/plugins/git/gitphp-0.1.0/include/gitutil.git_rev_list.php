<?php
/*
 *  gitutil.git_rev_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - fetch revision list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once('gitutil.git_exec.php');

function git_rev_list($proj,$head,$count = NULL,$skip = NULL,$header = FALSE,$parents = FALSE,$greptype = NULL, $search = NULL)
{
	$cmd = GIT_REV_LIST . " ";
	if ($header)
		$cmd .= "--header ";
	if ($parents)
		$cmd .= "--parents ";
	if ($count)
		$cmd .= "--max-count=" . $count . " ";
	if ($skip)
		$cmd .= "--skip=" . $skip . " ";
	if ($greptype && $search) {
		if ($greptype == "commit")
			$cmd .= "--grep=" . $search . " ";
		else if ($greptype == "author")
			$cmd .= "--author=" . $search . " ";
		else if ($greptype == "committer")
			$cmd .= "--committer=" . $search . " ";
		$cmd .= "--regexp-ignore-case ";
	}
	return git_exec($proj, $cmd . " " . $head);
}

?>
