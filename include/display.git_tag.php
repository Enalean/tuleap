<?php
/*
 *  display.git_tag.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - tag
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.date_str.php');
require_once(GITPHP_INCLUDEDIR . 'git/Tag.class.php');

function git_tag($hash)
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject()) . "|" . sha1($hash);

	if (!$tpl->is_cached('tag.tpl', $cachekey)) {

		$head = $gitphp_current_project->GetHeadCommit()->GetHash();
		$tpl->assign("head",$head);
		$tpl->assign("hash", $hash);

		$tag = new GitPHP_Tag($gitphp_current_project, $hash);

		$tpl->assign("tag", $tag);
		$tagger = $tag->GetTagger();
		if (!empty($tagger)) {
			$ad = date_str($tag->GetTaggerEpoch(), $tag->GetTaggerTimezone());
			$tpl->assign("datedata",$ad);
		}
	}
	$tpl->display('tag.tpl', $cachekey);
}

?>
