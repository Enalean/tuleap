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

function prep_tmpdir($dir)
{
	if (file_exists($dir)) {
		if (is_dir($dir)) {
			if (!is_writeable($dir))
				return "Specified tmpdir is not writeable!";
		} else
			return "Specified tmpdir is not a directory";
	} else
		if (!mkdir($dir,0700))
			return "Could not create tmpdir";
	return TRUE;
}

function git_snapshot($projectroot,$project,$hash)
{
	global $gitphp_conf;
	if (!isset($hash))
		$hash = "HEAD";
	$rname = str_replace(array("/",".git"),array("-",""),$project);
	$cmd = "env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-tar-tree " . $hash . " " . $rname;
	header("Content-Type: application/x-tar");
	header("Content-Disposition: attachment; filename=" . $rname . ".tar");
	echo shell_exec($cmd);
}

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

function git_project_descr($projectroot,$project,$trim = FALSE)
{
	$desc = file_get_contents($projectroot . $project . "/description");
	if ($trim && (strlen($desc) > 50))
		$desc = substr($desc,0,50) . " ...";
	return $desc;
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

function git_read_revlist($proj,$head,$count)
{
	global $gitphp_conf;
	$revs = shell_exec("env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . "git-rev-list --max-count=" . $count . " " . $head);
	$revlist = explode("\n",$revs);
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
	$lines = explode("\n",$revlist);
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
	$comment = array();
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
		} else {
			$trimmed = trim($line);
			if ((strlen($trimmed) > 0) && !ereg("^[0-9a-fA-F]{40}",$trimmed) && !ereg("^parent [0-9a-fA-F]{40}",$trimmed)) {
				if (!isset($commit['title'])) {
					$commit['title'] = $trimmed;
					if (strlen($trimmed) > 50)
						$commit['title_short'] = substr($trimmed,0,50) . " ...";
					else
						$commit['title_short'] = $trimmed;
				}
				$comment[] = $trimmed;
			}
		}
	}
	$commit['comment'] = $comment;
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
	$tpl->assign("descr",git_project_descr($projectroot,$project,TRUE));
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

function mode_str($octmode)
{
	$mode = octdec($octmode);
	if (($mode & 0x4000) == 0x4000)
		return "drwxr-xr-x";
	else if (($mode & 0xA000) == 0xA000)
		return "lrwxrwxrwx";
	else if (($mode & 0x8000) == 0x8000) {
		if (($mode & 0x0040) == 0x0040)
			return "-rwxr-xr-x";
		else
			return "-rw-r--r--";
	}
	return "----------";
}

function file_type($octmode)
{
	$mode = octdec($octmode);
	if (($mode & 0x4000) == 0x4000)
		return "directory";
	else if (($mode & 0xA000) == 0xA000)
		return "symlink";
	else if (($mode & 0x8000) == 0x8000)
		return "file";
	return "unknown";
}

function git_get_hash_by_path($project,$base,$path,$type = null)
{
	global $gitphp_conf;
	$tree = $base;
	$parts = explode("/",$path);
	$partcount = count($parts);
	foreach ($parts as $i => $part) {
		$lsout = shell_exec("env GIT_DIR=" . $project . " " . $gitphp_conf['gitbin'] . "git-ls-tree " . $tree);
		$entries = explode("\n",$lsout);
		foreach ($entries as $j => $line) {
			if (ereg("^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$",$line,$regs)) {
				if ($regs[4] == $part) {
					if ($i == ($partcount)-1)
						return $regs[3];
					if ($regs[2] == "tree")
						$tree = $regs[3];
					break;
				}
			}
		}
	}
}

function git_tree($projectroot,$project,$hash,$file,$hashbase)
{
	global $gitphp_conf,$tpl;
	if (!isset($hash)) {
		$hash = git_read_head($projectroot . $project);
		if (isset($file))
			$hash = git_get_hash_by_path($projectroot . $project, ($hashbase?$hashbase:$hash),$file,"tree");
			if (!isset($hashbase))
				$hashbase = $hash;
	}
	$lsout = shell_exec("env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-ls-tree -z " . $hash);
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	if (isset($hashbase) && ($co = git_read_commit($projectroot . $project, $hashbase))) {
		$basekey = $hashbase;
		$tpl->assign("hashbase",$hashbase);
		$tpl->assign("project",$project);
		$tpl->assign("title",$co['title']);
		if (isset($refs[$hashbase]))
			$tpl->assign("hashbaseref",$refs[$hashbase]);
		$tpl->display("tree_nav.tpl");
	} else {
		$tpl->assign("hash",$hash);
		$tpl->display("tree_emptynav.tpl");
	}
	$tpl->clear_all_assign();
	if (isset($file))
		$tpl->assign("filename",$file);
	$tpl->display("tree_filelist_header.tpl");

	$tok = strtok($lsout,"\0");
	$alternate = FALSE;
	while ($tok !== false) {
		if (ereg("^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$",$tok,$regs)) {
			$tpl->clear_all_assign();
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			$tpl->assign("filemode",mode_str($regs[1]));
			$tpl->assign("type",$regs[2]);
			$tpl->assign("hash",$regs[3]);
			$tpl->assign("name",$regs[4]);
			$tpl->assign("project",$project);
			if (isset($file))
				$tpl->assign("base",$file . "/");
			if (isset($basekey))
				$tpl->assign("hashbase",$basekey);
			$tpl->display("tree_filelist_item.tpl");
		}
		$tok = strtok("\0");
	}

	$tpl->clear_all_assign();
	$tpl->display("tree_filelist_footer.tpl");
}

function projectcmp($a,$b)
{
	return strcmp($a,$b);
}

function descrcmp($a,$b)
{
	global $gitphp_conf;
	return strcmp(git_project_descr($gitphp_conf['projectroot'],$b),git_project_descr($gitphp_conf['projectroot'],$b));
}

function ownercmp($a,$b)
{
	global $gitphp_conf;
	return strcmp(git_project_owner($gitphp_conf['projectroot'],$a),git_project_owner($gitphp_conf['projectroot'],$b));
}

function agecmp($a,$b)
{
	global $gitphp_conf;
	$ca = git_read_commit($gitphp_conf['projectroot'] . $a, git_read_head($gitphp_conf['projectroot'] . $a));
	$cb = git_read_commit($gitphp_conf['projectroot'] . $b, git_read_head($gitphp_conf['projectroot'] . $b));
	if ($ca['commit']['age'] == $cb['commit']['age'])
		return 0;
	return ($ca['commit']['age'] < $cb['commit']['age'] ? 1 : -1);
}

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

function git_read_tag($project, $tag_id)
{
	global $gitphp_conf;
	$tag = array();
	$tagout = shell_exec("env GIT_DIR=" . $project . " " . $gitphp_conf['gitbin'] . "git-cat-file tag " . $tag_id);
	$tag['id'] = $tag_id;
	$comment = array();
	$tok = strtok($tagout,"\n");
	while ($tok !== false) {
		if (ereg("^object ([0-9a-fA-F]{40})$",$tok,$regs))
			$tag['object'] = $regs[1];
		else if (ereg("^type (.+)$",$tok,$regs))
			$tag['type'] = $regs[1];
		else if (ereg("^tag (.+)$",$tok,$regs))
			$tag['name'] = $regs[1];
		else if (ereg("^tagger (.*) ([0-9]+) (.*)$",$tok,$regs)) {
			$tag['author'] = $regs[1];
			$tag['epoch'] = $regs[2];
			$tag['tz'] = $regs[3];
		} else if (ereg("--BEGIN",$tok)) {
			while ($tok !== false) {
				$comment[] = $tok;
				$tok = strtok("\n");
			}
			break;
		}
		$tok = strtok("\n");
	}
	$tag['comment'] = $comment;
	if (!isset($tag['name']))
		return null;
	return $tag;
}

function git_get_type($project, $hash)
{
	global $gitphp_conf;
	return trim(shell_exec("env GIT_DIR=" . $project . " " . $gitphp_conf['gitbin'] . "git-cat-file -t " . $hash));
}

function git_read_hash($path)
{
	return file_get_contents($path);
}

function epochcmp($a,$b)
{
	if ($a['epoch'] == $b['epoch'])
		return 0;
	return ($a['epoch'] < $b['epoch']) ? 1 : -1;
}

function git_read_refs($projectroot,$project,$refdir)
{
	if (!is_dir($projectroot . $project . "/" . $refdir))
		return null;
	$refs = array();
	if ($dh = opendir($projectroot . $project . "/" . $refdir)) {
		while (($dir = readdir($dh)) !== false) {
			if (strpos($dir,'.') !== 0) {
				if (is_dir($projectroot . $project . "/" . $refdir . "/" . $dir)) {
					if ($dh2 = opendir($projectroot . $project . "/" . $refdir . "/" . $dir)) {
						while (($dir2 = readdir($dh2)) !== false) {
							if (strpos($dir2,'.') !== 0)
								$refs[] = $dir . "/" . $dir2;
						}
						closedir($dh2);
					}
				}
				$refs[] = $dir;
			}
		}
		closedir($dh);
	} else
		return null;
	$reflist = array();
	foreach ($refs as $i => $ref_file) {
		$ref_id = git_read_hash($projectroot . $project . "/" . $refdir . "/" . $ref_file);
		$type = git_get_type($projectroot . $project, $ref_id);
		if ($type) {
			$ref_item = array();
			$ref_item['type'] = $type;
			$ref_item['id'] = $ref_id;
			$ref_item['epoch'] = 0;
			$ref_item['age'] = "unknown";

			if ($type == "tag") {
				$tag = git_read_tag($projectroot . $project, $ref_id);
				$ref_item['comment'] = $tag['comment'];
				if ($tag['type'] == "commit") {
					$co = git_read_commit($projectroot . $project, $tag['object']);
					$ref_item['epoch'] = $co['committer_epoch'];
					$ref_item['age'] = $co['age_string'];
				} else if (isset($tag['epoch'])) {
					$age = time() - $tag['epoch'];
					$ref_item['epoch'] = $tag['epoch'];
					$ref_item['age'] = age_string($age);
				}
				$ref_item['reftype'] = $tag['type'];
				$ref_item['name'] = $tag['name'];
				$ref_item['refid'] = $tag['object'];
			} else if ($type == "commit") {
				$co = git_read_commit($projectroot . $project, $ref_id);
				$ref_item['reftype'] = "commit";
				$ref_item['name'] = $ref_file;
				$ref_item['title'] = $co['title'];
				$ref_item['refid'] = $ref_id;
				$ref_item['epoch'] = $co['committer_epoch'];
				$ref_item['age'] = $co['age_string'];
			}
			$reflist[] = $ref_item;
		}
	}
	usort($reflist,"epochcmp");
	return $reflist;
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
	$revlist = git_read_revlist($projectroot . $project, $head, 17);
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
		$tpl->assign("project",$project);
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
			$tpl->assign("truncate",TRUE);
		}
		$tpl->display("project_revlist_item.tpl");
	}
	$tpl->clear_all_assign();
	$tpl->display("project_revlist_footer.tpl");

	$taglist = git_read_refs($projectroot,$project,"refs/tags");
	if (isset($taglist) && (count($taglist) > 0)) {
		$tpl->clear_all_assign();
		$tpl->assign("project",$project);
		$tpl->display("project_taglist_header.tpl");
		$alternate = FALSE;
		foreach ($taglist as $i => $tag) {
			$tpl->clear_all_assign();
			$tpl->assign("project",$project);
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			if ($i < 16) {
				$tpl->assign("tagage",$tag['age']);
				$tpl->assign("tagname",$tag['name']);
				$tpl->assign("tagid",$tag['id']);
				$tpl->assign("tagtype",$tag['type']);
				$tpl->assign("refid",$tag['refid']);
				$tpl->assign("reftype",$tag['reftype']);
				if (isset($tag['comment']))
					$tpl->assign("comment",$tag['comment']);
			} else
				$tpl->assign("truncate",TRUE);
			$tpl->display("project_taglist_item.tpl");
		}
		$tpl->clear_all_assign();
		$tpl->display("project_taglist_footer.tpl");
	}

	$headlist = git_read_refs($projectroot,$project,"refs/heads");
	if (isset($headlist) && (count($headlist) > 0)) {
		$tpl->clear_all_assign();
		$tpl->assign("project",$project);
		$tpl->display("project_headlist_header.tpl");
		$alternate = FALSE;
		foreach ($headlist as $i => $head) {
			$tpl->clear_all_assign();
			$tpl->assign("project",$project);
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			if ($i < 16) {
				$tpl->assign("headage",$head['age']);
				$tpl->assign("headname",$head['name']);
			} else
				$tpl->assign("truncate",TRUE);
			$tpl->display("project_headlist_item.tpl");
		}

		$tpl->clear_all_assign();
		$tpl->display("project_headlist_footer.tpl");
	}
}

function git_shortlog($projectroot,$project,$hash,$page)
{
	global $tpl,$gitphp_conf;
	$head = git_read_head($projectroot . $project);
	if (!isset($hash))
		$hash = $head;
	if (!isset($page))
		$page = 0;
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->display("shortlog_nav.tpl");

	$revlist = git_read_revlist($projectroot . $project, $hash, (100 * ($page+1)));

	if (($hash != $head) || $page)
		$tpl->assign("headlink",TRUE);
	if ($page > 0) {
		$tpl->assign("prevlink",TRUE);
		$tpl->assign("prevpage",$page-1);
	}
	if (count($revlist) >= (100 * ($page+1)-1)) {
		$tpl->assign("nextlink",TRUE);
		$tpl->assign("nextpage",$page+1);
	}
	$tpl->display("shortlog_pagenav.tpl");

	$alternate = FALSE;
	for ($i = ($page * 100); $i <= count($revlist); $i++) {
		$tpl->clear_all_assign();
		$commit = $revlist[$i];
		if (strlen(trim($commit)) > 0) {
			if (isset($refs[$commit]))
				$tpl->assign("commitref",$refs[$commit]);
			$co = git_read_commit($projectroot . $project, $commit);
			$ad = date_str($co['author_epoch']);
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			$tpl->assign("project",$project);
			$tpl->assign("commit",$commit);
			$tpl->assign("agestringage",$co['age_string_age']);
			$tpl->assign("agestringdate",$co['age_string_date']);
			$tpl->assign("authorname",$co['author_name']);
			$tpl->assign("title_short",$co['title_short']);
			if (strlen($co['title_short']) < strlen($co['title']))
				$tpl->assign("title",$co['title']);
			$tpl->display("shortlog_item.tpl");
		}
	}

	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	if (count($revlist) >= (100 * ($page+1)-1)) {
		$tpl->assign("nextlink",TRUE);
		$tpl->assign("nextpage",$page+1);
	}
	$tpl->display("shortlog_footer.tpl");
}

function git_log($projectroot,$project,$hash,$page)
{
	global $tpl;
	$head = git_read_head($projectroot . $project);
	if (!isset($hash))
		$hash = $head;
	if (!isset($page))
		$page = 0;
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->display("log_nav.tpl");

	$revlist = git_read_revlist($projectroot . $project, $hash, (100 * ($page+1)));

	if (($hash != $head) || $page)
		$tpl->assign("headlink",TRUE);
	if ($page > 0) {
		$tpl->assign("prevlink",TRUE);
		$tpl->assign("prevpage",$page-1);
	}
	if (count($revlist) >= (100 * ($page+1)-1)) {
		$tpl->assign("nextlink",TRUE);
		$tpl->assign("nextpage",$page+1);
	}
	$tpl->display("log_pagenav.tpl");

	if (!$revlist) {
		$tpl->clear_all_assign();
		$tpl->assign("project",$project);
		$co = git_read_commit($hash);
		$tpl->assign("age_string",$co['age_string']);
		$tpl->display("log_info.tpl");
	}
	for ($i = ($page * 100); $i <= count($revlist); $i++) {
		$tpl->clear_all_assign();
		$commit = $revlist[$i];
		$co = git_read_commit($projectroot . $project, $commit);
		$ad = date_str($co['author_epoch']);
		$tpl->assign("project",$project);
		$tpl->assign("commit",$commit);
		if (isset($refs[$commit]))
			$tpl->assign("commitref",$refs[$commit]);
		$tpl->assign("agestring",$co['age_string']);
		$tpl->assign("title",$co['title']);
		$tpl->assign("authorname",$co['author_name']);
		$tpl->assign("rfc2822",$ad['rfc2822']);
		$tpl->assign("comment",$co['comment']);
		if (count($co['comment']) > 0)
			$tpl->assign("notempty",TRUE);
		$tpl->display("log_item.tpl");
	}
}

function git_commit($projectroot,$project,$hash)
{
	global $gitphp_conf,$tpl;
	$co = git_read_commit($projectroot . $project, $hash);
	$ad = date_str($co['author_epoch'],$co['author_tz']);
	$cd = date_str($co['committer_epoch'],$co['committer_tz']);
	if (isset($co['parent'])) {
		$root = "";
		$parent = $co['parent'];
	} else {
		$root = "--root";
		$parent = "";
	}
	$diffout = shell_exec("env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-diff-tree -r -M " . $root . " " . $parent . " " . $hash);
	$difftree = explode("\n",$diffout);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->assign("tree",$co['tree']);
	if (isset($co['parent']))
		$tpl->assign("parent",$co['parent']);
	$tpl->display("commit_nav.tpl");
	$tpl->assign("title",$co['title']);
	if (isset($refs[$co['id']]))
		$tpl->assign("commitref",$refs[$co['id']]);
	$tpl->assign("author",$co['author']);
	$tpl->assign("adrfc2822",$ad['rfc2822']);
	$tpl->assign("adhourlocal",$ad['hour_local']);
	$tpl->assign("adminutelocal",$ad['minute_local']);
	$tpl->assign("adtzlocal",$ad['tz_local']);
	$tpl->assign("committer",$co['committer']);
	$tpl->assign("cdrfc2822",$cd['rfc2822']);
	$tpl->assign("cdhourlocal",$cd['hour_local']);
	$tpl->assign("cdminutelocal",$cd['minute_local']);
	$tpl->assign("cdtzlocal",$cd['tz_local']);
	$tpl->assign("id",$co['id']);
	$tpl->assign("parents",$co['parents']);
	$tpl->assign("comment",$co['comment']);
	$tpl->assign("difftreesize",count($difftree)+1);
	$tpl->display("commit_data.tpl");
	$alternate = FALSE;
	foreach ($difftree as $i => $line) {
		$tpl->clear_all_assign();
		if (ereg("^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)([0-9]{0,3})\t(.*)$",$line,$regs)) {
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			$tpl->assign("project",$project);
			$tpl->assign("hash",$hash);
			$tpl->assign("from_mode",$regs[1]);
			$tpl->assign("to_mode",$regs[2]);
			$tpl->assign("from_mode_cut",substr($regs[1],-4));
			$tpl->assign("to_mode_cut",substr($regs[2],-4));
			$tpl->assign("from_id",$regs[3]);
			$tpl->assign("to_id",$regs[4]);
			$tpl->assign("status",$regs[5]);
			$tpl->assign("similarity",$regs[6]);
			$tpl->assign("file",$regs[7]);
			$tpl->assign("from_file",strtok($regs[7],"\t"));
			$tpl->assign("to_file",strtok("\t"));
			$tpl->assign("to_filetype",file_type($regs[2]));
			if ((octdec($regs[2]) & 0x8000) == 0x8000)
				$tpl->assign("isreg",TRUE);
			$modestr = "";
			if ((octdec($regs[1]) & 0x17000) != (octdec($regs[2]) & 0x17000))
				$modestr .= " from " . file_type($regs[1]) . " to " . file_type($regs[2]);
			if ((octdec($regs[1]) & 0777) != (octdec($regs[2]) & 0777)) {
				if ((octdec($regs[1]) & 0x8000) && (octdec($regs[2]) & 0x8000))
					$modestr .= " mode: " . (octdec($regs[1]) & 0777) . "->" . (octdec($regs[2]) & 0777);
				else if (octdec($regs[2]) & 0x8000)
					$modestr .= " mode: " . (octdec($regs[2]) & 0777);
			}
			$tpl->assign("modechange",$modestr);
			$simmodechg = "";
			if ($regs[1] != $regs[2])
				$simmodechg .= ", mode: " . (octdec($regs[2]) & 0777);
			$tpl->assign("simmodechg",$simmodechg);

			$tpl->display("commit_item.tpl");
		}
	}
	$tpl->clear_all_assign();
	$tpl->display("commit_footer.tpl");
}

function git_diff_print($proj,$from,$from_name,$to,$to_name,$format = "html")
{
	global $gitphp_conf,$tpl;
	$from_tmp = "/dev/null";
	$to_tmp = "/dev/null";
	$pid = posix_getpid();
	if (isset($from)) {
		$from_tmp = $gitphp_conf['gittmp'] . "gitphp_" . $pid . "_from";
		shell_exec("env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . "git-cat-file blob " . $from . " > " . $from_tmp);
	}
	if (isset($to)) {
		$to_tmp = $gitphp_conf['gittmp'] . "gitphp_" . $pid . "_to";
		shell_exec("env GIT_DIR=" . $proj . " " . $gitphp_conf['gitbin'] . "git-cat-file blob " . $to . " > " . $to_tmp);
	}
	$diffout = shell_exec("diff -u -p -L '" . $from_name . "' -L '" . $to_name . "' " . $from_tmp . " " . $to_tmp);
	if ($format == "plain")
		echo $diffout;
	else {
		$line = strtok($diffout,"\n");
		while ($line !== false) {
			$start = substr($line,0,1);
			unset($color);
			if ($start == "+")
				$color = "#008800";
			else if ($start == "-")
				$color = "#cc0000";
			else if ($start == "@")
				$color = "#990099";
			if ($start != "\\") {
			/*
				while (($pos = strpos($line,"\t")) !== false) {
					if ($c = (8 - (($pos - 1) % 8))) {
						$spaces = "";
						for ($i = 0; $i < $c; $i++)
							$spaces .= " ";
						preg_replace('/\t/',$spaces,$line,1);
					}
				}
			 */
				$tpl->clear_all_assign();
				$tpl->assign("line",htmlentities($line));
				if (isset($color))
					$tpl->assign("color",$color);
				$tpl->display("diff_line.tpl");
			}
			$line = strtok("\n");
		}
	}
	if (isset($from))
		unlink($from_tmp);
	if (isset($to))
		unlink($to_tmp);
}

function git_commitdiff_plain($projectroot,$project,$hash,$hash_parent)
{
	global $gitphp_conf,$tpl;
	$ret = prep_tmpdir($gitphp_conf['gittmp']);
	if ($ret !== TRUE) {
		echo $ret;
		return;
	}
	$co = git_read_commit($projectroot . $project, $hash);
	if (!isset($hash_parent))
		$hash_parent = $co['parent'];
	$diffout = shell_exec("env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-diff-tree -r " . $hash_parent . " " . $hash);
	$difftree = explode("\n",$diffout);
	$refs = read_info_ref($projectroot . $project,"tags");
	$listout = shell_exec("env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-rev-list HEAD");
	$tok = strtok($listout,"\n");
	while ($tok !== false) {
		if (isset($refs[$tok]))
			$tagname = $refs[$tok];
		if ($tok == $hash)
			break;
		$tok = strtok("\n");
	}
	header("Content-type: text/plain; charset=UTF-8");
	header("Content-disposition: inline; filename=\"git-" . $hash . ".patch\"");
	$ad = date_str($co['author_epoch'],$co['author_tz']);
	$tpl->clear_all_assign();
	$tpl->assign("from",$co['author']);
	$tpl->assign("date",$ad['rfc2822']);
	$tpl->assign("subject",$co['title']);
	if (isset($tagname))
		$tpl->assign("tagname",$tagname);
	$tpl->assign("url",$gitphp_conf['self'] . "?p=" . $project . "&a=commitdiff&h=" . $hash);
	$tpl->assign("comment",$co['comment']);
	$tpl->display("diff_plaintext.tpl");
	echo "\n\n";
	foreach ($difftree as $i => $line) {
		if (ereg("^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$",$line,$regs)) {
			if ($regs[5] == "A")
				git_diff_print($projectroot . $project, null, "/dev/null", $regs[4], "b/" . $regs[6], "plain");
			else if ($regs[5] == "D")
				git_diff_print($projectroot . $project, $regs[3], "a/" . $regs[6], null, "/dev/null", "plain");
			else if ($regs[5] == "M")
				git_diff_print($projectroot . $project, $regs[3], "a/" . $regs[6], $regs[4], "b/" . $regs[6], "plain");
		}
	}
}

function git_commitdiff($projectroot,$project,$hash,$hash_parent)
{
	global $gitphp_conf,$tpl;
	$ret = prep_tmpdir($gitphp_conf['gittmp']);
	if ($ret !== TRUE) {
		echo $ret;
		return;
	}
	$co = git_read_commit($projectroot . $project, $hash);
	if (!isset($hash_parent))
		$hash_parent = $co['parent'];
	$diffout = shell_exec("env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-diff-tree -r " . $hash_parent . " " . $hash);
	$difftree = explode("\n",$diffout);
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->assign("tree",$co['tree']);
	$tpl->assign("hashparent",$hash_parent);
	$tpl->display("commitdiff_nav.tpl");
	$tpl->assign("title",$co['title']);
	if (isset($refs[$co['id']]))
		$tpl->assign("commitref",$refs[$co['id']]);
	$tpl->assign("comment",$co['comment']);
	$tpl->display("commitdiff_header.tpl");

	foreach ($difftree as $i => $line) {
		if (ereg("^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$",$line,$regs)) {
			$tpl->clear_all_assign();
			$tpl->assign("from_mode",$regs[1]);
			$tpl->assign("to_mode",$regs[2]);
			$tpl->assign("from_id",$regs[3]);
			$tpl->assign("to_id",$regs[4]);
			$tpl->assign("status",$regs[5]);
			$tpl->assign("file",$regs[6]);
			$tpl->assign("from_type",file_type($regs[1]));
			$tpl->assign("to_type",file_type($regs[2]));
			$tpl->display("commitdiff_item.tpl");
			if ($regs[5] == "A")
				git_diff_print($projectroot . $project, null,"/dev/null",$regs[4],"b/" . $regs[6]);
			else if ($regs[5] == "D")
				git_diff_print($projectroot . $project, $regs[3],"a/" . $regs[6],null,"/dev/null");
			else if (($regs[5] == "M") && ($regs[3] != $regs[4]))
				git_diff_print($projectroot . $project, $regs[3],"a/" . $regs[6],$regs[4],"b/" . $regs[6]);
		}
	}

	$tpl->clear_all_assign();
	$tpl->display("commitdiff_footer.tpl");
}

function git_heads($projectroot,$project)
{
	global $tpl;
	$head = git_read_head($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("head",$head);
	$tpl->display("heads_nav.tpl");
	$tpl->display("heads_header.tpl");
	$taglist = git_read_refs($projectroot, $project, "refs/heads");
	if (isset($taglist) && (count($taglist) > 0)) {
		$alternate = FALSE;
		foreach ($taglist as $i => $entry) {
			$tpl->clear_all_assign();
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			$tpl->assign("project",$project);
			$tpl->assign("age",$entry['age']);
			$tpl->assign("name",$entry['name']);
			$tpl->display("heads_item.tpl");
		}
	}
	$tpl->clear_all_assign();
	$tpl->display("heads_footer.tpl");
}

function git_tags($projectroot,$project)
{
	global $tpl;
	$head = git_read_head($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("head",$head);
	$tpl->display("tags_nav.tpl");
	$taglist = git_read_refs($projectroot, $project, "refs/tags");
	if (isset($taglist) && (count($taglist) > 0)) {
		$alternate = FALSE;
		foreach ($taglist as $i => $entry) {
			$tpl->clear_all_assign();
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			$tpl->assign("project",$project);
			$tpl->assign("age",$entry['age']);
			$tpl->assign("name",$entry['name']);
			$tpl->assign("reftype",$entry['reftype']);
			$tpl->assign("refid",$entry['refid']);
			$tpl->assign("id",$entry['id']);
			$tpl->assign("type",$entry['type']);
			if (isset($entry['comment']) && isset($entry['comment'][0]))
				$tpl->assign("comment",$entry['comment'][0]);
			$tpl->display("tags_item.tpl");
		}
	}
	$tpl->clear_all_assign();
	$tpl->display("tags_footer.tpl");
}

function git_opml($projectroot,$projectlist)
{
	global $tpl,$gitphp_conf;
	$projlist = git_read_projects($projectroot,$projectlist);
	header("Content-type: text/xml; charset=UTF-8");
	$tpl->clear_all_assign();
	$tpl->display("opml_header.tpl");
	echo "\n";
	foreach ($projlist as $cat => $plist) {
		if (is_array($plist)) {
			foreach ($plist as $i => $proj) {
				$head = git_read_head($projectroot . $proj);
				$co = git_read_commit($projectroot . $proj, $head);
				$tpl->clear_all_assign();
				$tpl->assign("proj",$proj);
				$tpl->assign("self",$gitphp_conf['self']);
				$tpl->display("opml_item.tpl");
				echo "\n";
			}
		} else {
			$head = git_read_head($projectroot . $plist);
			$co = git_read_commit($projectroot . $plist, $head);
			$tpl->clear_all_assign();
			$tpl->assign("proj",$plist);
			$tpl->assign("self",$gitphp_conf['self']);
			$tpl->display("opml_item.tpl");
			echo "\n";
		}
	}

	$tpl->clear_all_assign();
	$tpl->display("opml_footer.tpl");
}

function git_rss($projectroot,$project)
{
	global $tpl,$gitphp_conf;
	$head = git_read_head($projectroot . $project);
	$revlist = git_read_revlist($projectroot . $project, $head, 150);
	header("Content-type: text/xml; charset=UTF-8");
	$tpl->clear_all_assign();
	$tpl->assign("self",$gitphp_conf['self']);
	$tpl->assign("project",$project);
	$tpl->display("rss_header.tpl");

	for ($i = 0; $i <= count($revlist); $i++) {
		$commit = $revlist[$i];
		$co = git_read_commit($projectroot . $project, $commit);
		if (($i >= 20) && ((time() - $co['committer_epoch']) > 48*60*60))
			break;
		$cd = date_str($co['committer_epoch']);
		$difftree = array();
		$diffout = shell_exec("env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-diff-tree -r " . $co['parent'] . " " . $co['id']);
		$tok = strtok($diffout,"\n");
		while ($tok !== false) {
			if (ereg("^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)([0-9]{0,3})\t(.*)$",$tok,$regs))
				$difftree[] = $regs[7];
			$tok = strtok("\n");
		}
		$tpl->clear_all_assign();
		$tpl->assign("cdmday",$cd['mday']);
		$tpl->assign("cdmonth",$cd['month']);
		$tpl->assign("cdhour",$cd['hour']);
		$tpl->assign("cdminute",$cd['minute']);
		$tpl->assign("title",htmlentities($co['title']));
		$tpl->assign("author",htmlentities($co['author']));
		$tpl->assign("cdrfc2822",$cd['rfc2822']);
		$tpl->assign("self",$gitphp_conf['self']);
		$tpl->assign("project",$project);
		$tpl->assign("commit",$commit);
		$tpl->assign("comment",$co['comment']);
		$tpl->assign("difftree",$difftree);
		$tpl->display("rss_item.tpl");
	}

	$tpl->clear_all_assign();
	$tpl->display("rss_footer.tpl");
}

function git_blob($projectroot, $project, $hash, $file, $hashbase)
{
	global $gitphp_conf,$tpl;
	if (!isset($hash) && isset($file)) {
		$base = $hashbase ? $hashbase : git_read_head($projectroot . $project);
		$hash = git_get_hash_by_path($projectroot . $project, $base,$file,"blob");
	}
	$catout = shell_exec("env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-cat-file blob " . $hash);
	if (isset($hashbase) && ($co = git_read_commit($projectroot . $project, $hashbase))) {
		$tpl->clear_all_assign();
		$tpl->assign("project",$project);
		$tpl->assign("hashbase",$hashbase);
		$tpl->assign("tree",$co['tree']);
		$tpl->assign("hash",$hash);
		if (isset($file))
			$tpl->assign("file",$file);
		$tpl->assign("title",$co['title']);
		$tpl->display("blob_nav.tpl");
	} else {
		$tpl->clear_all_assign();
		$tpl->assign("hash",$hash);
		$tpl->display("blob_emptynav.tpl");
	}
	$tpl->clear_all_assign();
	if (isset($file))
		$tpl->assign("file",$file);
	$tpl->display("blob_header.tpl");

	$usedgeshi = $gitphp_conf['geshi'];
	if ($usedgeshi) {
		$usedgeshi = FALSE;
		include_once($gitphp_conf['geshiroot'] . "geshi.php");
		$geshi = new GeSHi("",'php');
		if ($geshi) {
			$lang = "";
			if (isset($file))
				$lang = $geshi->get_language_name_from_extension(substr(strrchr($file,'.'),1));
			if (isset($lang) && (strlen($lang) > 0)) {
				$geshi->set_source($catout);
				$geshi->set_language($lang);
				$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
				echo $geshi->parse_code();
				$usedgeshi = TRUE;
			}
		}
	}

	if (!$usedgeshi) {
		$lines = explode("\n",$catout);
		foreach ($lines as $i => $line) {
			/*
			 * TODO: Convert tabs to spaces
			 */
			$tpl->clear_all_assign();
			$tpl->assign("nr",$i+1);
			$tpl->assign("line",htmlentities($line));
			$tpl->display("blob_line.tpl");
		}
	}
	$tpl->clear_all_assign();
	$tpl->display("blob_footer.tpl");
}

function git_blob_plain($projectroot,$project,$hash,$file)
{
	global $gitphp_conf;
	if ($file)
		$saveas = $file;
	else
		$saveas = $hash . ".txt";
	header("Content-type: text/plain; charset=UTF-8");
	header("Content-disposition: inline; filename=\"" . $saveas . "\"");
	echo shell_exec("env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-cat-file blob " . $hash);
}

function git_blobdiff($projectroot,$project,$hash,$hashbase,$hashparent,$file)
{
	global $gitphp_conf,$tpl;
	$ret = prep_tmpdir($gitphp_conf['gittmp']);
	if ($ret !== TRUE) {
		echo $ret;
		return;
	}
	if (isset($hashbase) && ($co = git_read_commit($projectroot . $project, $hashbase))) {
		$tpl->clear_all_assign();
		$tpl->assign("project",$project);
		$tpl->assign("hash",$hash);
		$tpl->assign("hashbase",$hashbase);
		$tpl->assign("hashparent",$hashparent);
		$tpl->assign("tree",$co['tree']);
		$tpl->assign("title",$co['title']);
		$tpl->display("blobdiff_nav.tpl");
	} else {
		$tpl->clear_all_assign();
		$tpl->assign("hash",$hash);
		$tpl->assign("hashparent",$hashparent);
		$tpl->display("blobdiff_emptynav.tpl");
	}
	$tpl->clear_all_assign();
	if (isset($file))
		$tpl->assign("file",$file);
	$tpl->assign("project",$project);
	$tpl->assign("hashparent",$hashparent);
	$tpl->assign("hashbase",$hashbase);
	$tpl->assign("hash",$hash);
	$tpl->display("blobdiff_header.tpl");
	git_diff_print($projectroot . $project, $hashparent,($file?$file:$hashparent),$hash,($file?$file:$hash));
	$tpl->clear_all_assign();
	$tpl->display("blobdiff_footer.tpl");
}

function git_blobdiff_plain($projectroot,$project,$hash,$hashbase,$hashparent)
{
	global $gitphp_conf;
	$ret = prep_tmpdir($gitphp_conf['gittmp']);
	if ($ret !== TRUE) {
		echo $ret;
		return;
	}
	header("Content-type: text/plain; charset=UTF-8");
	git_diff_print($projectroot . $project, $hashparent,($file?$file:$hashparent),$hash,($file?$file:$hash),"plain");
}

function git_history($projectroot,$project,$hash,$file)
{
	global $tpl,$gitphp_conf;
	if (!isset($hash))
		$hash = git_read_head($projectroot . $project);
	$co = git_read_commit($projectroot . $project, $hash);
	$refs = read_info_ref($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->assign("tree",$co['tree']);
	$tpl->display("history_nav.tpl");
	$tpl->assign("title",$co['title']);
	$tpl->assign("file",$file);
	$tpl->display("history_header.tpl");
	$cmdout = shell_exec("env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-rev-list " . $hash . " | env GIT_DIR=" . $projectroot . $project . " " . $gitphp_conf['gitbin'] . "git-diff-tree -r --stdin '" . $file . "'");
	$alternate = FALSE;
	$lines = explode("\n",$cmdout);
	foreach ($lines as $i => $line) {
		if (ereg("^([0-9a-fA-F]{40})",$line,$regs))
			$commit = $regs[1];
		else if (ereg(":([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)\t(.*)$",$line,$regs) && isset($commit)) {
				$co = git_read_commit($projectroot . $project, $commit);
				$tpl->clear_all_assign();
				if ($alternate)
					$tpl->assign("class","dark");
				else
					$tpl->assign("class","light");
				$alternate = !$alternate;
				$tpl->assign("project",$project);
				$tpl->assign("agestringage",$co['age_string_age']);
				$tpl->assign("agestringdate",$co['age_string_date']);
				$tpl->assign("authorname",$co['author_name']);
				$tpl->assign("commit",$commit);
				$tpl->assign("file",$file);
				$tpl->assign("title",$co['title_short']);
				if (isset($refs[$commit]))
					$tpl->assign("commitref",$refs[$commit]);
				$blob = git_get_hash_by_path($projectroot . $project, $hash,$file);
				$blob_parent = git_get_hash_by_path($projectroot . $project, $commit,$file);
				if ($blob && $blob_parent && ($blob != $blob_parent)) {
					$tpl->assign("blob",$blob);
					$tpl->assign("blobparent",$blob_parent);
					$tpl->assign("difftocurrent",TRUE);
				}
				$tpl->display("history_item.tpl");
				unset($commit);
		}
	}
	$tpl->clear_all_assign();
	$tpl->display("history_footer.tpl");
}

?>
