<?php
/*
 *  display.git_summary.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - summary page
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once(GITPHP_INCLUDEDIR . 'defs.constants.php');
 require_once(GITPHP_INCLUDEDIR . 'util.date_str.php');
 require_once(GITPHP_INCLUDEDIR . 'util.age_string.php');
 require_once(GITPHP_INCLUDEDIR . 'gitutil.git_read_revlist.php');
 require_once(GITPHP_INCLUDEDIR . 'gitutil.read_info_ref.php');
 require_once(GITPHP_INCLUDEDIR . 'git/Project.class.php');

function git_summary()
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$cachekey = sha1($gitphp_current_project->GetProject());

	if (!$tpl->is_cached('project.tpl', $cachekey)) {
		$projectroot = GitPHP_Config::GetInstance()->GetValue('projectroot');

		$descr = $gitphp_current_project->GetDescription();
		$headCommit = $gitphp_current_project->GetHeadCommit();
		$commitdate = date_str($headCommit->GetCommitterEpoch(), $headCommit->GetCommitterTimezone());
		$owner = $gitphp_current_project->GetOwner();
		$refs = read_info_ref();
		$tpl->assign("head", $headCommit->GetHash());
		$tpl->assign("description",$descr);
		$tpl->assign("owner",$owner);
		$tpl->assign("lastchange",$commitdate['rfc2822']);
		if (GitPHP_Config::GetInstance()->HasKey('cloneurl'))
			$tpl->assign('cloneurl', GitPHP_Config::GetInstance()->GetValue('cloneurl') . $gitphp_current_project->GetProject());
		if (GitPHP_Config::GetInstance()->HasKey('pushurl'))
			$tpl->assign('pushurl', GitPHP_Config::GetInstance()->GetValue('pushurl') . $gitphp_current_project->GetProject());
		$revlist = git_read_revlist($headCommit->GetHash(), 17);
		foreach ($revlist as $i => $rev) {
			$revdata = array();
			$revdata["commit"] = $rev;
			if (isset($refs[$rev]))
				$revdata["commitref"] = $refs[$rev];
			$revco = $gitphp_current_project->GetCommit($rev);
			if ($revco) {
				$revdata["commitage"] = age_string($revco->GetAge());
				$revdata["commitauthor"] = $revco->GetAuthorName();
				$title = $revco->GetTitle();
				$title_short = $revco->GetTitle(GITPHP_TRIM_LENGTH);
				if (strlen($title_short) < strlen($title)) {
					$revdata["title"] = $title;
					$revdata["title_short"] = $title_short;
				} else
					$revdata["title_short"] = $title;
				unset($revco);
			}
			$revlist[$i] = $revdata;
		}
		$tpl->assign("revlist",$revlist);

		$taglist = $gitphp_current_project->GetTags();
		if (isset($taglist) && (count($taglist) > 0)) {
			$tpl->assign("taglist",$taglist);
		}

		$headlist = $gitphp_current_project->GetHeads();
		if (isset($headlist) && (count($headlist) > 0)) {
			$tpl->assign("headlist",$headlist);
		}
	}
	$tpl->display('project.tpl', $cachekey);
}

?>
