<?php
/*
 *  display.git_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - tree
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.mode_str.php');
 require_once('gitutil.git_read_head.php');
 require_once('gitutil.git_get_hash_by_path.php');
 require_once('gitutil.git_ls_tree.php');
 require_once('gitutil.read_info_ref.php');
 require_once('gitutil.git_read_commit.php');

function git_tree($projectroot,$project,$hash,$file,$hashbase)
{
	global $tpl;
	if (!isset($hash)) {
		$hash = git_read_head($projectroot . $project);
		if (isset($file))
			$hash = git_get_hash_by_path($projectroot . $project, ($hashbase?$hashbase:$hash),$file,"tree");
			if (!isset($hashbase))
				$hashbase = $hash;
	}
	$lsout = git_ls_tree($projectroot . $project, $hash, TRUE);
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	if (isset($hashbase) && ($co = git_read_commit($projectroot . $project, $hashbase))) {
		$basekey = $hashbase;
		$tpl->assign("hashbase",$hashbase);
		$tpl->assign("project",$project);
		$tpl->assign("title",$co['title']);
		if (isset($refs[$hashbase]))
			$tpl->assign("hashbaseref",$refs[$hashbase]);
		$tpl->display("tree_nav.tpl");
	} else {
		$tpl->assign("hash",$hash);
		$tpl->display("tree_emptynav.tpl");
	}
	$tpl->clear_all_assign();
	if (isset($file))
		$tpl->assign("filename",$file);
	$tpl->display("tree_filelist_header.tpl");

	$tok = strtok($lsout,"\0");
	$alternate = FALSE;
	while ($tok !== false) {
		if (ereg("^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$",$tok,$regs)) {
			$tpl->clear_all_assign();
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			$tpl->assign("filemode",mode_str($regs[1]));
			$tpl->assign("type",$regs[2]);
			$tpl->assign("hash",$regs[3]);
			$tpl->assign("name",$regs[4]);
			$tpl->assign("project",$project);
			if (isset($file))
				$tpl->assign("base",$file . "/");
			if (isset($basekey))
				$tpl->assign("hashbase",$basekey);
			$tpl->display("tree_filelist_item.tpl");
		}
		$tok = strtok("\0");
	}

	$tpl->clear_all_assign();
	$tpl->display("tree_filelist_footer.tpl");
}

?>
