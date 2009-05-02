<?php
/*
 *  display.git_commitdiff_plain.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - commit diff (plaintext)
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('util.prep_tmpdir.php');
 include_once('util.date_str.php');
 include_once('gitutil.git_read_commit.php');
 include_once('gitutil.git_diff_tree.php');
 include_once('gitutil.git_rev_list.php');
 include_once('gitutil.read_info_ref.php');
 include_once('display.git_diff_print.php');

function git_commitdiff_plain($projectroot,$project,$hash,$hash_parent)
{
	global $gitphp_conf,$tpl;
	$ret = prep_tmpdir();
	if ($ret !== TRUE) {
		echo $ret;
		return;
	}
	$co = git_read_commit($projectroot . $project, $hash);
	if (!isset($hash_parent))
		$hash_parent = $co['parent'];
	$diffout = git_diff_tree($projectroot . $project, $hash_parent . " " . $hash);
	$difftree = explode("\n",$diffout);
	$refs = read_info_ref($projectroot . $project,"tags");
	$listout = git_rev_list($projectroot . $project, "HEAD");
	$tok = strtok($listout,"\n");
	while ($tok !== false) {
		if (isset($refs[$tok]))
			$tagname = $refs[$tok];
		if ($tok == $hash)
			break;
		$tok = strtok("\n");
	}
	header("Content-type: text/plain; charset=UTF-8");
	header("Content-disposition: inline; filename=\"git-" . $hash . ".patch\"");
	$ad = date_str($co['author_epoch'],$co['author_tz']);
	$tpl->clear_all_assign();
	$tpl->assign("from",$co['author']);
	$tpl->assign("date",$ad['rfc2822']);
	$tpl->assign("subject",$co['title']);
	if (isset($tagname))
		$tpl->assign("tagname",$tagname);
	$tpl->assign("url",$gitphp_conf['self'] . "?p=" . $project . "&a=commitdiff&h=" . $hash);
	$tpl->assign("comment",$co['comment']);
	$tpl->display("diff_plaintext.tpl");
	echo "\n\n";
	foreach ($difftree as $i => $line) {
		if (ereg("^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$",$line,$regs)) {
			if ($regs[5] == "A")
				git_diff_print($projectroot . $project, null, "/dev/null", $regs[4], "b/" . $regs[6], "plain");
			else if ($regs[5] == "D")
				git_diff_print($projectroot . $project, $regs[3], "a/" . $regs[6], null, "/dev/null", "plain");
			else if ($regs[5] == "M")
				git_diff_print($projectroot . $project, $regs[3], "a/" . $regs[6], $regs[4], "b/" . $regs[6], "plain");
		}
	}
}

?>
