<?php
/*
 *  display.git_project_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - project list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.projectcmp.php');
 require_once('util.descrcmp.php');
 require_once('util.ownercmp.php');
 require_once('util.agecmp.php');
 require_once('gitutil.git_read_projects.php');

function git_project_list($projectroot,$projectlist,$order = "project")
{
	global $tpl,$git_projects;

	$cachekey = sha1(serialize($projectlist)) . "|" . sha1($order);

	if (!$tpl->is_cached('projectlist.tpl', $cachekey)) {
		$projects = git_read_projects($projectroot,$projectlist, TRUE);
		if (is_array($projects)) {
			if (count($projects) > 0) {
				if ($order)
					$tpl->assign("order",$order);
				if (!isset($git_projects)) {
					switch ($order) {
						case "project":
							usort($projects,"projectcmp");
							break;
						case "descr":
							usort($projects,"descrcmp");
							break;
						case "owner":
							usort($projects,"ownercmp");
							break;
						case "age":
							usort($projects,"agecmp");
							break;
					}
					$tpl->assign("projects",$projects);
				} else {
					foreach ($projects as $cat => $plist) {
						switch ($order) {
							case "project":
								usort($projects[$cat],"projectcmp");
								break;
							case "descr":
								usort($projects[$cat],"descrcmp");
								break;
							case "owner":
								usort($projects[$cat],"ownercmp");
								break;
							case "age":
								usort($projects[$cat],"agecmp");
								break;
						}
					}
					$tpl->assign("categorizedprojects",$projects);
				}
			} else {
				$tpl->assign("message","No projects found");
				$tpl->assign("error",TRUE);
			}
		} else {
			$tpl->assign("message",$projects);
			$tpl->assign("error",TRUE);
		}
	}
	$tpl->display('projectlist.tpl', $cachekey);
}

?>
