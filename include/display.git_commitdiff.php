<?php
/*
 *  display.git_commitdiff.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - commit diff
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.file_type.php');
 require_once('util.prep_tmpdir.php');
 require_once('gitutil.git_read_commit.php');
 require_once('gitutil.git_diff_tree.php');
 require_once('gitutil.read_info_ref.php');
 require_once('display.git_diff_print.php');

function git_commitdiff($projectroot,$project,$hash,$hash_parent)
{
	global $tpl;
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
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->assign("tree",$co['tree']);
	$tpl->assign("hashparent",$hash_parent);
	$tpl->display("commitdiff_nav.tpl");
	$tpl->assign("title",$co['title']);
	if (isset($refs[$co['id']]))
		$tpl->assign("commitref",$refs[$co['id']]);
	$tpl->assign("comment",$co['comment']);
	$tpl->display("commitdiff_header.tpl");

	foreach ($difftree as $i => $line) {
		if (ereg("^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$",$line,$regs)) {
			$tpl->clear_all_assign();
			$tpl->assign("project",$project);
			$tpl->assign("hash",$hash);
			$tpl->assign("from_mode",$regs[1]);
			$tpl->assign("to_mode",$regs[2]);
			$tpl->assign("from_id",$regs[3]);
			$tpl->assign("to_id",$regs[4]);
			$tpl->assign("status",$regs[5]);
			$tpl->assign("file",$regs[6]);
			$tpl->assign("from_type",file_type($regs[1]));
			$tpl->assign("to_type",file_type($regs[2]));
			$tpl->display("commitdiff_item.tpl");
			if ($regs[5] == "A")
				git_diff_print($projectroot . $project, null,"/dev/null",$regs[4],"b/" . $regs[6]);
			else if ($regs[5] == "D")
				git_diff_print($projectroot . $project, $regs[3],"a/" . $regs[6],null,"/dev/null");
			else if (($regs[5] == "M") && ($regs[3] != $regs[4]))
				git_diff_print($projectroot . $project, $regs[3],"a/" . $regs[6],$regs[4],"b/" . $regs[6]);
		}
	}

	$tpl->clear_all_assign();
	$tpl->display("commitdiff_footer.tpl");
}

?>
