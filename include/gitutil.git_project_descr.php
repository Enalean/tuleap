<?php
/*
 *  gitutil.git_project_descr.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - project description
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function git_project_descr($projectroot,$project,$trim = FALSE)
{
	$desc = file_get_contents($projectroot . $project . "/description");
	if ($trim && (strlen($desc) > 50))
		$desc = substr($desc,0,50) . " ...";
	return $desc;
}

?>
