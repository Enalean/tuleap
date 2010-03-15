<?php
/*
 *  gitutil.git_diff_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - diff tree objects
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once('gitutil.git_exec.php');

function git_diff_tree($proj,$hashes,$renames = FALSE)
{
	$cmd = GIT_DIFF_TREE . " -r ";
	if ($renames)
		$cmd .= "-M ";
	return git_exec($proj, $cmd . $hashes);
}

?>
