<?php
/*
 *  display.git_tags.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - tags
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_refs.php');

function git_tags($projectroot,$project)
{
	global $tpl;
	$head = git_read_head($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("head",$head);
	$tpl->display("tags_nav.tpl");
	$tpl->display("tags_header.tpl");
	$taglist = git_read_refs($projectroot, $project, "refs/tags");
	if (isset($taglist) && (count($taglist) > 0)) {
		$alternate = FALSE;
		foreach ($taglist as $i => $entry) {
			$tpl->clear_all_assign();
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			$tpl->assign("project",$project);
			$tpl->assign("age",$entry['age']);
			$tpl->assign("name",$entry['name']);
			$tpl->assign("reftype",$entry['reftype']);
			$tpl->assign("refid",$entry['refid']);
			$tpl->assign("id",$entry['id']);
			$tpl->assign("type",$entry['type']);
			if (isset($entry['comment']) && isset($entry['comment'][0]))
				$tpl->assign("comment",$entry['comment'][0]);
			$tpl->display("tags_item.tpl");
		}
	}
	$tpl->clear_all_assign();
	$tpl->display("tags_footer.tpl");
}

?>
