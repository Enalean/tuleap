<?php
/*
 *  display.git_shortlog.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - short log
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.constants.php');
 require_once('util.date_str.php');
 require_once('util.age_string.php');
 require_once('gitutil.git_read_revlist.php');
 require_once('gitutil.read_info_ref.php');

function git_shortlog($hash,$page)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . $hash . "|" . (isset($page) ? $page : 0);

	if (!$tpl->is_cached('shortlog.tpl', $cachekey)) {
		$head = $gitphp_current_project->GetHeadCommit();;
		if (!isset($hash))
			$hash = $head->GetHash();
		if (!isset($page))
			$page = 0;
		$refs = read_info_ref();
		$tpl->assign("hash",$hash);
		$tpl->assign("head",$head->GetHash());

		if ($page)
			$tpl->assign("page",$page);

		$revlist = git_read_revlist($hash, 101, ($page * 100));

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
				$co = $gitphp_current_project->GetCommit($commit);
				$ad = date_str($co->GetAuthorEpoch());
				$commitline["commit"] = $commit;
				$age = $co->GetAge();
				if ($age > 60*60*24*7*2) {
					$commitline["agestringdate"] = date('Y-m-d', $co->GetCommitterEpoch());
					$commitline["agestringage"] = age_string($age);
				} else {
					$commitline["agestringdate"] = age_string($age);
					$commitline["agestringage"] = date('Y-m-d', $co->GetCommitterEpoch());
				}
				$commitline["authorname"] = $co->GetAuthorName();
				$titleshort = $co->GetTitle(GITPHP_TRIM_LENGTH);
				$title = $co->GetTitle();
				$commitline["title_short"] = $titleshort;
				if (strlen($titleshort) < strlen($title))
					$commitline["title"] = $title;
				$commitlines[] = $commitline;
				unset($co);
			}
		}
		$tpl->assign("commitlines",$commitlines);
	}
	$tpl->display('shortlog.tpl', $cachekey);
}

?>
