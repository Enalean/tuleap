<?php
/*
 *  gitutil.git_diff_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - diff tree objects
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('defs.commands.php');

function git_diff_tree($proj,$hashes,$renames = FALSE)
{
	global $gitphp_conf;
	$cmd = "env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . GIT_DIFF_TREE . " -r ";
	if ($renames)
		$cmd .= "-M ";
	return shell_exec($cmd . $hashes);
}

?>
