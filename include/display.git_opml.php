<?php
/*
 *  display.git_opml.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - OPML feed
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('util.script_url.php');
 include_once('gitutil.git_read_projects.php');
 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_commit.php');

function git_opml($projectroot,$projectlist)
{
	global $tpl,$gitphp_conf;
	$projlist = git_read_projects($projectroot,$projectlist);
	header("Content-type: text/xml; charset=UTF-8");
	$tpl->clear_all_assign();
	$tpl->assign("title",$gitphp_conf['title']);
	$tpl->display("opml_header.tpl");
	echo "\n";
	foreach ($projlist as $cat => $plist) {
		if (is_array($plist)) {
			foreach ($plist as $i => $proj) {
				$head = git_read_head($projectroot . $proj);
				$co = git_read_commit($projectroot . $proj, $head);
				$tpl->clear_all_assign();
				$tpl->assign("proj",$proj);
				$tpl->assign("self",script_url());
				$tpl->display("opml_item.tpl");
				echo "\n";
			}
		} else {
			$head = git_read_head($projectroot . $plist);
			$co = git_read_commit($projectroot . $plist, $head);
			$tpl->clear_all_assign();
			$tpl->assign("proj",$plist);
			$tpl->assign("self",script_url());
			$tpl->display("opml_item.tpl");
			echo "\n";
		}
	}

	$tpl->clear_all_assign();
	$tpl->display("opml_footer.tpl");
}

?>
