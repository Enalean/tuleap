<?php
/*
 *  gitutil.git_history_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - history list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');

function git_history_list($hash,$file)
{
	global $gitphp_current_project;

	if (!$gitphp_current_project)
		return '';

	$exe = new GitPHP_GitExe($gitphp_current_project);

	$args = array();
	$args[] = $hash;
	$args[] = '|';
	$args[] = $exe->GetBinary();
	$args[] = '--git-dir=' . $gitphp_current_project->GetPath();
	$args[] = GIT_DIFF_TREE;
	$args[] = '-r';
	$args[] = '--stdin';
	$args[] = $file;
	return $exe->Execute(GIT_REV_LIST, $args);
}

?>
