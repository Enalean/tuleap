<?php
/*
 *  gitutil.git_read_projects.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read projects
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_recurse_projects.php');
 require_once('gitutil.git_project_info.php');

function git_read_projects($projectroot,$projectlist,$projdata = FALSE)
{
echo '<h1>'.$projectroot.'</h1>';
	$projects = array();
	if (isset($projectroot)) {
		if (is_dir($projectroot)) {
			if (isset($projectlist)) {
				foreach ($projectlist as $cat => $plist) {
					if (is_array($plist)) {
						$projs = array();
						foreach ($plist as $pname => $ppath) {
							if (is_dir($projectroot . $ppath) && is_file($projectroot . $ppath . "/HEAD")) {
								if ($projdata)
									$projs[] = git_project_info($projectroot, $ppath);
								else
									$projs[] = $ppath;
							}
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
				for ($i = 0; $i < $len; ++$i) {
					$p = substr($projects[$i],$cut + 1);
					if ($projdata)
						$projects[$i] = git_project_info($projectroot, $p);
					else
						$projects[$i] = $p;
				}
			}
		} else
			return "Projectroot is not a directory";
	} else
		return "No projectroot set";
	return $projects;
}

?>
