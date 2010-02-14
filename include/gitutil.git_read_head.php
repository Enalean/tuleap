<?php
/*
 *  gitutil.git_read_head.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read HEAD
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

require_once('defs.commands.php');
require_once(GITPHP_INCLUDEDIR . 'git/GitExe.class.php');

function git_read_head()
{
	global $gitphp_current_project;

	if (!$gitphp_current_project)
		return '';

	$exe = new GitPHP_GitExe(GitPHP_Config::GetInstance()->GetValue('gitbin'), $gitphp_current_project);

	$args = array();
	$args[] = '--verify';
	$args[] = 'HEAD';
	return trim($exe->Execute(GIT_REV_PARSE, $args));
}

?>
