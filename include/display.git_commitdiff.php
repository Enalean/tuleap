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
 require_once('gitutil.git_diff_tree.php');
 require_once('gitutil.read_info_ref.php');
 require_once('gitutil.git_diff.php');

function git_commitdiff($hash,$hash_parent)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . $hash . "|" . $hash_parent;

	if (!$tpl->is_cached('commitdiff.tpl', $cachekey)) {
		$ret = prep_tmpdir();
		if ($ret !== TRUE) {
			echo $ret;
			return;
		}
		$co = $gitphp_current_project->GetCommit($hash);
		if (!isset($hash_parent)) {
			$parent = $co->GetParent();
			if ($parent)
				$hash_parent = $parent->GetHash();
		}
		$diffout = git_diff_tree($hash_parent . " " . $hash);
		$difftree = explode("\n",$diffout);
		$refs = read_info_ref();
		$tpl->assign("hash",$hash);
		$tree = $co->GetTree();
		if ($tree)
			$tpl->assign("tree", $tree->GetHash());
		$tpl->assign("hashparent",$hash_parent);
		$tpl->assign("title", $co->GetTitle());
		if (isset($refs[$co->GetHash()]))
			$tpl->assign("commitref",$refs[$co->GetHash()]);
		$tpl->assign("comment",$co->GetComment());
		$difftreelines = array();
		foreach ($difftree as $i => $line) {
			if (preg_match("/^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$/",$line,$regs)) {
				$difftreeline = array();
				$difftreeline["from_mode"] = $regs[1];
				$difftreeline["to_mode"] = $regs[2];
				$difftreeline["from_id"] = $regs[3];
				$difftreeline["to_id"] = $regs[4];
				$difftreeline["status"] = $regs[5];
				$difftreeline["file"] = $regs[6];
				$difftreeline["from_type"] = file_type($regs[1]);
				$difftreeline["to_type"] = file_type($regs[2]);
				if ($regs[5] == "A")
					$difftreeline['diffout'] = explode("\n",git_diff(null,"/dev/null",$regs[4],"b/" . $regs[6]));
				else if ($regs[5] == "D")
					$difftreeline['diffout'] = explode("\n",git_diff($regs[3],"a/" . $regs[6],null,"/dev/null"));
				else if (($regs[5] == "M") && ($regs[3] != $regs[4]))
					$difftreeline['diffout'] = explode("\n",git_diff($regs[3],"a/" . $regs[6],$regs[4],"b/" . $regs[6]));
				$difftreelines[] = $difftreeline;
			}
		}
		$tpl->assign("difftreelines",$difftreelines);
	}
	$tpl->display('commitdiff.tpl', $cachekey);
}

?>
