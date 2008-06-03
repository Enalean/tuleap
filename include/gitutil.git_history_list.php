<?php
/*
 *  gitutil.git_history_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - history list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('defs.commands.php');
 include_once('gitutil.git_exec.php');

function git_history_list($proj,$hash,$file)
{
	$list = array();
	$cmd = GIT_REV_LIST . " " . $hash;
	$out = git_exec($proj, $cmd);
	$outlist = explode("\n",$out);
	foreach ($outlist as $i => $line) {
		$out2 = git_exec($proj, GIT_DIFF_TREE . " -r " . $line . " '" . $file . "'");
		$out2list = explode("\n",$out2);
		$list = array_merge($list, $out2list);
	}
	return $list;
}

?>
