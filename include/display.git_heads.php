<?php
/*
 *  display.git_heads.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - heads
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_refs.php');

function git_heads($projectroot,$project)
{
	global $tpl;
	$head = git_read_head($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("head",$head);
	$tpl->display("heads_nav.tpl");
	$tpl->display("heads_header.tpl");
	$taglist = git_read_refs($projectroot, $project, "refs/heads");
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
			$tpl->display("heads_item.tpl");
		}
	}
	$tpl->clear_all_assign();
	$tpl->display("heads_footer.tpl");
}

?>
