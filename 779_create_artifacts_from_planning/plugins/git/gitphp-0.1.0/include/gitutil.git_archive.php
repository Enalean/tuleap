<?php
/*
 *  gitutil.git_archive.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - archive
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once('gitutil.git_exec.php');

function git_archive($proj,$hash,$rname = NULL, $fmt = "tar")
{
	$cmd = GIT_ARCHIVE . " --format=" . $fmt;
	if ($rname)
		$cmd .= " --prefix=" . $rname . "/";
	$cmd .= " " . $hash;
	return git_exec($proj, $cmd);
}

?>
