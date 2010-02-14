<?php
/*
 *  display.git_commitdiff_plain.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - commit diff (plaintext)
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.prep_tmpdir.php');
 require_once('util.date_str.php');
 require_once('util.script_url.php');
 require_once('gitutil.git_diff_tree.php');
 require_once('gitutil.git_read_revlist.php');
 require_once('gitutil.read_info_ref.php');
 require_once('gitutil.git_diff.php');

function git_commitdiff_plain($hash,$hash_parent)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . $hash . "|" . $hash_parent;

	header("Content-type: text/plain; charset=UTF-8");
	header("Content-disposition: inline; filename=\"git-" . $hash . ".patch\"");

	if (!$tpl->is_cached('diff_plaintext.tpl', $cachekey)) {
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
		$refs = read_info_ref('tags');
		$listout = git_read_revlist('HEAD');
		foreach ($listout as $i => $rev) {
			if (isset($refs[$rev]))
				$tagname = $refs[$rev];
			if ($rev == $hash)
				break;
		}
		$ad = date_str($co->GetAuthorEpoch(), $co->GetAuthorTimezone());
		$tpl->assign("from", $co->GetAuthor());
		$tpl->assign("date",$ad['rfc2822']);
		$tpl->assign("subject", $co->GetTitle());
		if (isset($tagname))
			$tpl->assign("tagname",$tagname);
		$tpl->assign("url",script_url() . "?p=" . $gitphp_current_project->GetProject() . "&a=commitdiff&h=" . $hash);
		$tpl->assign("comment", $co->GetComment());
		$diffs = array();
		foreach ($difftree as $i => $line) {
			if (preg_match("/^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$/",$line,$regs)) {
				if ($regs[5] == "A")
					$diffs[] = git_diff(null, "/dev/null", $regs[4], "b/" . $regs[6]);
				else if ($regs[5] == "D")
					$diffs[] = git_diff($regs[3], "a/" . $regs[6], null, "/dev/null");
				else if ($regs[5] == "M")
					$diffs[] = git_diff($regs[3], "a/" . $regs[6], $regs[4], "b/" . $regs[6]);
			}
		}
		$tpl->assign("diffs",$diffs);
	}
	$tpl->display('diff_plaintext.tpl', $cachekey);
}

?>
