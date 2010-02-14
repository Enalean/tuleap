<?php
/*
 *  display.git_history.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - history
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once(GITPHP_INCLUDEDIR . 'defs.constants.php');
 require_once('gitutil.git_get_hash_by_path.php');
 require_once('gitutil.read_info_ref.php');
 require_once('gitutil.git_history_list.php');
 require_once('gitutil.git_path_trees.php');

function git_history($hash,$file)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . $hash . "|" . sha1($file);

	if (!$tpl->is_cached('history.tpl', $cachekey)) {
		if (!isset($hash))
			$hash = $gitphp_current_project->GetHeadCommit()->GetHash();

		$co = $gitphp_current_project->GetCommit($hash);
		$refs = read_info_ref();
		$tpl->assign("hash",$hash);
		if (isset($refs[$hash]))
			$tpl->assign("hashbaseref",$refs[$hash]);
		$tpl->assign("tree", $co->GetTree()->GetHash());
		$tpl->assign("title", $co->GetTitle());
		$paths = git_path_trees($hash, $file);
		$tpl->assign("paths",$paths);
		date_default_timezone_set('UTC');
		$cmdout = git_history_list($hash, $file);
		$lines = explode("\n", $cmdout);
		$historylines = array();
		foreach ($lines as $i => $line) {
			if (preg_match("/^([0-9a-fA-F]{40})/",$line,$regs))
				$commit = $regs[1];
			else if (preg_match("/:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$/",$line,$regs) && isset($commit)) {
					$historyline = array();
					$co2 = $gitphp_current_project->GetCommit($commit);
					$age = $co2->GetAge();
					if ($age > 60*60*24*7*2) {
						$historyline['agestringdate'] = date('Y-m-d', $co2->GetCommitterEpoch());
						$historyline['agestringage'] = age_string($age);
					} else {
						$historyline['agestringdate'] = age_string($age);
						$historyline['agestringage'] = date('Y-m-d', $co2->GetCommitterEpoch());
					}
					$historyline["authorname"] = $co2->GetAuthorName();
					$historyline["commit"] = $commit;
					$historyline["file"] = $file;
					$historyline["title"] = $co2->GetTitle(GITPHP_TRIM_LENGTH);
					if (isset($refs[$commit]))
						$historyline["commitref"] = $refs[$commit];
					$blob = git_get_hash_by_path($hash,$file);
					$blob_parent = git_get_hash_by_path($commit,$file);
					if ($blob && $blob_parent && ($blob != $blob_parent)) {
						$historyline["blob"] = $blob;
						$historyline["blobparent"] = $blob_parent;
					}
					$historylines[] = $historyline;
					unset($co2);
					unset($commit);
			}
		}
		$tpl->assign("historylines",$historylines);
	}
	$tpl->display('history.tpl', $cachekey);
}

?>
