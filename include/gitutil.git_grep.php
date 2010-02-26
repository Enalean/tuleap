<?php
/*
 *  gitutil.git_grep.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - grep
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('defs.commands.php');
require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');

function git_grep($hash, $search, $case = false, $binary = false, $fullname = true)
{
	global $gitphp_current_project;

	if (!$gitphp_current_project)
		return '';

	$exe = new GitPHP_GitExe($gitphp_current_project);

	$args = array();
	if (!$binary)
		$args[] = '-I';
	if ($fullname)
		$args[] = '--full-name';
	if (!$case)
		$args[] = '--ignore-case';
	$args[] = '-e';
	$args[] = $search;
	$args[] = $hash;
	return $exe->Execute(GIT_GREP, $args);
}

?>
