<?php
/*
 * display.git_blame.php
 * gitphp: A PHP git repository browser
 * Component: Display - blame
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 */

require_once('gitutil.git_parse_blame.php');
require_once('gitutil.read_info_ref.php');
require_once('gitutil.git_path_trees.php');

function git_blame($hash, $file, $hashbase)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . sha1($file) . "|" . $hashbase;

	if (!$tpl->is_cached('blame.tpl', $cachekey)) {
		$head = $gitphp_current_project->GetHeadCommit()->GetHash();
		if (!isset($hashbase))
			$hashbase = $head;
		if (!isset($hash) && isset($file))
			$hash = git_get_hash_by_path($hashbase,$file,"blob");
		$tpl->assign("hash",$hash);
		$tpl->assign("hashbase",$hashbase);
		$tpl->assign("head", $head);
		$co = $gitphp_current_project->GetCommit($hashbase);
		if ($co) {
			$tpl->assign("fullnav",TRUE);
			$refs = read_info_ref();
			$tpl->assign("tree",$co->GetTree()->GetHash());
			$tpl->assign("title",$co->GetTitle());
			if (isset($file))
				$tpl->assign("file",$file);
			if ($hashbase == "HEAD") {
				if (isset($refs[$head]))
					$tpl->assign("hashbaseref",$refs[$head]);
			} else {
				if (isset($refs[$hashbase]))
					$tpl->assign("hashbaseref",$refs[$hashbase]);
			}
		}
		$paths = git_path_trees($hashbase, $file);
		$tpl->assign("paths",$paths);

		$blamedata = git_parse_blame($file, $hashbase);
		$tpl->assign("blamedata",$blamedata);

	}

	$tpl->display('blame.tpl', $cachekey);
}

?>
