<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../bugs/bug_utils.php');
require('../bugs/bug_data.php');

if ($group_id) {
    
    // Initialize the global data structure before anything else
    bug_init($group_id);

    $project=project_get_object($group_id);
    $changed = false;
    $changes = array();

    switch ($func) {

    case 'addbug' : {
	require('../bugs/add_bug.php');
	break;
    }

    case 'postaddbug' : {

	// Get the list of bug fields used in the form 
	$vfl = bug_extract_field_list();       

	//data control layer
	$bug_id=bug_data_create_bug($group_id,$vfl, $add_cc, $add_file, $input_file);

	// Attach new file if there is one
	if ($bug_id && $add_file && $input_file) {
	    bug_attach_file($bug_id,$group_id,$input_file,
			    $input_file_name, $input_file_type,
			    $input_file_size,$file_description,
			    $changes);
	}

	// Add new cc if any
	if ($bug_id && $add_cc) {
	    bug_add_cc($bug_id,$group_id,$add_cc,$cc_comment,$changes);
	}

	// send an email to notify the user of the bug update
	if ($bug_id) {
	    bug_mail_followup($bug_id,$project->getNewBugAddress());
	    require('../bugs/browse_bug.php');
	} else {
	    //some error occurred
	    exit_error('ERROR',$feedback);
	}

	break;
    }

    case 'postmodbug' : {

	// Get the list of bug fields used in the form 
	$vfl = bug_extract_field_list();

	//data control layer
	$changed = bug_data_handle_update($group_id,$bug_id,$task_id_dependent,
					  $bug_id_dependent,$canned_response,$vfl,
					  $changes);

	// Attach new file if there is one
	if ($add_file && $input_file) {
	    $changed |= bug_attach_file($bug_id,$group_id,$input_file,
					$input_file_name,$input_file_type,
					$input_file_size,$file_description,
					$changes);
	}

	// Add new cc if any
	if ($add_cc) {
	    $changed |= bug_add_cc($bug_id,$group_id,$add_cc,$cc_comment,$changes);
	}

	if ($changed) {
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
	    bug_mail_followup($bug_id,$address,$changes);
	}

	require('../bugs/browse_bug.php');
	break;
    }

    case 'delete_dependent_task' : {
	if (user_ismember($group_id,'B1')) {

	    bug_data_delete_dependent_task($bug_id,$is_dependent_on_task_id);

	    // unsent bug_id var to make sure that it doesn;t
	    // impact the next bug query.
	    unset($bug_id);
	    unset($HTTP_GET_VARS['bug_id']);
	    require('../bugs/browse_bug.php');
	} else {
	    exit_permission_denied();
	}	
	break;	    
    }

    case 'delete_dependent_bug' : {
	if (user_ismember($group_id,'B1')) {

	    bug_data_delete_dependent_bug($bug_id,$is_dependent_on_bug_id);

	    // unsent bug_id var to make sure that it doesn;t
	    // impact the next bug query.
	    unset($bug_id);
	    unset($HTTP_GET_VARS['bug_id']);
	    require('../bugs/browse_bug.php');
	} else {
	    exit_permission_denied();
	}	
	break;	    
    }

    case 'delete_file' : {
	if (user_ismember($group_id,'B2')) {

	    bug_delete_file($group_id,$bug_id,$bug_file_id);

	    // unsent bug_id var to make sure that it doesn;t
	    // impact the next bug query.
	    unset($bug_id);
	    unset($HTTP_GET_VARS['bug_id']);
	    require('../bugs/browse_bug.php');
	} else {
	    exit_permission_denied();
	}	
	break;	    
    }

    case 'delete_cc' : {
	$changed = bug_delete_cc($group_id,$bug_id,$bug_cc_id,$changes);
	
	if ($changed) {
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
	    bug_mail_followup($bug_id,$address,$changes);
	}
	
	// unsent bug_id var to make sure that it doesn;t
	// impact the next bug query.
	unset($bug_id);
	unset($HTTP_GET_VARS['bug_id']);
	require('../bugs/browse_bug.php');
	
	break;	    
    }

    case 'postaddcomment' : {
	require('../bugs/postadd_comment.php');
	if ($project->sendAllBugUpdates()) {
	    $address=$project->getNewBugAddress();
	}

	if ($changed) {
	    bug_mail_followup($bug_id,$address,$changes);
	}
	require('../bugs/browse_bug.php');
	break;
    }

    case 'browse' : {
	require('../bugs/browse_bug.php');
	break;
    }

    case 'detailbug' : {
	// If a printer version is requested force the detail_bug script
	// even if user logged in.
	if (user_ismember($group_id,'B1') && !$pv) {
	    require('../bugs/mod_bug.php');
	} else {
	    require('../bugs/detail_bug.php');
	}
	break;
    }

    case 'modfilters' : {
	if (user_isloggedin()) {
	    require('../bugs/mod_filters.php');
	    break;
	} else {
	    exit_not_logged_in();
	}
    }

    case 'postmodfilters' : {
	if (user_isloggedin()) {
	    require('../bugs/postmod_filters.php');
	    require('../bugs/mod_filters.php');
	    break;
	} else {
	    exit_not_logged_in();
	}
    }

    default : {
	require('../bugs/browse_bug.php');
	break;
    }

    }

} else {

    exit_no_group();

}
?>
