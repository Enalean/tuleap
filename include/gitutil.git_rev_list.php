<?php
/*
 *  gitutil.git_rev_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - fetch revision list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('defs.commands.php');

function git_rev_list($proj,$head,$count = NULL,$header = FALSE,$parents = FALSE)
{
	global $gitphp_conf;
	$cmd = "env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . GIT_REV_LIST . " ";
	if ($header)
		$cmd .= "--header ";
	if ($parents)
		$cmd .= "--parents ";
	if ($count)
		$cmd .= "--max-count=" . $count;
	return shell_exec($cmd . " " . $head);
}

?>
