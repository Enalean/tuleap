<?php
/*
 *  gitutil.git_history_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - history list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once('gitutil.git_exec.php');

function git_history_list($proj,$hash,$file)
{
	$cmd = GIT_REV_LIST . " " . $hash . " | " . GitPHP_Config::GetInstance()->GetValue('gitbin', 'git') . " --git-dir=" . $proj . " " . GIT_DIFF_TREE . " -r --stdin -- " . $file;
	return git_exec($proj, $cmd);
}

?>
