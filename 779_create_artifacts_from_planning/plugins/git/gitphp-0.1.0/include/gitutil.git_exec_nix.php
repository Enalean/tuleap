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
	$cmd = $gitphp_conf['gitbin'];
	if (isset($project) && (strlen($project) > 0))
		$cmd .= " --git-dir=" . $project;
	$cmd .= " " . $command;        
	return shell_exec($cmd);
 }

?>
