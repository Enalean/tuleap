<?php
/*
 *  display.git_summary.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - summary page
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.date_str.php');
 require_once('gitutil.git_project_descr.php');
 require_once('gitutil.git_project_owner.php');
 require_once('gitutil.git_read_head.php');
 require_once('gitutil.git_read_commit.php');
 require_once('gitutil.git_read_revlist.php');
 require_once('gitutil.git_read_refs.php');
 require_once('gitutil.read_info_ref.php');

function git_summary($projectroot,$project)
{
	global $tpl,$gitphp_conf;

	$cachekey = sha1($project);

	if (!$tpl->is_cached('project.tpl', $cachekey)) {
		$descr = git_project_descr($projectroot,$project);
		$head = git_read_head($projectroot . $project);
		$commit = git_read_commit($projectroot . $project, $head);
		$commitdate = date_str($commit['committer_epoch'],$commit['committer_tz']);
		$owner = git_project_owner($projectroot,$project);
		$refs = read_info_ref($projectroot . $project);
		$tpl->assign("head",$head);
		$tpl->assign("description",$descr);
		$tpl->assign("owner",$owner);
		$tpl->assign("lastchange",$commitdate['rfc2822']);
		if (isset($gitphp_conf['cloneurl']))
			$tpl->assign('cloneurl', $gitphp_conf['cloneurl'] . $project);
		if (isset($gitphp_conf['pushurl']))
			$tpl->assign('pushurl', $gitphp_conf['pushurl'] . $project);
		$revlist = git_read_revlist($projectroot . $project, $head, 17);
		foreach ($revlist as $i => $rev) {
			$revdata = array();
			$revco = git_read_commit($projectroot . $project, $rev);
			$authordate = date_str($revco['author_epoch']);
			$revdata["commit"] = $rev;
			if (isset($refs[$rev]))
				$revdata["commitref"] = $refs[$rev];
			$revdata["commitage"] = $revco['age_string'];
			$revdata["commitauthor"] = $revco['author_name'];
			if (strlen($revco['title_short']) < strlen($revco['title'])) {
				$revdata["title"] = $revco['title'];
				$revdata["title_short"] = $revco['title_short'];
			} else
				$revdata["title_short"] = $revco['title'];
			$revlist[$i] = $revdata;
		}
		$tpl->assign("revlist",$revlist);

		$taglist = git_read_refs($projectroot,$project,"refs/tags");
		if (isset($taglist) && (count($taglist) > 0)) {
			foreach ($taglist as $i => $tag) {
				if (isset($tag['comment'])) {
					$com = trim($tag['comment'][0]);
					if (strlen($com) > GITPHP_TRIM_LENGTH)
						$com = substr($trimmed,0,GITPHP_TRIM_LENGTH) . "...";
					$taglist[$i]['comment'] = $com;
				}
			}
			$tpl->assign("taglist",$taglist);
		}

		$headlist = git_read_refs($projectroot,$project,"refs/heads");
		if (isset($headlist) && (count($headlist) > 0)) {
			$tpl->assign("headlist",$headlist);
		}
	}
	$tpl->display('project.tpl', $cachekey);
}

?>
