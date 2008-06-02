<?php
/*
 *  display.git_summary.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - summary page
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('util.date_str.php');
 include_once('gitutil.git_project_descr.php');
 include_once('gitutil.git_project_owner.php');
 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_commit.php');
 include_once('gitutil.git_read_revlist.php');
 include_once('gitutil.git_read_refs.php');
 include_once('gitutil.read_info_ref.php');

function git_summary($projectroot,$project)
{
	global $tpl;
	$descr = git_project_descr($projectroot,$project);
	$head = git_read_head($projectroot . $project);
	$commit = git_read_commit($projectroot . $project, $head);
	$commitdate = date_str($commit['committer_epoch'],$commit['committer_tz']);
	$owner = git_project_owner($projectroot,$project);
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("head",$head);
	$tpl->display("project_nav.tpl");
	$tpl->clear_all_assign();
	$tpl->assign("description",$descr);
	$tpl->assign("owner",$owner);
	$tpl->assign("lastchange",$commitdate['rfc2822']);
	$tpl->display("project_brief.tpl");
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->display("project_revlist_header.tpl");
	$revlist = git_read_revlist($projectroot . $project, $head, 17);
	$alternate = FALSE;
	foreach ($revlist as $i => $rev) {
		$tpl->clear_all_assign();
		$revco = git_read_commit($projectroot . $project, $rev);
		$authordate = date_str($revco['author_epoch']);
		if ($alternate)
			$tpl->assign("class","dark");
		else
			$tpl->assign("class","light");
		$alternate = !$alternate;
		$tpl->assign("project",$project);
		if ($i <= 16) {
			$tpl->assign("commit",$rev);
			if (isset($refs[$rev]))
				$tpl->assign("commitref",$refs[$rev]);
			$tpl->assign("commitage",$revco['age_string']);
			$tpl->assign("commitauthor",$revco['author_name']);
			if (strlen($revco['title_short']) < strlen($revco['title'])) {
				$tpl->assign("title",$revco['title']);
				$tpl->assign("title_short",$revco['title_short']);
			} else
				$tpl->assign("title_short",$revco['title']);
		} else {
			$tpl->assign("truncate",TRUE);
		}
		$tpl->display("project_revlist_item.tpl");
	}
	$tpl->clear_all_assign();
	$tpl->display("project_revlist_footer.tpl");

	$taglist = git_read_refs($projectroot,$project,"refs/tags");
	if (isset($taglist) && (count($taglist) > 0)) {
		$tpl->clear_all_assign();
		$tpl->assign("project",$project);
		$tpl->display("project_taglist_header.tpl");
		$alternate = FALSE;
		foreach ($taglist as $i => $tag) {
			$tpl->clear_all_assign();
			$tpl->assign("project",$project);
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			if ($i < 16) {
				$tpl->assign("tagage",$tag['age']);
				$tpl->assign("tagname",$tag['name']);
				$tpl->assign("tagid",$tag['id']);
				$tpl->assign("tagtype",$tag['type']);
				$tpl->assign("refid",$tag['refid']);
				$tpl->assign("reftype",$tag['reftype']);
				if (isset($tag['comment']))
					$tpl->assign("comment",$tag['comment']);
			} else
				$tpl->assign("truncate",TRUE);
			$tpl->display("project_taglist_item.tpl");
		}
		$tpl->clear_all_assign();
		$tpl->display("project_taglist_footer.tpl");
	}

	$headlist = git_read_refs($projectroot,$project,"refs/heads");
	if (isset($headlist) && (count($headlist) > 0)) {
		$tpl->clear_all_assign();
		$tpl->assign("project",$project);
		$tpl->display("project_headlist_header.tpl");
		$alternate = FALSE;
		foreach ($headlist as $i => $head) {
			$tpl->clear_all_assign();
			$tpl->assign("project",$project);
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			if ($i < 16) {
				$tpl->assign("headage",$head['age']);
				$tpl->assign("headname",$head['name']);
			} else
				$tpl->assign("truncate",TRUE);
			$tpl->display("project_headlist_item.tpl");
		}

		$tpl->clear_all_assign();
		$tpl->display("project_headlist_footer.tpl");
	}
}

?>
