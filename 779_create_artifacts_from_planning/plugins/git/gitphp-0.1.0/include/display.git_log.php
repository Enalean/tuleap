<?php
/*
 *  display.git_log.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - log
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.date_str.php');
 require_once('gitutil.git_read_head.php');
 require_once('gitutil.git_read_revlist.php');
 require_once('gitutil.git_read_commit.php');
 require_once('gitutil.read_info_ref.php');

function git_log($projectroot,$project,$hash,$page)
{
	global $tpl;

	$cachekey = sha1($project) . "|" . $hash . "|" . (isset($page) ? $page : 0);

	if (!$tpl->is_cached('log.tpl', $cachekey)) {
		$head = git_read_head($projectroot . $project);
		if (!isset($hash))
			$hash = $head;
		if (!isset($page))
			$page = 0;
		$refs = read_info_ref($projectroot . $project);
		$tpl->assign("hash",$hash);
		$tpl->assign("head",$head);

		if ($page)
			$tpl->assign("page",$page);

		$revlist = git_read_revlist($projectroot . $project, $hash, 101, ($page * 100));

		$revlistcount = count($revlist);
		$tpl->assign("revlistcount",$revlistcount);

		if (!$revlist) {
			$tpl->assign("norevlist",TRUE);
			$co = git_read_commit($hash);
			$tpl->assign("lastchange",$co['age_string']);
		}

		$commitlines = array();
		$commitcount = min(100,$revlistcount);
		for ($i = 0; $i < $commitcount; ++$i) {
			$commit = $revlist[$i];
			if (isset($commit) && strlen($commit) > 1) {
				$commitline = array();
				$co = git_read_commit($projectroot . $project, $commit);
				$ad = date_str($co['author_epoch']);
				$commitline["project"] = $project;
				$commitline["commit"] = $commit;
				if (isset($refs[$commit]))
					$commitline["commitref"] = $refs[$commit];
				$commitline["agestring"] = $co['age_string'];
				$commitline["title"] = $co['title'];
				$commitline["authorname"] = $co['author_name'];
				$commitline["rfc2822"] = $ad['rfc2822'];
				$commitline["comment"] = $co['comment'];
				$commitlines[] = $commitline;
			}
		}
		$tpl->assign("commitlines",$commitlines);
	}
	$tpl->display('log.tpl', $cachekey);
}

?>
