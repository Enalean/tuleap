<?php
/*
 *  display.git_search.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - search
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

include_once('gitutil.git_read_commit.php');
include_once('gitutil.git_rev_list.php');

function git_search($projectroot, $project, $hash, $search, $searchtype, $page = 0)
{
	global $tpl,$gitphp_conf;

	if (!$gitphp_conf['search']) {
		$tpl->clear_all_assign();
		$tpl->assign("message","Search has been disabled");
		$tpl->display("message.tpl");
		return;
	}

	if (!isset($search) || (strlen($search) < 2)) {
		$tpl->clear_all_assign();
		$tpl->assign("error",TRUE);
		$tpl->assign("message","You must enter search text of at least 2 characters");
		$tpl->display("message.tpl");
		return;
	}
	if (!isset($hash)) {
		//$hash = git_read_head($projectroot . $project);
		$hash = "HEAD";
	}

	$co = git_read_commit($projectroot . $project, $hash);

	$revlist = explode("\n",trim(git_rev_list($projectroot . $project, $hash, 101, ($page * 100), FALSE, FALSE, $searchtype, $search)));

	if (count($revlist) < 1 || (strlen($revlist[0]) < 1)) {
		$tpl->clear_all_assign();
		$tpl->assign("message","No matches for '" . $search . "'.");
		$tpl->display("message.tpl");
		return;
	}

	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->assign("treehash",$co['tree']);
	$tpl->display("search_nav.tpl");

	$tpl->assign("search",$search);
	$tpl->assign("searchtype",$searchtype);
	if ($page > 0) {
		$tpl->assign("firstlink",TRUE);
		$tpl->assign("prevlink",TRUE);
		if ($page > 1)
			$tpl->assign("prevpage",$page-1);
	}
	if (count($revlist) > 100) {
		$tpl->assign("nextlink",TRUE);
		$tpl->assign("nextpage",$page+1);
	}
	$tpl->display("search_pagenav.tpl");

	$tpl->assign("title",$co['title']);
	$tpl->display("search_header.tpl");

	$alternate = FALSE;
	$commitcount = min(100,count($revlist));
	for ($i = 0; $i < $commitcount; $i++) {
		$tpl->clear_all_assign();
		$commit = $revlist[$i];
		if (strlen(trim($commit)) > 0) {
			$co2 = git_read_commit($projectroot . $project, $commit);
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			$tpl->assign("project",$project);
			$tpl->assign("commit",$commit);
			$tpl->assign("agestringage",$co2['age_string_age']);
			$tpl->assign("agestringdate",$co2['age_string_date']);
			$tpl->assign("authorname",$co2['author_name']);
			$tpl->assign("title_short",$co2['title_short']);
			if (strlen($co2['title_short']) < strlen($co2['title']))
				$tpl->assign("title",$co2['title']);
			$tpl->assign("committree",$co2['tree']);
			$matches = array();
			foreach ($co2['comment'] as $comline) {
				if (eregi("(.*)(" . quotemeta($search) . ")(.*)",$comline,$regs)) {
					$maxlen = 50;
					$linelen = strlen($regs[0]);
					if ($linelen > $maxlen) {
						$matchlen = strlen($regs[2]);
						$remain = floor(($maxlen - $matchlen) / 2);
						$leftlen = strlen($regs[1]);
						$rightlen = strlen($regs[3]);
						if ($leftlen > $remain) {
							$leftremain = $remain;
							if ($rightlen < $remain)
								$leftremain += ($remain - $rightlen);
							$regs[1] = "..." . substr($regs[1], ($leftlen - ($leftremain - 3)));
						}
						if ($rightlen > $remain) {
							$rightremain = $remain;
							if ($leftlen < $remain)
								$rightremain += ($remain - $leftlen);
							$regs[3] = substr($regs[3],0,$rightremain-3) . "...";
						}
					}
					$matches[] = $regs[1] . "<span class=\"searchmatch\">" . $regs[2] . "</span>" . $regs[3];
				}
			}
			$tpl->assign("matches",$matches);
			$tpl->display("search_item.tpl");
		}
	}

	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->assign("search",$search);
	$tpl->assign("searchtype",$searchtype);
	if (count($revlist) > 100) {
		$tpl->assign("nextlink",TRUE);
		$tpl->assign("nextpage",$page+1);
	}
	$tpl->display("search_footer.tpl");
}

?>
