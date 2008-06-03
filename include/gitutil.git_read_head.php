<?php
/*
 *  gitutil.git_read_head.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read HEAD
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

include_once('defs.commands.php');
include_once('gitutil.git_exec.php');

function git_read_head($proj)
{
	$cmd = GIT_REV_PARSE . " --verify HEAD";
	return git_exec($proj, $cmd);
}

?>
