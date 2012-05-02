<?php
/*
 *  gitutil.git_recurse_projects.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - recursively read projects
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function git_recurse_projects($dir)
{
	$projects = array();
	if ($dh = opendir($dir)) {
		while (($file = readdir($dh)) !== false) {
			if ((strpos($file,'.') !== 0) && is_dir($dir . "/" . $file)) {
				if (is_file($dir . "/" . $file . "/HEAD")) {
					$projects[] = $dir . "/" . $file;
				} else {
					$p2 = git_recurse_projects($dir . "/" . $file);
					$projects = array_merge($projects, $p2);
				}
			}
		}
		closedir($dh);
	}
	return $projects;
}

?>
