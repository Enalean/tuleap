<?php
/*
 *  gitutil.git_version.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - version
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_exec.php');

 function git_version()
 {
 	$verstr = explode(" ",git_exec(null, "--version"));
	if (($verstr[0] == "git") && ($verstr[1] == "version"))
		return $verstr[2];
	return null;
 }

?>
