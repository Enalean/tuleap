<?php
/*
 *  display.git_tags.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - tags
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

 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_refs.php');

function git_tags($projectroot,$project)
{
	global $tpl;
	$head = git_read_head($projectroot . $project);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("head",$head);
	$tpl->display("tags_nav.tpl");
	$tpl->display("tags_header.tpl");
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

?>
