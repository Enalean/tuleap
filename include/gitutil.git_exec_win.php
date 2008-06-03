<?php
/*
 *  gitutil.git_exec_win.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - execute git command on windows
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function git_exec_win($project, $command)
{
	global $gitphp_conf;
	$cmd = $gitphp_conf['gitbin'] . " --git-dir=" . $project . " " . $command;
	return shell_exec($cmd);
}

?>
