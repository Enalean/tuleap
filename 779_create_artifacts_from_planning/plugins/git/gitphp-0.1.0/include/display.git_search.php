<?php
/*
 *  display.git_search.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - search
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('defs.constants.php');
require_once('util.highlight.php');
require_once('gitutil.git_read_commit.php');
require_once('gitutil.git_read_revlist.php');
require_once('display.git_message.php');

function git_search($projectroot, $project, $hash, $search, $searchtype, $page = 0)
{
	global $tpl,$gitphp_conf;

	$cachekey = sha1($project) . "|" . $hash . "|" . sha1($searchtype) . "|" . sha1($search) . "|" . (isset($page) ? $page : 0);

	if (!$tpl->is_cached('search.tpl', $cachekey)) {

		if (!$gitphp_conf['search']) {
			git_message("Search has been disabled", TRUE, TRUE);
			return;
		}

		if (!isset($search) || (strlen($search) < 2)) {
			git_message("You must enter search text of at least 2 characters", TRUE, TRUE);
			return;
		}
		if (!isset($hash)) {
			//$hash = git_read_head($projectroot . $project);
			$hash = "HEAD";
		}

		$co = git_read_commit($projectroot . $project, $hash);

		$revlist = git_read_revlist($projectroot . $project, $hash, 101, ($page * 100), FALSE, FALSE, $searchtype, $search);
		if (count($revlist) < 1 || (strlen($revlist[0]) < 1)) {
			git_message("No matches for '" . $search . "'.", FALSE, TRUE);
			return;
		}

		$tpl->assign("hash",$hash);
		$tpl->assign("treehash",$co['tree']);

		$tpl->assign("search",$search);
		$tpl->assign("searchtype",$searchtype);
		$tpl->assign("page",$page);
		$revlistcount = count($revlist);
		$tpl->assign("revlistcount",$revlistcount);

		$tpl->assign("title",$co['title']);

		$commitlines = array();
		$commitcount = min(100,$revlistcount);
		for ($i = 0; $i < $commitcount; ++$i) {
			$commit = $revlist[$i];
			if (strlen(trim($commit)) > 0) {
				$commitline = array();
				$co2 = git_read_commit($projectroot . $project, $commit);
				$commitline["commit"] = $commit;
				$commitline["agestringage"] = $co2['age_string_age'];
				$commitline["agestringdate"] = $co2['age_string_date'];
				$commitline["authorname"] = $co2['author_name'];
				$commitline["title_short"] = $co2['title_short'];
				if (strlen($co2['title_short']) < strlen($co2['title']))
					$commitline["title"] = $co2['title'];
				$commitline["committree"] = $co2['tree'];
				$matches = array();
				foreach ($co2['comment'] as $comline) {
					$hl = highlight($comline, $search, "searchmatch", GITPHP_TRIM_LENGTH);
					if ($hl && (strlen($hl) > 0))
						$matches[] = $hl;
				}
				$commitline["matches"] = $matches;
				$commitlines[] = $commitline;
			}
		}
		
		$tpl->assign("commitlines",$commitlines);
	}
	$tpl->display('search.tpl', $cachekey);
}

?>
