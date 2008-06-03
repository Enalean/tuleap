<?php
/*
 *  display.git_history.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - history
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('gitutil.git_get_hash_by_path.php');
 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_commit.php');
 include_once('gitutil.read_info_ref.php');
 include_once('gitutil.git_history_list.php');

function git_history($projectroot,$project,$hash,$file)
{
	global $tpl;
	if (!isset($hash))
		$hash = git_read_head($projectroot . $project);
	$co = git_read_commit($projectroot . $project, $hash);
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->assign("tree",$co['tree']);
	$tpl->display("history_nav.tpl");
	$tpl->assign("title",$co['title']);
	$tpl->assign("file",$file);
	$tpl->display("history_header.tpl");
	$cmdout = git_history_list($projectroot . $project, $hash, $file);
	$alternate = FALSE;
	foreach ($cmdout as $i => $line) {
		if (ereg("^([0-9a-fA-F]{40})",$line,$regs))
			$commit = $regs[1];
		else if (ereg(":([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$",$line,$regs) && isset($commit)) {
				$co = git_read_commit($projectroot . $project, $commit);
				$tpl->clear_all_assign();
				if ($alternate)
					$tpl->assign("class","dark");
				else
					$tpl->assign("class","light");
				$alternate = !$alternate;
				$tpl->assign("project",$project);
				$tpl->assign("agestringage",$co['age_string_age']);
				$tpl->assign("agestringdate",$co['age_string_date']);
				$tpl->assign("authorname",$co['author_name']);
				$tpl->assign("commit",$commit);
				$tpl->assign("file",$file);
				$tpl->assign("title",$co['title_short']);
				if (isset($refs[$commit]))
					$tpl->assign("commitref",$refs[$commit]);
				$blob = git_get_hash_by_path($projectroot . $project, $hash,$file);
				$blob_parent = git_get_hash_by_path($projectroot . $project, $commit,$file);
				if ($blob && $blob_parent && ($blob != $blob_parent)) {
					$tpl->assign("blob",$blob);
					$tpl->assign("blobparent",$blob_parent);
					$tpl->assign("difftocurrent",TRUE);
				}
				$tpl->display("history_item.tpl");
				unset($commit);
		}
	}
	$tpl->clear_all_assign();
	$tpl->display("history_footer.tpl");
}

?>
