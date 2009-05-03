<?php
/*
 *  util.agecmp.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - project age comparison function
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_read_commit.php');
 require_once('gitutil.git_read_head.php');

function agecmp($a,$b)
{
	global $gitphp_conf;
	$ca = git_read_commit($gitphp_conf['projectroot'] . $a, git_read_head($gitphp_conf['projectroot'] . $a));
	$cb = git_read_commit($gitphp_conf['projectroot'] . $b, git_read_head($gitphp_conf['projectroot'] . $b));
	if ($ca['age'] == $cb['age'])
		return 0;
	return ($ca['age'] < $cb['age'] ? -1 : 1);
}

?>
