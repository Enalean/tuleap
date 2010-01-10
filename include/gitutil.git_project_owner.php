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
	global $git_projects;

	if (is_file($git_projects)) {
		if (!($fp = fopen($git_projects, 'r')))
			return "Failed to open projects.list";

		while (!feof($fp) && ($line = fgets($fp)))
		{
			$pinfo = explode(' ', $line);
			$ppath = $pinfo[0];
			if ($ppath == $project)
			{
				fclose($fp);
				return urldecode($pinfo[1]);
			}
		}

		fclose($fp);
	} elseif (function_exists('posix_getpwuid')) {
		$data = posix_getpwuid(fileowner($projectroot . $project));
		if (isset($data['gecos']) && (strlen($data['gecos']) > 0))
			return $data['gecos'];
		return $data['name'];
	} else
		return "";
}

?>
