<?php
/*
 *  display.git_search.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - search
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('defs.constants.php');
require_once('util.age_string.php');
require_once('util.highlight.php');
require_once('gitutil.git_read_revlist.php');
require_once('display.git_message.php');

function git_search($hash, $search, $searchtype, $page = 0)
{
	global $tpl, $gitphp_current_project;
	
	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . $hash . "|" . sha1($searchtype) . "|" . sha1($search) . "|" . (isset($page) ? $page : 0);

	if (!$tpl->is_cached('search.tpl', $cachekey)) {

		if (!GitPHP_Config::GetInstance()->GetValue('search', true)) {
			git_message("Search has been disabled", TRUE, TRUE);
			return;
		}

		if (!isset($search) || (strlen($search) < 2)) {
			git_message("You must enter search text of at least 2 characters", TRUE, TRUE);
			return;
		}
		if (!isset($hash)) {
			$hash = 'HEAD';
		}

		$co = $gitphp_current_project->GetCommit($hash);

		$revlist = git_read_revlist($hash, 101, ($page * 100), FALSE, FALSE, $searchtype, $search);
		if (count($revlist) < 1 || (strlen($revlist[0]) < 1)) {
			git_message("No matches for '" . $search . "'.", FALSE, TRUE);
			return;
		}

		$tpl->assign("hash",$hash);
		$tpl->assign("treehash", $co->GetTree()->GetHash());

		$tpl->assign("search",$search);
		$tpl->assign("searchtype",$searchtype);
		$tpl->assign("page",$page);
		$revlistcount = count($revlist);
		$tpl->assign("revlistcount",$revlistcount);

		$tpl->assign("title", $co->GetTitle());

		date_default_timezone_set('UTC');
		$commitlines = array();
		$commitcount = min(100,$revlistcount);
		for ($i = 0; $i < $commitcount; ++$i) {
			$commit = $revlist[$i];
			if (strlen(trim($commit)) > 0) {
				$commitline = array();
				$co2 = $gitphp_current_project->GetCommit($commit);
				$commitline["commit"] = $commit;
				$age = $co2->GetAge();
				if ($age > 60*60*24*7*2) {
					$commitline['agestringdate'] = date('Y-m-d', $co2->GetCommitterEpoch());
					$commitline['agestringage'] = age_string($age);
				} else {
					$commitline['agestringdate'] = age_string($age);
					$commitline['agestringage'] = date('Y-m-d', $co2->GetCommitterEpoch());
				}
				$commitline["authorname"] = $co2->GetAuthorName();
				$title = $co2->GetTitle();
				$titleshort = $co2->GetTitle(GITPHP_TRIM_LENGTH);
				$commitline["title_short"] = $titleshort;
				if (strlen($titleshort) < strlen($title))
					$commitline["title"] = $title;
				$commitline["committree"] = $co2->GetTree()->GetHash();
				$matches = array();
				$commentlines = $co2->GetComment();
				foreach ($commentlines as $comline) {
					$hl = highlight($comline, $search, "searchmatch", GITPHP_TRIM_LENGTH);
					if ($hl && (strlen($hl) > 0))
						$matches[] = $hl;
				}
				$commitline["matches"] = $matches;
				$commitlines[] = $commitline;
				unset($co2);
			}
		}
		
		$tpl->assign("commitlines",$commitlines);
	}
	$tpl->display('search.tpl', $cachekey);
}

?>
