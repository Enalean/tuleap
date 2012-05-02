<?php
/*
 *  gitutil.git_ls_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - list tree
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once('gitutil.git_exec.php');

function git_ls_tree($proj,$hash,$nullterm = FALSE, $recurse = FALSE)
{
	$cmd = GIT_LS_TREE;
	if ($nullterm)
		$cmd .= " -z";
	if ($recurse)
		$cmd .= " -r -t --full-name";
	return git_exec($proj, $cmd . " " . $hash);
}

?>
