<?php
/*
 *  gitutil.git_project_owner.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - project owner
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function git_project_owner($projectroot,$project)
{
	$data = posix_getpwuid(fileowner($projectroot . $project));
	if (isset($data['gecos']) && (strlen($data['gecos']) > 0))
		return $data['gecos'];
	return $data['name'];
}

?>
