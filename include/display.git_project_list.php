<?php
/*
 *  display.git_project_list.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - project list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('util.projectcmp.php');
 include_once('util.descrcmp.php');
 include_once('util.ownercmp.php');
 include_once('util.agecmp.php');
 include_once('display.git_project_listentry.php');
 include_once('gitutil.git_read_projects.php');

function git_project_list($projectroot,$projectlist,$order)
{
	global $tpl,$git_projects;
	$projects = git_read_projects($projectroot,$projectlist);
	if (is_array($projects)) {
		if (count($projects) > 0) {
			$tpl->clear_all_assign();
			$tpl->assign("order",$order);
			$tpl->display("projlist_header.tpl");
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
			}
			$alternate = false;
			foreach ($projects as $cat => $plist) {
				if (is_array($plist)) {
					if ($cat != "none") {
						$tpl->clear_all_assign();
						$tpl->assign("category",$cat);
						$tpl->display("projlist_category.tpl");
					}
					if (isset($git_projects)) {
						switch ($order) {
							case "project":
								usort($plist,"projectcmp");
								break;
							case "descr":
								usort($plist,"descrcmp");
								break;
							case "owner":
								usort($plist,"ownercmp");
								break;
							case "age":
								usort($plist,"agecmp");
								break;
						}
					}
					foreach ($plist as $i => $proj) {
						git_project_listentry($projectroot,$proj,($alternate?"dark":"light"),($cat=="none"?FALSE:TRUE));
						$alternate = !$alternate;
					}
				} else {
					git_project_listentry($projectroot,$plist,($alternate?"dark":"light"));
					$alternate = !$alternate;
				}
			}
			$tpl->clear_all_assign();
			$tpl->display("projlist_footer.tpl");
		} else
			echo "No projects found";
	} else
		echo $projects;
}

?>
