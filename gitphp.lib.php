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

function git_read_head($proj)
{
	global $gitphp_conf;
	return shell_exec("env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . "git-rev-parse --verify HEAD");
}

function git_read_revlist($proj,$head)
{
	global $gitphp_conf;
	$revlist = array();
	$revs = shell_exec("env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . "git-rev-list --max-count=17 " . $head);
	$tok = strtok($revs,"\n");
	while ($tok !== false) {
		$revlist[] = $tok;
		$tok = strtok("\n");
	}
	return $revlist;
}

function age_string($age)
{
	if ($age > 60*60*24*365*2)
		return (int)($age/60/60/24/365) . " years ago";
	else if ($age > 60*60*24*(365/12)*2)
		return (int)($age/60/60/24/(365/12)) . " months ago";
	else if ($age > 60*60*24*7*2)
		return (int)($age/60/60/24/7) . " weeks ago";
	else if ($age > 60*60*24*2)
		return (int)($age/60/60/24) . " days ago";
	else if ($age > 60*60*2)
		return (int)($age/60/60) . " hours ago";
	else if ($age > 60*2)
		return (int)($age/60) . " min ago";
	else if ($age > 2)
		return (int)$age . " sec ago";
	return "right now";
}

function git_read_commit($proj,$head)
{
	global $gitphp_conf;
	$revlist = shell_exec("env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . "git-rev-list --header --parents --max-count=1 " . $head);
	$lines = array();
	$tok = strtok($revlist,"\n");
	while ($tok !== false) {
		$lines[] = $tok;
		$tok = strtok("\n");
	}
	if (!($lines[0]) || !ereg("^[0-9a-fA-F]{40}",$lines[0]))
		return null;
	$commit = array();
	$tok = strtok($lines[0]," ");
	$commit['id'] = $tok;
	$tok = strtok(" ");
	$parents = array();
	while ($tok !== false) {
		$parents[] = $tok;
		$tok = strtok(" ");
	}
	$commit['parents'] = $parents;
	$commit['parent'] = $parents[0];
	foreach ($lines as $i => $line) {
		if (ereg("^tree ([0-9a-fA-F]{40})$",$line,$regs))
			$commit['tree'] = $regs[1];
		else if (ereg("^author (.*) ([0-9]+) (.*)$",$line,$regs)) {
			$commit['author'] = $regs[1];
			$commit['author_epoch'] = $regs[2];
			$commit['author_tz'] = $regs[3];
			if (ereg("^([^<]+) <",$commit['author'],$r))
				$commit['author_name'] = $r[1];
			else
				$commit['author_name'] = $commit['author'];
		} else if (ereg("^committer (.*) ([0-9]+) (.*)$",$line,$regs)) {
			$commit['committer'] = $regs[1];
			$commit['committer_epoch'] = $regs[2];
			$commit['committer_tz'] = $regs[3];
			$commit['committer_name'] = $commit['committer'];
			$commit['committer_name'] = ereg_replace(" <.*","",$commit['committer_name']);
		}
	}
	/*
	 * TODO: Store title and comment
	 */
	$age = time() - $commit['committer_epoch'];
	$commit['age'] = $age;
	$commit['age_string'] = age_string($age);
	if ($age > 60*60*24*7*2) {
		$commit['age_string_date'] = date("Y-m-d",$commit['committer_epoch']);
		$commit['age_string_age'] = $commit['age_string'];
	} else {
		$commit['age_string_date'] = $commit['age_string'];
		$commit['age_string_age'] = date("Y-m-d",$commit['committer_epoch']);
	}
	return $commit;
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
	$head = git_read_head($projectroot . $project);
	$commit = git_read_commit($projectroot . $project,$head);
	if ($commit['age'] < 60*60*24*2)
		$tpl->assign("age_colored",TRUE);
	if ($commit['age'] < 60*60*2)
		$tpl->assign("age_bold",TRUE);
	$tpl->assign("age_string",$commit['age_string']);
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

function read_info_ref($project, $type = "")
{
	$refs = array();
	$lines = file($project);
	foreach ($lines as $no => $line) {
		if (ereg("^([0-9a-fA-F]{40})\t.*" . $type . "/([^\^]+)",$line,$regs)) {
			if ($isset($refs[$regs[1]]))
				$refs[$regs[1]] .= " / " . $regs[2];
			else
				$refs[$regs[1]] = $regs[2];
		}
	}
	return $refs;
}

function date_str($epoch,$tz = "-0000")
{
	$date = array();
	$date['hour'] = date("H",$epoch);
	$date['minute'] = date("i",$epoch);
	$date['mday'] = date("d",$epoch);
	$date['day'] = date("D",$epoch);
	$date['month'] = date("M",$epoch);
	$date['rfc2822'] = date("r",$epoch);
	$date['mday-time'] = date("d M H:i",$epoch);
	if (ereg("^([+\-][0-9][0-9])([0-9][0-9])$",$tz,$regs)) {
		$local = $epoch + ((((int)$regs[1]) + ($regs[2]/60)) * 3600);
		$date['hour_local'] = date("H",$local);
		$date['minute_local'] = date("i",$local);
		$date['tz_local'] = $tz;
	}
	return $date;
}

function git_summary($projectroot,$project)
{
	global $tpl;
	$descr = git_project_descr($projectroot,$project);
	$head = git_read_head($projectroot . $project);
	$commit = git_read_commit($projectroot . $project, $head);
	$commitdate = date_str($commit['committer_epoch'],$commit['committer_tz']);
	$owner = git_project_owner($projectroot,$project);
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("head",$head);
	$tpl->display("project_nav.tpl");
	$tpl->clear_all_assign();
	$tpl->assign("description",$descr);
	$tpl->assign("owner",$owner);
	$tpl->assign("lastchange",$commitdate['rfc2822']);
	$tpl->display("project_brief.tpl");
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->display("project_revlist_header.tpl");
	$revlist = git_read_revlist($projectroot . $project, $head);
	$alternate = FALSE;
	foreach ($revlist as $i => $rev) {
		$tpl->clear_all_assign();
		$revco = git_read_commit($projectroot . $project, $rev);
		$authordate = date_str($revco['author_epoch']);
		if ($alternate)
			$tpl->assign("class","dark");
		else
			$tpl->assign("class","light");
		$alternate = !$alternate;
		if ($i < 16) {
			$tpl->assign("commit",$rev);
			if (isset($refs[$rev]))
				$tpl->assign("commitref",$refs[$rev]);
			$tpl->assign("commitage",$revco['age_string']);
			$tpl->assign("commitauthor",$revco['author_name']);
			if (strlen($revco['title_short']) < strlen($revco['title'])) {
				$tpl->assign("title",$revco['title']);
				$tpl->assign("title_short",$revco['title_short']);
			} else
				$tpl->assign("title_short",$revco['title']);
		} else {
			$tpl->assign("project",$project);
			$tpl->assign("truncate",TRUE);
		}
		$tpl->display("project_revlist_item.tpl");
	}
	$tpl->clear_all_assign();
	$tpl->display("project_revlist_footer.tpl");
}

?>
