<?php
/*
 *  gitutil.git_diff_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - diff tree objects
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once(GITPHP_INCLUDEDIR . 'git/GitExe.class.php');

function git_diff_tree($hashes,$renames = FALSE)
{
	global $gitphp_current_project;

	if (!$gitphp_current_project)
		return '';

	$exe = new GitPHP_GitExe(GitPHP_Config::GetInstance()->GetValue('gitbin'), $gitphp_current_project);

	$args = array();
	$args[] = '-r';
	if ($renames)
		$args[] = '-M';
	$args[] = $hashes;
	return $exe->Execute(GIT_DIFF_TREE, $args);
}

?>
