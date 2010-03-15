<?php
/*
 *  gitutil.git_history_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - history list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once('gitutil.git_exec.php');

function git_history_list($proj,$hash,$file)
{
	global $gitphp_conf;
	$cmd = GIT_REV_LIST . " " . $hash . " | " . $gitphp_conf['gitbin'] . " --git-dir=" . $proj . " " . GIT_DIFF_TREE . " -r --stdin -- " . $file;
	return git_exec($proj, $cmd);
}

?>
