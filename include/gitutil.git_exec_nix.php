<?php
/*
 *  gitutil.git_exec_nix.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - execute git command on *nix
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 function git_exec_nix($project, $command)
 {
 	global $gitphp_conf;
	$cmd = "env GIT_DIR=" . $project . " " . $gitphp_conf['gitbin'] . " " . $command;
	return shell_exec($cmd);
 }

?>
