<?php
/*
 *  display.git_log.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - log
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('util.date_str.php');
 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_revlist.php');
 include_once('gitutil.git_read_commit.php');
 include_once('gitutil.read_info_ref.php');

function git_log($projectroot,$project,$hash,$page)
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
	$tpl->display("log_nav.tpl");

	$revlist = git_read_revlist($projectroot . $project, $hash, (100 * ($page+1)));

	if (($hash != $head) || $page)
		$tpl->assign("headlink",TRUE);
	if ($page > 0) {
		$tpl->assign("prevlink",TRUE);
		$tpl->assign("prevpage",$page-1);
	}
	if (count($revlist) >= (100 * ($page+1)-1)) {
		$tpl->assign("nextlink",TRUE);
		$tpl->assign("nextpage",$page+1);
	}
	$tpl->display("log_pagenav.tpl");

	if (!$revlist) {
		$tpl->clear_all_assign();
		$tpl->assign("project",$project);
		$co = git_read_commit($hash);
		$tpl->assign("age_string",$co['age_string']);
		$tpl->display("log_info.tpl");
	}
	for ($i = ($page * 100); $i < count($revlist); $i++) {
		$commit = $revlist[$i];
		if (isset($commit) && strlen($commit) > 1) {
			$tpl->clear_all_assign();
			$co = git_read_commit($projectroot . $project, $commit);
			$ad = date_str($co['author_epoch']);
			$tpl->assign("project",$project);
			$tpl->assign("commit",$commit);
			if (isset($refs[$commit]))
				$tpl->assign("commitref",$refs[$commit]);
			$tpl->assign("agestring",$co['age_string']);
			$tpl->assign("title",$co['title']);
			$tpl->assign("authorname",$co['author_name']);
			$tpl->assign("rfc2822",$ad['rfc2822']);
			$tpl->assign("comment",$co['comment']);
			if (count($co['comment']) > 0)
				$tpl->assign("notempty",TRUE);
			$tpl->display("log_item.tpl");
		}
	}
}

?>
