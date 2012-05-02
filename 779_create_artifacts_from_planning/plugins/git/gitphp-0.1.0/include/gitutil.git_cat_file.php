<?php
/*
 *  gitutil.git_cat_file.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - cat file
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once('gitutil.git_exec.php');

function git_cat_file($proj,$hash,$pipeto = NULL, $type = "blob")
{
	$cmd = GIT_CAT_FILE . " " . $type . " " . $hash;
	if ($pipeto)
		$cmd .= " > " . $pipeto;
	return git_exec($proj, $cmd);
}

?>
