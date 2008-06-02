<?php
/*
 *  gitutil.git_read_head.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read HEAD
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

include_once('defs.commands.php');

function git_read_head($proj)
{
	global $gitphp_conf;
	return shell_exec("env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . GIT_REV_PARSE . " --verify HEAD");
}

?>
