<?php
/*
 *  gitutil.git_project_info.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - single project info
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once(GITPHP_INCLUDEDIR . 'gitutil.git_read_head.php');
 require_once(GITPHP_INCLUDEDIR . 'gitutil.git_read_commit.php');

function git_project_info($projectroot,$project)
{
	$projectObj = GitPHP_ProjectList::GetInstance()->GetProject($project);

	$projinfo = array();
	$projinfo["project"] = $project;
	$projinfo["descr"] = $projectObj->GetDescription(true);
	$projinfo["owner"] = $projectObj->GetOwner();
	$head = git_read_head();
	$commit = git_read_commit($head);
	$projinfo["age"] = $commit['age'];
	$projinfo["age_string"] = $commit['age_string'];
	return $projinfo;
}

?>
