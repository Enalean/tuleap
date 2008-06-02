<?php
/*
 *  gitutil.git_tar_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - tar tree
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('defs.commands.php');

function git_tar_tree($proj,$hash,$rname = NULL)
{
	global $gitphp_conf;
	$cmd = "env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . GIT_TAR_TREE . " " . $hash;
	if ($rname)
		$cmd .= " " . $rname;
	return $cmd;
}

?>
