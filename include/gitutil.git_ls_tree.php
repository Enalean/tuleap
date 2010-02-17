<?php
/*
 *  gitutil.git_ls_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - list tree
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once(GITPHP_INCLUDEDIR . 'git/GitExe.class.php');

function git_ls_tree($hash,$nullterm = FALSE, $recurse = FALSE)
{
	global $gitphp_current_project;

	if (!$gitphp_current_project)
		return '';

	$exe = new GitPHP_GitExe($gitphp_current_project);

	$args = array();
	if ($nullterm)
		$args[] = '-z';
	if ($recurse) {
		$args[] = '-r';
		$args[] = '-t';
		$args[] = '--full-name';
	}
	$args[] = $hash;
	return $exe->Execute(GIT_LS_TREE, $args);
}

?>
