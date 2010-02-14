<?php
/*
 *  gitutil.git_project_info.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - single project info
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once(GITPHP_INCLUDEDIR . 'defs.constants.php');
 require_once(GITPHP_INCLUDEDIR . 'util.age_string.php');

function git_project_info($project)
{
	$projectObj = GitPHP_ProjectList::GetInstance()->GetProject($project);

	$projinfo = array();
	$projinfo['project'] = $project;
	$projinfo['descr'] = $projectObj->GetDescription(GITPHP_TRIM_LENGTH);
	$projinfo['owner'] = $projectObj->GetOwner();
	$commit = $projectObj->GetHeadCommit();
	if ($commit) {
		$projinfo['age'] = $commit->GetAge();
		$projinfo['age_string'] = age_string($projinfo['age']);
	}
	return $projinfo;
}

?>
