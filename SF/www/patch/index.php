<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../patch/patch_utils.php');

if ($group_id) {

	switch ($func) {
		case 'addpatch' : {
			include '../patch/add_patch.php';
			break;
		}
		case 'postaddpatch' : {
			include '../patch/postadd_patch.php';
			include '../patch/browse_patch.php';
			break;
		}
		case 'postmodpatch' : {
			include '../patch/postmod_patch.php';
			include '../patch/browse_patch.php';
			break;
		}
		case 'postaddcomment' : {
			include '../patch/postadd_comment.php';
			include '../patch/browse_patch.php';
			break;
		}
		case 'browse' : {
			include '../patch/browse_patch.php';
			break;
		}
		case 'detailpatch' : {
			if (user_ismember($group_id,'C2')) {
				include '../patch/mod_patch.php';
			} else {
				include '../patch/detail_patch.php';
			}
			break;
		}
		default : {
			include '../patch/browse_patch.php';
			break;
		}
	}

} else {

	exit_no_group();

}
?>
