<?php
/*
 *  display.git_opml.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - OPML feed
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

 include_once('gitutil.git_read_projects.php');
 include_once('gitutil.git_read_head.php');
 include_once('gitutil.git_read_commit.php');

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

?>
