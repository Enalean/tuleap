<?php
/*
 *  gitutil.git_history_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - history list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('defs.commands.php');

function git_history_list($proj,$hash,$file)
{
	global $gitphp_conf;
	return shell_exec("env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . GIT_REV_LIST . " " . $hash . " | env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . GIT_DIFF_TREE . " -r --stdin '" . $file . "'");
}

?>
