<?php
/*
 *  display.git_heads.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - heads
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

?>
