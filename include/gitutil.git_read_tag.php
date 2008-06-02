<?php
/*
 *  gitutil.git_read_tag.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read tag
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

 include_once('gitutil.git_cat_file.php');

function git_read_tag($project, $tag_id)
{
	$tag = array();
	$tagout = git_cat_file($project, $tag_id, NULL, "tag");
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

?>
