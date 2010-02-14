<?php
/*
 *  gitutil.git_archive.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - archive
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once(GITPHP_INCLUDEDIR . 'git/GitExe.class.php');

function git_archive($hash,$rname = NULL, $fmt = "tar")
{
	global $gitphp_current_project;

	if (!$gitphp_current_project)
		return '';

	$exe = new GitPHP_GitExe(GitPHP_Config::GetInstance()->GetValue('gitbin'), $gitphp_current_project);

	$args = array();
	$args[] = '--format=' . $fmt;
	if ($rname)
		$args[] = '--prefix=' . $rname . '/';
	$args[] = $hash;
	return $exe->Execute(GIT_ARCHIVE, $args);
}

?>
