<?php
/*
 *  display.git_search_files.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - search in files
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('defs.constants.php');
require_once('util.highlight.php');
require_once('gitutil.git_filesearch.php');
require_once('gitutil.git_read_commit.php');

function git_search_files($projectroot, $project, $hash, $search, $page = 0)
{
	global $tpl,$gitphp_conf;

	if (!($gitphp_conf['search'] && $gitphp_conf['filesearch'])) {
		$tpl->clear_all_assign();
		$tpl->assign("message","File search has been disabled");
		$tpl->display("message.tpl");
		return;
	}

	if (!isset($search) || (strlen($search) < 2)) {
		$tpl->clear_all_assign();
		$tpl->assign("error",TRUE);
		$tpl->assign("message","You must enter search text of at least 2 characters");
		$tpl->display("message.tpl");
		return;
	}
	if (!isset($hash)) {
		//$hash = git_read_head($projectroot . $project);
		$hash = "HEAD";
	}

	$co = git_read_commit($projectroot . $project, $hash);

	$filesearch = git_filesearch($projectroot . $project, $hash, $search, false, ($page * 100), 101);

	if (count($filesearch) < 1) {
		$tpl->clear_all_assign();
		$tpl->assign("message","No matches for '" . $search . "'.");
		$tpl->display("message.tpl");
		return;
	}

	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->assign("treehash",$co['tree']);
	$tpl->display("search_nav.tpl");

	$tpl->assign("search",$search);
	$tpl->assign("searchtype","file");
	if ($page > 0) {
		$tpl->assign("firstlink",TRUE);
		$tpl->assign("prevlink",TRUE);
		if ($page > 1)
			$tpl->assign("prevpage",$page-1);
	}
	if (count($filesearch) > 100) {
		$tpl->assign("nextlink",TRUE);
		$tpl->assign("nextpage",$page+1);
	}
	$tpl->display("search_pagenav.tpl");

	$tpl->assign("title",$co['title']);
	$tpl->display("search_header.tpl");

	$alternate = FALSE;
	$i = 0;
	foreach ($filesearch as $file => $data) {
		$tpl->clear_all_assign();
		if ($alternate)
			$tpl->assign("class","dark");
		else
			$tpl->assign("class","light");
		$alternate = !$alternate;
		$tpl->assign("project",$project);
		$tpl->assign("hashbase",$hash);
		$tpl->assign("file",$file);
		if (strpos($file,"/") !== false) {
			$f = basename($file);
			$d = dirname($file);
			if ($d == "/")
				$d = "";
			$hlt = highlight($f, $search, "searchmatch");
			if ($hlt)
				$hlt = $d . "/" . $hlt;
		} else
			$hlt = highlight($file, $search, "searchmatch");
		if ($hlt)
			$tpl->assign("filename",$hlt);
		else
			$tpl->assign("filename",$file);
		$tpl->assign("hash",$data['hash']);
		if ($data['type'] == "tree")
			$tpl->assign("tree",TRUE);
		if (isset($data['lines'])) {
			$matches = array();
			foreach ($data['lines'] as $line) {
				$hlt = highlight($line,$search,"searchmatch",floor(GITPHP_TRIM_LENGTH*1.5),true);
				if ($hlt)
					$matches[] = $hlt;
			}
			if (count($matches) > 0)
				$tpl->assign("matches",$matches);
		}
		$tpl->display("search_fileitem.tpl");
		$i++;
		if ($i >= 100)
			break;
	}

	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->assign("search",$search);
	$tpl->assign("searchtype","file");
	if (count($filesearch) > 100) {
		$tpl->assign("nextlink",TRUE);
		$tpl->assign("nextpage",$page+1);
	}
	$tpl->display("search_footer.tpl");
}

?>
