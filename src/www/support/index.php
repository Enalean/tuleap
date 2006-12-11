<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require('../support/support_utils.php');
require('../support/support_data.php');

if ($group_id) {
	$project=project_get_object($group_id);

	switch ($func) {

		case 'addsupport' : {
			require('../support/add_support.php');
			break;
		}
		case 'postaddsupport' : {
			$support_id=support_data_create_support($group_id,$support_category_id,$user_email,$summary,$details);

			if ($support_id) {
				//send an email to the submittor and default address for the project
				sr_utils_mail_followup($support_id, $project->getNewSupportAddress());
				require('../support/browse_support.php');
			} else {
				//some kind of error in creation
				exit_error('ERROR',$feedback);
			}
			break;
		}

		case 'postmodsupport' : {

		    $changes = array();

		    $changed = support_data_handle_update($group_id,$support_id,$priority,$support_status_id,
						     $support_category_id,$assigned_to,$summary,$canned_response,$details,$changes);
			/*
				see if we're supposed to send all modifications to an address
			*/

		    if ($changed) {
			if ($project->sendAllSupportUpdates()) {
				$address=$project->getNewSupportAddress();
			}
			/*
				now send the email
				it's no longer optional due to the group-level notification address
			*/
			sr_utils_mail_followup($support_id,$address,$changes);
		    }

			require('../support/browse_support.php');
			break;
		}

		case 'postaddcomment' : {
			require('../support/postadd_comment.php');
			if ($project->sendAllSupportUpdates()) {
				$address=$project->getNewSupportAddress();
			}
			sr_utils_mail_followup($support_id,$address,$changes);
			require('../support/browse_support.php');
			break;
		}
		case 'browse' : {
			require('../support/browse_support.php');
			break;
		}
		case 'detailsupport' : {
			if (user_ismember($group_id,'S1')) {
				require('../support/mod_support.php');
			} else {
				require('../support/detail_support.php');
			}
			break;
		}
		default : {
			require('../support/browse_support.php');
			break;
		}
	}

} else {

	exit_no_group();

}

?>
