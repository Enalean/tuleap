<?php
/*
 *  gitutil.git_tar_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - tar tree
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('defs.commands.php');
 include_once('gitutil.git_exec.php');

function git_tar_tree($proj,$hash,$rname = NULL)
{
	$cmd = GIT_TAR_TREE . " " . $hash;
	if ($rname)
		$cmd .= " " . $rname;
	return git_exec($proj, $cmd);
}

?>
