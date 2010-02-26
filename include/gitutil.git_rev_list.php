<?php
/*
 *  gitutil.git_rev_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - fetch revision list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once(GITPHP_GITOBJECTDIR . 'GitExe.class.php');

function git_rev_list($head,$count = NULL,$skip = NULL,$header = FALSE,$parents = FALSE,$greptype = NULL, $search = NULL)
{
	global $gitphp_current_project;

	if (!$gitphp_current_project)
		return '';

	$exe = new GitPHP_GitExe($gitphp_current_project);

	$args = array();

	if ($header)
		$args[] = '--header';
	if ($parents)
		$args[] = '--parents';
	if ($count)
		$args[] = '--max-count=' . $count;
	if ($skip)
		$args[] = '--skip=' . $skip;
	if ($greptype && $search) {
		if ($greptype == 'commit')
			$args[] = '--grep=\'' . $search . '\'';
		else if ($greptype == 'author')
			$args[] = '--author=\'' . $search . '\'';
		else if ($greptype == 'committer')
			$args[] = '--committer=\'' . $search . '\'';
		$args[] = '--regexp-ignore-case';
	}
	$args[] = $head;
	return $exe->Execute(GIT_REV_LIST, $args);
}

?>
