<?php
/*
 *  gitutil.git_version.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - version
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');

 function git_version()
 {
 	$exe = new GitPHP_GitExe();
	$out = $exe->Execute('', array('--version'));

 	$verstr = explode(" ", $out);
	if (($verstr[0] == "git") && ($verstr[1] == "version"))
		return $verstr[2];
	return null;
 }

?>
