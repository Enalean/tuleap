<?php
/*
 *  gitutil.git_grep.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - grep
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('defs.commands.php');
require_once('gitutil.git_exec.php');

function git_grep($project, $hash, $search, $case = false, $binary = false, $fullname = true)
{
	$cmd = GIT_GREP;
	if (!$binary)
		$cmd .= " -I";
	if ($fullname)
		$cmd .= " --full-name";
	if (!$case)
		$cmd .= " --ignore-case";
	$cmd .= " -e " . $search;
	return git_exec($project, $cmd . " " . $hash);
}

?>
