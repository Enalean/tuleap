<?php
/*
 *  gitutil.git_exec.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - execute git command depending on platform
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
 	require_once('gitutil.git_exec_win.php');
 else
 	require_once('gitutil.git_exec_nix.php');

function git_exec($project, $command)
{
	if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		return git_exec_win($project, $command);
	else
		return git_exec_nix($project, $command);
}

?>
