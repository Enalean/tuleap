<?php
/*
 *  display.git_shortlog.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - short log
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.date_str.php');
 require_once('gitutil.git_read_head.php');
 require_once('gitutil.git_read_revlist.php');
 require_once('gitutil.git_read_commit.php');
 require_once('gitutil.read_info_ref.php');

function git_shortlog($projectroot,$project,$hash,$page)
{
	global $tpl;

	$cachekey = sha1($project) . "|" . $hash . "|" . (isset($page) ? $page : 0);

	if (!$tpl->is_cached('shortlog.tpl', $cachekey)) {
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

		$commitlines = array();
		$commitcount = min(100,count($revlist));
		for ($i = 0; $i < $commitcount; ++$i) {
			$commit = $revlist[$i];
			if (strlen(trim($commit)) > 0) {
				$commitline = array();
				if (isset($refs[$commit]))
					$commitline["commitref"] = $refs[$commit];
				$co = git_read_commit($projectroot . $project, $commit);
				$ad = date_str($co['author_epoch']);
				$commitline["commit"] = $commit;
				$commitline["agestringage"] = $co['age_string_age'];
				$commitline["agestringdate"] = $co['age_string_date'];
				$commitline["authorname"] = $co['author_name'];
				$commitline["title_short"] = $co['title_short'];
				if (strlen($co['title_short']) < strlen($co['title']))
					$commitline["title"] = $co['title'];
				$commitlines[] = $commitline;
			}
		}
		$tpl->assign("commitlines",$commitlines);
	}
	$tpl->display('shortlog.tpl', $cachekey);
}

?>
