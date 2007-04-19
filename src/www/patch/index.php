<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: index.php 1405 2005-03-21 14:41:41Z guerin $

require_once('pre.php');
require('../patch/patch_utils.php');

if ($group_id) {

	switch ($func) {
		case 'addpatch' : {
			require('../patch/add_patch.php');
			break;
		}
		case 'postaddpatch' : {
			require('../patch/postadd_patch.php');
			require('../patch/browse_patch.php');
			break;
		}
		case 'postmodpatch' : {
			require('../patch/postmod_patch.php');
			require('../patch/browse_patch.php');
			break;
		}
		case 'postaddcomment' : {
			require('../patch/postadd_comment.php');
			require('../patch/browse_patch.php');
			break;
		}
		case 'browse' : {
			require('../patch/browse_patch.php');
			break;
		}
		case 'detailpatch' : {
			if (user_ismember($group_id,'C2')) {
				require('../patch/mod_patch.php');
			} else {
				require('../patch/detail_patch.php');
			}
			break;
		}
		default : {
			require('../patch/browse_patch.php');
			break;
		}
	}

} else {

	exit_no_group();

}
?>
