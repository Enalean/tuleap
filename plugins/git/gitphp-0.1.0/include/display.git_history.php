<?php
/*
 *  display.git_history.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - history
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_get_hash_by_path.php');
 require_once('gitutil.git_read_head.php');
 require_once('gitutil.git_read_commit.php');
 require_once('gitutil.read_info_ref.php');
 require_once('gitutil.git_history_list.php');
 require_once('gitutil.git_path_trees.php');

function git_history($projectroot,$project,$hash,$file)
{
	global $tpl;

	$cachekey = sha1($project) . "|" . $hash . "|" . sha1($file);

	if (!$tpl->is_cached('history.tpl', $cachekey)) {
		if (!isset($hash))
			$hash = git_read_head($projectroot . $project);
		$co = git_read_commit($projectroot . $project, $hash);
		$refs = read_info_ref($projectroot . $project);
		$tpl->assign("hash",$hash);
		if (isset($refs[$hash]))
			$tpl->assign("hashbaseref",$refs[$hash]);
		$tpl->assign("tree",$co['tree']);
		$tpl->assign("title",$co['title']);
		$paths = git_path_trees($projectroot . $project, $hash, $file);
		$tpl->assign("paths",$paths);
		$cmdout = git_history_list($projectroot . $project, $hash, $file);
		$lines = explode("\n", $cmdout);
		$historylines = array();
		foreach ($lines as $i => $line) {
			if (preg_match("/^([0-9a-fA-F]{40})/",$line,$regs))
				$commit = $regs[1];
			else if (preg_match("/:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$/",$line,$regs) && isset($commit)) {
					$historyline = array();
					$co = git_read_commit($projectroot . $project, $commit);
					$historyline["agestringage"] = $co['age_string_age'];
					$historyline["agestringdate"] = $co['age_string_date'];
					$historyline["authorname"] = $co['author_name'];
					$historyline["commit"] = $commit;
					$historyline["file"] = $file;
					$historyline["title"] = $co['title_short'];
					if (isset($refs[$commit]))
						$historyline["commitref"] = $refs[$commit];
					$blob = git_get_hash_by_path($projectroot . $project, $hash,$file);
					$blob_parent = git_get_hash_by_path($projectroot . $project, $commit,$file);
					if ($blob && $blob_parent && ($blob != $blob_parent)) {
						$historyline["blob"] = $blob;
						$historyline["blobparent"] = $blob_parent;
					}
					$historylines[] = $historyline;
					unset($commit);
			}
		}
		$tpl->assign("historylines",$historylines);
	}
	$tpl->display('history.tpl', $cachekey);
}

?>
