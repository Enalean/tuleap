<?php
/*
 *  gitutil.git_project_info.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - single project info
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_project_descr.php');
 require_once('gitutil.git_project_owner.php');
 require_once('gitutil.git_read_head.php');
 require_once('gitutil.git_read_commit.php');

function git_project_info($projectroot,$project)
{
	$projinfo = array();
	$projinfo["project"] = $project;
	$projinfo["descr"] = git_project_descr($projectroot,$project,TRUE);
	$projinfo["owner"] = git_project_owner($projectroot,$project);
	$head = git_read_head($projectroot . $project);
	$commit = git_read_commit($projectroot . $project,$head);
	$projinfo["age"] = $commit['age'];
	$projinfo["age_string"] = $commit['age_string'];
	return $projinfo;
}

?>
