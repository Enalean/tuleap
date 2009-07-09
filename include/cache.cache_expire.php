<?php
/*
 *  cache.cache_expire.pehe
 *  gitphp: A PHP git repository browser
 *  Component: Cache - cache expire
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('gitutil.git_read_refs.php');

function cache_expire($projectroot, $project, $projectlist, $expireall = false)
{
	global $tpl;

	if ($expireall) {
		$tpl->clear_all_cache();
		return;
	}

	if ((!isset($projectroot)) || (!isset($project)))
		return;

	$headlist = git_read_refs($projectroot, $project, "refs/heads");

	if (count($headlist) > 0) {
		$age = $headlist[0]['age'];

		$tpl->clear_cache(null, sha1($project), null, $age);

		$tpl->clear_cache('projectlist.tpl', sha1(serialize($projectlist)), null, $age);
	}
}

?>
