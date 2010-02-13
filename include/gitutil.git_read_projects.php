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

function git_read_projects($projdata = FALSE)
{
	$projectroot = GitPHP_Config::GetInstance()->GetValue('projectroot');
	$projectlist = GitPHP_ProjectList::GetInstance()->GetConfig();

	if (!is_dir($projectroot))
		return "Projectroot is not a directory";

	$projects = array();
	if (is_string($projectlist) && is_file($projectlist)) {
		if (!($fp = fopen($projectlist, 'r')))
			return "Failed to open project list file: " . $projectlist;

		while (!feof($fp) && ($line = fgets($fp)))
		{
			$pinfo = explode(' ', $line);
			$ppath = trim($pinfo[0]);
			if (is_file($projectroot . $ppath . "/HEAD"))
			{
				if ($projdata)
					$ppath = git_project_info($projectroot, $ppath);
				$projects[] = $ppath;
			}
		}

		fclose($fp);
	} elseif (is_array($projectlist)) {
		foreach ($projectlist as $cat => $plist) {
			if (is_array($plist)) {
				$projs = array();
				foreach ($plist as $pname => $ppath) {
					if (is_file($projectroot . $ppath . "/HEAD")) {
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
	return $projects;
}

?>
