<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../bugs/bug_utils.php');
require('../bugs/bug_data.php');

if ($group_id) {

	$project=project_get_object($group_id);
	switch ($func) {

		case 'addbug' : {
			include '../bugs/add_bug.php';
			break;
		}

		case 'postaddbug' : {
			//data control layer
			$bug_id=bug_data_create_bug($group_id,$summary,$details,$category_id,$bug_group_id,$priority,$assigned_to);
			if ($bug_id) {
				// send an email to notify the user and 
				// let the project know the bug was submitted
				mail_followup($bug_id,$project->getNewBugAddress());
				include '../bugs/browse_bug.php';
			} else {
				//some error occurred
				exit_error('ERROR',$feedback);
			}
			break;
		}

		case 'postmodbug' : {
			//data control layer
			bug_data_handle_update ($group_id,$bug_id,$status_id,$priority,$category_id,
				$assigned_to,$summary,$bug_group_id,$resolution_id,$details,
				$dependent_on_task,$dependent_on_bug,$canned_response);
			/*
				see if we're supposed to send all modifications to an address
			*/
			if ($project->sendAllBugUpdates()) {
				$address=$project->getNewBugAddress();
			}
			/*
				now send the email
				it's no longer optional due to the group-level notification address
			*/
			mail_followup($bug_id,$address);
			include '../bugs/browse_bug.php';
			break;
		}
/*
		case 'massupdate' : {
			//data control layer
			bug_data_mass_update ($group_id,$bug_id,$status_id,$priority,$category_id,
				$assigned_to,$bug_group_id,$resolution_id);
			include '../bugs/browse_bug.php';
			break;
		}
*/
		case 'postaddcomment' : {
			include '../bugs/postadd_comment.php';
                        if ($project->sendAllBugUpdates()) {
                                $address=$project->getNewBugAddress();
                        }       
			mail_followup($bug_id,$address);
			include '../bugs/browse_bug.php';
			break;
		}

		case 'browse' : {
			include '../bugs/browse_bug.php';
			break;
		}

		case 'detailbug' : {
			if (user_ismember($group_id,'B2')) {
				include '../bugs/mod_bug.php';
			} else {
				include '../bugs/detail_bug.php';
			}
			break;
		}

		case 'modfilters' : {
			if (user_isloggedin()) {
				include '../bugs/mod_filters.php';
				break;
			} else {
				exit_not_logged_in();
			}
		}

		case 'postmodfilters' : {
			if (user_isloggedin()) {
				include '../bugs/postmod_filters.php';
				include '../bugs/mod_filters.php';
				break;
			} else {
				exit_not_logged_in();
			}
		}

		default : {
			include '../bugs/browse_bug.php';
			break;
		}

	}

} else {

	exit_no_group();

}
?>
