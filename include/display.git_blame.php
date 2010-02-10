<?php
/*
 * display.git_blame.php
 * gitphp: A PHP git repository browser
 * Component: Display - blame
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 */

require_once('gitutil.git_read_head.php');
require_once('gitutil.git_parse_blame.php');
require_once('gitutil.git_read_commit.php');
require_once('gitutil.read_info_ref.php');
require_once('gitutil.git_path_trees.php');

function git_blame($projectroot, $project, $hash, $file, $hashbase)
{
	global $tpl;

	$cachekey = sha1($project) . "|" . sha1($file) . "|" . $hashbase;

	if (!$tpl->is_cached('blame.tpl', $cachekey)) {
		$head = git_read_head($projectroot . $project);
		if (!isset($hashbase))
			$hashbase = $head;
		if (!isset($hash) && isset($file))
			$hash = git_get_hash_by_path($projectroot . $project, $hashbase,$file,"blob");
		$tpl->assign("hash",$hash);
		$tpl->assign("hashbase",$hashbase);
		$tpl->assign("head", $head);
		if ($co = git_read_commit($projectroot . $project, $hashbase)) {
			$tpl->assign("fullnav",TRUE);
			$refs = read_info_ref($projectroot . $project);
			$tpl->assign("tree",$co['tree']);
			$tpl->assign("title",$co['title']);
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
		$paths = git_path_trees($projectroot . $project, $hashbase, $file);
		$tpl->assign("paths",$paths);

		$blamedata = git_parse_blame($projectroot . $project, $file, $hashbase);
		$tpl->assign("blamedata",$blamedata);

	}

	$tpl->display('blame.tpl', $cachekey);
}

?>
