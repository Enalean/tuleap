<?php
/*
 *  display.git_rss.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - RSS feed
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('defs.constants.php');
 include_once('util.date_str.php');
 include_once('util.script_url.php');
 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_revlist.php');
 include_once('gitutil.git_read_commit.php');
 include_once('gitutil.git_diff_tree.php');

function git_rss($projectroot,$project)
{
	global $tpl;
	$head = git_read_head($projectroot . $project);
	$revlist = git_read_revlist($projectroot . $project, $head, GITPHP_RSS_ITEMS);
	header("Content-type: text/xml; charset=UTF-8");
	$tpl->clear_all_assign();
	$tpl->assign("self",script_url());
	$tpl->assign("project",$project);
	$tpl->display("rss_header.tpl");

	for ($i = 0; $i <= count($revlist); $i++) {
		$commit = $revlist[$i];
		$co = git_read_commit($projectroot . $project, $commit);
		if (($i >= 20) && ((time() - $co['committer_epoch']) > 48*60*60))
			break;
		$cd = date_str($co['committer_epoch']);
		$difftree = array();
		$diffout = git_diff_tree($projectroot . $project, $co['parent'] . " " . $co['id']);
		$tok = strtok($diffout,"\n");
		while ($tok !== false) {
			if (ereg("^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)([0-9]{0,3})\t(.*)$",$tok,$regs))
				$difftree[] = $regs[7];
			$tok = strtok("\n");
		}
		$tpl->clear_all_assign();
		$tpl->assign("cdmday",$cd['mday']);
		$tpl->assign("cdmonth",$cd['month']);
		$tpl->assign("cdhour",$cd['hour']);
		$tpl->assign("cdminute",$cd['minute']);
		$tpl->assign("title",htmlentities($co['title']));
		$tpl->assign("author",htmlentities($co['author']));
		$tpl->assign("cdrfc2822",$cd['rfc2822']);
		$tpl->assign("self",script_url());
		$tpl->assign("project",$project);
		$tpl->assign("commit",$commit);
		$tpl->assign("comment",$co['comment']);
		$tpl->assign("difftree",$difftree);
		$tpl->display("rss_item.tpl");
	}

	$tpl->clear_all_assign();
	$tpl->display("rss_footer.tpl");
}

?>
