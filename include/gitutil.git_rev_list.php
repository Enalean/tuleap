<?php
/*
 *  gitutil.git_rev_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - fetch revision list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('defs.commands.php');
 include_once('gitutil.git_exec.php');

function git_rev_list($proj,$head,$count = NULL,$header = FALSE,$parents = FALSE)
{
	$cmd = GIT_REV_LIST . " ";
	if ($header)
		$cmd .= "--header ";
	if ($parents)
		$cmd .= "--parents ";
	if ($count)
		$cmd .= "--max-count=" . $count;
	return git_exec($proj, $cmd . " " . $head);
}

?>
