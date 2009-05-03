<?php
/*
 *  display.git_shortlog.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - short log
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('util.date_str.php');
 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_revlist.php');
 include_once('gitutil.git_read_commit.php');
 include_once('gitutil.read_info_ref.php');

function git_shortlog($projectroot,$project,$hash,$page)
{
	global $tpl;
	$head = git_read_head($projectroot . $project);
	if (!isset($hash))
		$hash = $head;
	if (!isset($page))
		$page = 0;
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->display("shortlog_nav.tpl");

	$revlist = git_read_revlist($projectroot . $project, $hash, 101, ($page * 100));

	if (($hash != $head) || $page)
		$tpl->assign("headlink",TRUE);
	if ($page > 0) {
		$tpl->assign("prevlink",TRUE);
		$tpl->assign("prevpage",$page-1);
	}
	if (count($revlist) > 100) {
		$tpl->assign("nextlink",TRUE);
		$tpl->assign("nextpage",$page+1);
	}
	$tpl->display("shortlog_pagenav.tpl");

	$alternate = FALSE;
	$commitcount = min(100,count($revlist));
	for ($i = 0; $i < $commitcount; $i++) {
		$tpl->clear_all_assign();
		$commit = $revlist[$i];
		if (strlen(trim($commit)) > 0) {
			if (isset($refs[$commit]))
				$tpl->assign("commitref",$refs[$commit]);
			$co = git_read_commit($projectroot . $project, $commit);
			$ad = date_str($co['author_epoch']);
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			$tpl->assign("project",$project);
			$tpl->assign("commit",$commit);
			$tpl->assign("agestringage",$co['age_string_age']);
			$tpl->assign("agestringdate",$co['age_string_date']);
			$tpl->assign("authorname",$co['author_name']);
			$tpl->assign("title_short",$co['title_short']);
			if (strlen($co['title_short']) < strlen($co['title']))
				$tpl->assign("title",$co['title']);
			$tpl->display("shortlog_item.tpl");
		}
	}

	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	if (count($revlist) > 100) {
		$tpl->assign("nextlink",TRUE);
		$tpl->assign("nextpage",$page+1);
	}
	$tpl->display("shortlog_footer.tpl");
}

?>
