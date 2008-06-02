<?php
/*
 *  display.git_shortlog.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - short log
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
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

 include_once('util.date_str.php');
 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_revlist.php');
 include_once('gitutil.git_read_commit.php');
 include_once('gitutil.read_info_ref.php');

function git_shortlog($projectroot,$project,$hash,$page)
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

?>
