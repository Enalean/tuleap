<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../pm/pm_utils.php');
require($DOCUMENT_ROOT.'/bugs/bug_data.php'); // needed by pm_data
require('../pm/pm_data.php');

if ($group_id ) {
	/*
		Verify that this group_project_id falls under this group
	*/

    // Initialize the global data structure before anything else
    pm_init($group_id);

    $project=project_get_object($group_id);

	//can this person view these tasks? they may have hacked past the /pm/index.php page
	if (user_isloggedin() && user_ismember($group_id)) {
		$public_flag='0,1';
	} else {
		$public_flag='1';
	}

	/* if no function given then it defaults to browse */
	if (!$func) {
		$func='browse';
	}

	/* if no sub project id given then it defaults to ANY (0) */
	if (!isset($group_project_id)) {
	    $group_project_id = 0; 
	}

	/*
		Verify that this subproject belongs to this project.
		If sub project is 0 then it means any sub project
		for this group so don't make any verification
	*/
	
	if (!pm_isvarany($group_project_id)) {

	    if (is_array($group_project_id)) 
		$gpid_arr = $group_project_id;
	    else
		$gpid_arr[] = $group_project_id;

	    reset($gpid_arr);
	    while (list(,$v) = each($gpid_arr)) {
		$result=db_query("SELECT * FROM project_group_list ".
			"WHERE group_project_id='$v' AND group_id='$group_id' AND is_public IN ($public_flag)");
		if (db_numrows($result) < 1) {
			exit_permission_denied();
		}
	    }
	}

	/*
		Figure out which function we're dealing with here
	*/

	switch ($func) {

	       case 'addtask' : {
			if (user_ismember($group_id,'P2')) {
				include '../pm/add_task.php';
			} else {
				exit_permission_denied();
			}
			break;;
		}

		case 'postaddtask' : {
			if (user_ismember($group_id,'P2')) {

                // Get the list of task fields used in the form 
                $vfl = pm_extract_field_list();

    			$project_task_id = pm_data_create_task ($group_project_id,$group_id,$dependent_on,$assigned_to,$vfl,$bug_id);

            	// Attach new file if there is one
            	if ($project_task_id && $add_file && $input_file) {
            	    pm_attach_file($project_task_id,$group_id,$input_file,
            			    $input_file_name, $input_file_type,
            			    $input_file_size,$file_description,
            			    $changes);
            	}

            	if ($project_task_id) {
            	    /*
            	      now send the email
            	      it's no longer optional due to the group-level notification address
            	    */
            	    pm_mail_followup($project_task_id,$project->getNewTaskAddress());
            	}

				include '../pm/browse_task.php';
			} else {
				exit_permission_denied();
			}
			break;;
		}

		case 'postmodtask' : {
			if (user_ismember($group_id,'P2')) {
                
                // Get the list of task fields used in the form 
                $vfl = pm_extract_field_list();

    			$changed = pm_data_update_task ($old_group_project_id,$project_task_id,$group_project_id,$group_id,$dependent_on,$assigned_to,$vfl,$changes);
				
            	// Attach new file if there is one
            	if ($add_file && $input_file) {
            	    $changed |= pm_attach_file($project_task_id,$group_id,$input_file,
            					$input_file_name,$input_file_type,
            					$input_file_size,$file_description,
            					$changes);
            	}

            	// Add new cc if any
            	if ($add_cc) {
            	    $changed |= pm_add_cc($project_task_id,$group_id,$add_cc,$cc_comment,$changes);
            	}
				
            	if ($changed) {
            	    /*
            	      see if we're supposed to send all modifications to an address
            	    */
            	    if ($project->sendAllTaskUpdates()) {
            		    $address=$project->getNewTaskAddress();
            	    }
            	    
            	    /*
            	      now send the email
            	      it's no longer optional due to the group-level notification address
            	    */
            	    pm_mail_followup($project_task_id,$address,$changes);
            	}

				// reset subproject id to avoid interference with browsing
				// selection criteria
				unset($group_project_id);
				include '../pm/browse_task.php';
				break;;
			} else {
				exit_permission_denied();
			}
		}

		case 'browse' : {
			include '../pm/browse_task.php';
			break;;
		}

		case 'detailtask' : {
			if (user_ismember($group_id,'P2')) {
				include '../pm/mod_task.php';
			} else {
				include '../pm/detail_task.php';
			}
			break;;
		}

        case 'delete_cc' : {
            $changed = pm_delete_cc($group_id,$project_task_id,$project_cc_id,$changes);
            
            if ($changed) {
                /*
                  see if we're supposed to send all modifications to an address
                */
                if ($project->sendAllTaskUpdates()) {
            	    $address=$project->getNewTaskAddress();
                }
                
                /*
                  now send the email
                  it's no longer optional due to the group-level notification address
                */
                pm_mail_followup($project_task_id,$address,$changes);
            }
        
            // unsent project_task_id var to make sure that it doesn;t
            // impact the next task query.
    		unset($group_project_id);
    		include '../pm/browse_task.php';
        
        break;	    
        } // case

        case 'delete_file' :
        	if (user_ismember($group_id,'P2')) {
        
        	    pm_delete_file($group_id,$project_task_id,$project_file_id);
        
        	    // unsent project_task_id var to make sure that it doesn;t
        	    // impact the next task query.
        	    unset($project_task_id);
        	    unset($HTTP_GET_VARS['project_task_id']);
        	    include '../pm/browse_task.php';
        	} else {
        	    exit_permission_denied();
        	}	
        	break;	    

	} // switch

} else {
	//browse for group first message
	if (!$group_id || !$group_project_id) {
		exit_no_group();
	} else {
		exit_permission_denied();
	}
}
?>
