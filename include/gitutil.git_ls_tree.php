<?php
/*
 *  gitutil.git_ls_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - list tree
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('defs.commands.php');

function git_ls_tree($proj,$hash,$nullterm = FALSE)
{
	global $gitphp_conf;
	$cmd = "env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . GIT_LS_TREE;
	if ($nullterm)
		$cmd .= " -z";
	return shell_exec($cmd . " " . $hash);
}

?>
