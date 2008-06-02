<?php
/*
 *  display.git_blobdiff_plain.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob diff (plaintext)
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

 include_once('util.prep_tmpdir.php');
 include_once('gitutil.git_diff_print.php');

function git_blobdiff_plain($projectroot,$project,$hash,$hashbase,$hashparent)
{
	$ret = prep_tmpdir();
	if ($ret !== TRUE) {
		echo $ret;
		return;
	}
	header("Content-type: text/plain; charset=UTF-8");
	git_diff_print($projectroot . $project, $hashparent,($file?$file:$hashparent),$hash,($file?$file:$hash),"plain");
}

?>
