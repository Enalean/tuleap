<?php
/*
 *  display.git_project_index.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - project index
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_read_projects.php');

function git_project_index($projectroot, $projectlist)
{
	$projlist = git_read_projects($projectroot, $projectlist);
	header("Content-type: text/plain; charset=utf-8");
	header("Content-Disposition: inline; filename=\"index.aux\"");
	foreach ($projlist as $cat => $plist) {
		if (is_array($plist)) {
			foreach ($plist as $i => $proj)
				echo $proj . "\n";
		} else
			echo $plist . "\n";
	}
}

?>
