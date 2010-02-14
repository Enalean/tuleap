<?php
/*
 *  display.git_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - tree
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.mode_str.php');
 require_once('gitutil.git_get_hash_by_path.php');
 require_once('gitutil.git_ls_tree.php');
 require_once('gitutil.read_info_ref.php');
 require_once('gitutil.git_path_trees.php');

function git_tree($hash,$file,$hashbase)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . $hashbase . "|" . $hash . "|" . sha1($file);

	if (!$tpl->is_cached('tree.tpl', $cachekey)) {
		
		if (!isset($hash)) {
			$hash = $gitphp_current_project->GetHeadCommit()->GetHash();
			if (isset($file))
				$hash = git_get_hash_by_path(($hashbase?$hashbase:$hash),$file,"tree");
		}
		if (!isset($hashbase))
			$hashbase = $hash;
		$lsout = git_ls_tree($hash, TRUE);
		$refs = read_info_ref();
		$tpl->assign("hash",$hash);
		if (isset($hashbase))
			$tpl->assign("hashbase",$hashbase);
		if (isset($hashbase)) {
			$co = $gitphp_current_project->GetCommit($hashbase);
			if ($co) {
				$tpl->assign("fullnav",TRUE);
				$tpl->assign("title",$co->GetTitle());
				if (isset($refs[$hashbase]))
					$tpl->assign("hashbaseref",$refs[$hashbase]);
			}
		}
		$paths = git_path_trees($hashbase, $file);
		$tpl->assign("paths",$paths);

		if (isset($file))
			$tpl->assign("base",$file . "/");

		$treelines = array();
		$tok = strtok($lsout,"\0");
		while ($tok !== false) {
			if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$/",$tok,$regs)) {
				$treeline = array();
				$treeline["filemode"] = mode_str($regs[1]);
				$treeline["type"] = $regs[2];
				$treeline["hash"] = $regs[3];
				$treeline["name"] = $regs[4];
				$treelines[] = $treeline;
			}
			$tok = strtok("\0");
		}
		$tpl->assign("treelines",$treelines);
	}
	$tpl->display('tree.tpl', $cachekey);
}

?>
