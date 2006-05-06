<?php
/*
 *  gitphp.lib.php
 *  gitphp: A PHP git repository browser
 *  Component: Function library
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Library General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

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
				if ($dh = opendir($projectroot)) {
					while (($file = readdir($dh)) !== false) {
						if ((strpos($file,'.') !== 0) && is_dir($projectroot . $file) && is_file($projectroot . $file . "/HEAD"))
							$projects[] = $file;
					}
					closedir($dh);
				} else
					return "Could not read project directory";
			}
		} else
			return "Projectroot is not a directory";
	} else
		return "No projectroot set";
	return $projects;
}

function git_project_descr($projectroot,$project)
{
	return file_get_contents($projectroot . $project . "/description");
}

function git_project_owner($projectroot,$project)
{
	$data = posix_getpwuid(fileowner($projectroot . $project));
	if (isset($data['gecos']) && (strlen($data['gecos']) > 0))
		return $data['gecos'];
	return $data['name'];
}

function git_project_age($projectroot,$project)
{
}

function age_string($age)
{
}

function git_project_listentry($projectroot,$project,$class,$indent)
{
	global $tpl;
	$tpl->clear_all_assign();
	$tpl->assign("class",$class);
	$tpl->assign("project",$project);
	$tpl->assign("descr",git_project_descr($projectroot,$project));
	$tpl->assign("owner",git_project_owner($projectroot,$project));
	if ($indent)
		$tpl->assign("idt",TRUE);
	$age = git_project_age($projectroot,$project);
	if ($age < 60*60*24*2)
		$tpl->assign("age_colored",TRUE);
	if ($age < 60*60*2)
		$tpl->assign("age_bold",TRUE);
	$tpl->assign("age_string",age_string($age));
	$tpl->display("projlist_item.tpl");
}

function git_project_list($projectroot,$projectlist)
{
	global $tpl;
	$projects = git_read_projects($projectroot,$projectlist);
	if (is_array($projects)) {
		if (count($projects) > 0) {
			$tpl->clear_all_assign();
			$tpl->display("projlist_header.tpl");
			$alternate = false;
			foreach ($projects as $cat => $plist) {
				if (is_array($plist)) {
					if ($cat != "none") {
						$tpl->clear_all_assign();
						$tpl->assign("category",$cat);
						$tpl->display("projlist_category.tpl");
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
