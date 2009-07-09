<?php
/*
 *  display.git_tag.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - tag
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.date_str.php');
 require_once('gitutil.git_read_tag.php');
 require_once('gitutil.git_read_head.php');

function git_tag($projectroot, $project, $hash)
{
	global $tpl;

	$cachekey = sha1($project) . "|" . $hash;

	if (!$tpl->is_cached('tag.tpl', $cachekey)) {

		$head = git_read_head($projectroot . $project);
		$tpl->assign("head",$head);
		$tpl->assign("hash", $hash);

		$tag = git_read_tag($projectroot . $project, $hash);

		$tpl->assign("tag",$tag);
		if (isset($tag['author'])) {
			$ad = date_str($tag['epoch'],$tag['tz']);
			$tpl->assign("datedata",$ad);
		}
	}
	$tpl->display('tag.tpl', $cachekey);
}

?>
