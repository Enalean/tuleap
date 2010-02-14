<?php
/*
 *  gitutil.git_cat_file.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - cat file
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once(GITPHP_INCLUDEDIR . 'git/GitExe.class.php');

function git_cat_file($hash,$pipeto = NULL, $type = "blob")
{
	global $gitphp_current_project;

	if (!$gitphp_current_project)
		return '';

	$exe = new GitPHP_GitExe(GitPHP_Config::GetInstance()->GetValue('gitbin'), $gitphp_current_project);

	$args = array();
	$args[] = $type;
	$args[] = $hash;
	if ($pipeto)
		$args[] = ' > ' . $pipeto;
	return $exe->Execute(GIT_CAT_FILE, $args);
}

?>
