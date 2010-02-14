<?php
/*
 *  display.git_log.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - log
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.date_str.php');
 require_once('util.age_string.php');
 require_once('gitutil.git_read_revlist.php');
 require_once('gitutil.read_info_ref.php');

function git_log($hash,$page)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . $hash . "|" . (isset($page) ? $page : 0);

	if (!$tpl->is_cached('log.tpl', $cachekey)) {
		$head = $gitphp_current_project->GetHeadCommit()->GetHash();
		if (!isset($hash))
			$hash = $head;
		if (!isset($page))
			$page = 0;
		$refs = read_info_ref();
		$tpl->assign("hash",$hash);
		$tpl->assign("head",$head);

		if ($page)
			$tpl->assign("page",$page);

		$revlist = git_read_revlist($hash, 101, ($page * 100));

		$revlistcount = count($revlist);
		$tpl->assign("revlistcount",$revlistcount);

		if (!$revlist) {
			$tpl->assign("norevlist",TRUE);
			$co = $gitphp_current_project->GetCommit($hash);
			$tpl->assign("lastchange", age_string($co->GetAge()));
		}

		$commitlines = array();
		$commitcount = min(100,$revlistcount);
		for ($i = 0; $i < $commitcount; ++$i) {
			$commit = $revlist[$i];
			if (isset($commit) && strlen($commit) > 1) {
				$commitline = array();
				$co = $gitphp_current_project->GetCommit($commit);
				$ad = date_str($co->GetAuthorEpoch());
				$commitline["project"] = $gitphp_current_project->GetProject();
				$commitline["commit"] = $commit;
				if (isset($refs[$commit]))
					$commitline["commitref"] = $refs[$commit];
				$commitline["agestring"] = age_string($co->GetAge());
				$commitline["title"] = $co->GetTitle();
				$commitline["authorname"] = $co->GetAuthorName();
				$commitline["rfc2822"] = $ad['rfc2822'];
				$commitline["comment"] = $co->GetComment();
				$commitlines[] = $commitline;
				unset($co);
			}
		}
		$tpl->assign("commitlines",$commitlines);
	}
	$tpl->display('log.tpl', $cachekey);
}

?>
