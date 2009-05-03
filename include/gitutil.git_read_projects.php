<?php
/*
 *  gitutil.git_read_projects.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read projects
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_recurse_projects.php');

function git_read_projects($projectroot,$projectlist)
{
	$projects = array();
	if (isset($projectroot)) {
		if (is_dir($projectroot)) {
			if (isset($projectlist)) {
				foreach ($projectlist as $cat => $plist) {
					if (is_array($plist)) {
						$projs = array();
						foreach ($plist as $pname => $ppath) {
							if (is_dir($projectroot . $ppath) && is_file($projectroot . $ppath . "/HEAD"))
								$projs[] = $ppath;
						}
						if (count($projs) > 0) {
							sort($projs);
							$projects[$cat] = $projs;
						}
					}
				}
			} else {
				$projects = git_recurse_projects($projectroot);
				$len = count($projects);
				$cut = strlen($projectroot);
				for ($i = 0; $i < $len; $i++) {
					$projects[$i] = substr($projects[$i],$cut + 1);
				}
			}
		} else
			return "Projectroot is not a directory";
	} else
		return "No projectroot set";
	return $projects;
}

?>
