<?php
/*
 * gitutil.git_read_blame.php
 * gitphp: A PHP git repository browser
 * Component: Git utility - get blame info
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 */

require_once('defs.commands.php');
require_once(GITPHP_INCLUDEDIR . 'git/GitExe.class.php');

function git_read_blame($file, $rev = null)
{
	global $gitphp_current_project;

	if (!$gitphp_current_project)
		return '';

	$exe = new GitPHP_GitExe(GitPHP_Config::GetInstance()->GetValue('gitbin'), $gitphp_current_project);

	$args = array();

	$args[] = '-p';	
	if ($rev)
		$args[] = $rev;
	$args[] = $file;
	return $exe->Execute(GIT_BLAME, $args);
}

?>
