<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

function pm_data_get_tasks ($group_id,$group_project_id) {
    if ($group_project_id) {
	$sql="SELECT project_task_id,summary ".
	    "FROM project_task ".
	    "WHERE group_project_id='$group_project_id' ".
	    "AND status_id <> '3' ORDER BY project_task_id DESC LIMIT 200";
    } else {
	// If subproject id is null then get all the task for this project (group_id)
	$sql="SELECT project_task.project_task_id,project_task.summary ".
	    "FROM project_task,project_group_list ".
	    "WHERE project_task.group_project_id=project_group_list.group_project_id ".
	    "AND project_task.status_id <> '3' ".
	    "AND project_group_list.group_id='$group_id' ORDER BY project_task_id DESC LIMIT 200";
    }
    return db_query($sql);
}

function pm_data_get_subprojects ($group_id) {
	$sql="SELECT group_project_id,project_name ".
		"FROM project_group_list WHERE group_id='$group_id'".
		" && is_public <> 9 order by order_id";
	return db_query($sql);
}

function pm_data_get_other_tasks ($group_id,$group_project_id,$project_task_id) {
    if ($group_project_id) {
	$sql="SELECT project_task_id,summary ".
	    "FROM project_task ".
	    "WHERE group_project_id='$group_project_id' ".
	    "AND status_id <> '3' ".
	    "AND project_task_id <> '$project_task_id' ORDER BY project_task_id DESC LIMIT 200";
    } else {
	// If subproject id is null then get all the task for this project (group_id)
	$sql="SELECT project_task.project_task_id,project_task.summary ".
	    "FROM project_task,project_group_list ".
	    "WHERE project_task.group_project_id=project_group_list.group_project_id ".
	    "AND project_task.status_id <> '3' ".
	    "AND project_task_id <> '$project_task_id' ".
	    "AND project_group_list.group_id='$group_id' ORDER BY project_task_id DESC LIMIT 200";
    }
	return db_query($sql);
}

function pm_data_get_technicians ($group_id) {
	$sql="SELECT user.user_id,user.user_name ".
		"FROM user,user_group ".
		"WHERE user.user_id=user_group.user_id ".
		"AND user_group.group_id='$group_id' ".
		"AND user_group.project_flags IN (1,2) ".
		"ORDER BY user.user_name";
	return db_query($sql);
}

function pm_data_get_submitters ($group_id=false) {
	$sql="SELECT DISTINCT user.user_id,user.user_name ".
		"FROM user,project_task ".
		"WHERE user.user_id=project_task.created_by ".
		"AND project_task.group_id='$group_id' ".
		"ORDER BY user.user_name";
	return db_query($sql);
}

function pm_data_get_assigned_to ($project_task_id) {
	$sql="SELECT assigned_to_id ".
		"FROM project_assigned_to ".
		"WHERE project_task_id='$project_task_id'";
	return db_query($sql);
}

function pm_data_get_assigned_to_name ($project_task_id) {
	$sql="SELECT user.user_name ".
		"FROM project_assigned_to, user ".
		"WHERE project_task_id='$project_task_id' ".
		"AND project_assigned_to.assigned_to_id = user.user_id";
	return db_query($sql);
}

function pm_data_get_statuses () {
	$sql='SELECT * FROM project_status';
	return db_query($sql);
}

function pm_data_get_status_name($string) {
	/*
		simply return status_name from project_status
	*/
	$sql="SELECT * FROM project_status WHERE status_id='$string'";
	$result=db_query($sql);
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,'status_name');
	} else {
		return 'Error - Not Found';
	}
}

function pm_data_get_group_name($group_project_id) {
	/*
		Simply return the resolution name for this id
	*/

	$sql="SELECT * FROM project_group_list WHERE group_project_id='$group_project_id'";
	$result=db_query($sql);
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,'project_name');
	} else {
		return 'Error - Not Found';
	}
}

//function pm_data_create_history ($field_name,$old_value,$project_task_id) {
//	global $feedback;
//	/*
//		handle the insertion of history for these parameters
//	*/
//	$sql="insert into project_history(project_task_id,field_name,old_value,mod_by,date) ".
//		"VALUES ('$project_task_id','$field_name','$old_value','".user_getid()."','".time()."')";
//	$result=db_query($sql);
//	if (!$result) {
//		$feedback .= ' ERROR IN AUDIT TRAIL - '.db_error();
//		error_set_true();
//		error_set_string(db_error());
//	}
//}

function pm_data_insert_assigned_to($array,$project_task_id) {
	global $feedback;
	/*
		Insert the people this task is assigned to
	*/
	$user_count=count($array);
	if ($user_count < 1) {
		//if no users selected, insert user "none"
		$sql="INSERT INTO project_assigned_to VALUES ('','$project_task_id','100')";
		$result=db_query($sql);
	} else {
		for ($i=0; $i<$user_count; $i++) {
			if (($user_count > 1) && ($array[$i]==100)) {
				//don't insert the row if there's more 
				//than 1 item selected and this item is the "none user"
			} else {
				$sql="INSERT INTO project_assigned_to VALUES ('','$project_task_id','$array[$i]')";
				//echo "\n$sql";
				$result=db_query($sql);
				if (!$result) {
					$feedback .= ' ERROR inserting project_assigned_to '.db_error();
				}
			}
		}
	}
}

function pm_data_update_assigned_to($array,$project_task_id) {
	/*
		DELETE THEN Insert the people this task is assigned to
	*/
	$toss=db_query("DELETE FROM project_assigned_to WHERE project_task_id='$project_task_id'");
	pm_data_insert_assigned_to($array,$project_task_id);
}

function pm_data_insert_dependent_tasks($array,$project_task_id) {
	global $feedback;
	/*
		Insert the list of dependencies
	*/
	$depend_count=count($array);
	if ($depend_count < 1) {
		//if no tasks selected, insert task "none"
		$sql="INSERT INTO project_dependencies VALUES ('','$project_task_id','100')";
		$result=db_query($sql);
	} else {
		for ($i=0; $i<$depend_count; $i++) {
			if (($depend_count > 1) && ($array[$i]==100)) {
				//don't insert the row if there's more
				//than 1 item selected and this item is the "none task"
			} else {
				$sql="INSERT INTO project_dependencies VALUES ('','$project_task_id','$array[$i]')";
				//echo "\n$sql";
				$result=db_query($sql);
	
				if (!$result) {
					$feedback .= ' ERROR inserting dependent_tasks '.db_error();
				}
			}
		}
	}
}

function pm_data_update_dependent_tasks($array,$project_task_id) {
	/*
		DELETE THEN Insert the list of dependencies
	*/
	$toss=db_query("DELETE FROM project_dependencies WHERE project_task_id='$project_task_id'");
	pm_data_insert_dependent_tasks($array,$project_task_id);
}

function pm_data_create_task ($group_project_id,$group_id,$dependent_on,$assigned_to,$vfl,$bug_id=false) {

	global $feedback;
	
    //we don't force them to be logged in to submit a task
    if (!user_isloggedin()) {
    	$user=100;
    } else {
        $user=user_getid();
    }
	
	if (!$group_project_id || (pm_check_empty_fields($vfl) == false) ) {
		exit_missing_param();
	}

    //first make sure this wasn't double-submitted
    $res=db_query("SELECT * FROM project_task WHERE created_by='$user' AND summary='".$vfl['summary']);
    if ($res && db_numrows($res) > 0) {
	    $feedback = ' ERROR - DOUBLE SUBMISSION. You have already submitted a task with the same summary. Please don\'t do that ';
	    return;		
    }

    // Finally, create the task itself
    // Remark: this SQL query only sets up the values for fields used by
    // this project. For other unused fields we assume that the DB will set
    // up an appropriate default value (see project_task table definition)

    // build the variable list of fiels and values
    reset($vfl);
    while ( list($field,$value) = each($vfl)) {
    	if (pm_data_is_special($field)) { continue; }
    	$vfl_cols .= ','.$field;
    	if (pm_data_is_text_area($field) ||
    	    pm_data_is_text_field($field)) {
    	    $value = htmlspecialchars($value);
    	} else if (pm_data_is_date_field($field)) {
    	    // if it's a date we must convert the format to unix time
    	    list($value,$ok) = util_date_to_unixtime($value);
        }

    	$vfl_values .= ',\''.$value.'\'';
    }    

    // Add all special fields that were not handled in the previous block
    $fixed_cols = 'status_id,created_by,summary,details';
    $fixed_values = "'1','$user','".
	htmlspecialchars($vfl['summary'])."','".htmlspecialchars($vfl['details'])."'";

    $sql="INSERT INTO project_task ($fixed_cols $vfl_cols) VALUES ($fixed_values $vfl_values)";
    //echo "DBG - SQL insert task: $sql";
    $result=db_query($sql);
    $project_task_id=db_insertid($result);

    if (!$project_task_id) {
	    $feedback = 'INSERT new task failed. Report to the Administrator<br>'.
	    'SQL statement:<br>'.$sql.'<br>';
	    return;
    } else {
		/*
		  Insert a task dependency => the create task comes from the
		  Bugs menu (Create Task)
		*/
		if ($bug_id) {
		    $dep_tasks = util_result_column_to_array (bug_data_get_dependent_tasks($bug_id));
		    $dep_tasks[] = $project_task_id;
		    bug_data_update_dependent_tasks($group_id,$dep_tasks,$bug_id);
		}
    
		pm_data_insert_assigned_to($assigned_to,$project_task_id);
		pm_data_insert_dependent_tasks($dependent_on,$project_task_id);

		$feedback .= ' Successfully added task ';
    }        

    return $project_task_id;
}

function pm_data_get_dependent_tasks ($project_task_id=false, $notin=false) {
	/*
		Get the list of ids this is dependent on
	*/
	$sql="SELECT is_dependent_on_task_id FROM project_dependencies WHERE project_task_id='$project_task_id'";
	if ($notin) {
	    $sql .= ' AND is_dependent_on_task_id NOT IN ('. join(',',$notin).')';
	}
	return db_query($sql);
}

function pm_data_update_task ($old_group_project_id,$project_task_id,$group_project_id,$group_id,$dependent_on,$assigned_to,$vfl,
		&$changes) {

	if (!$group_project_id || !$project_task_id || !$old_group_project_id || !$group_id || (pm_check_empty_fields($vfl) == false) ) {
		exit_missing_param();
	}

	$sql="SELECT * FROM project_task WHERE project_task_id='$project_task_id' AND group_project_id='$old_group_project_id'";

	$result=db_query($sql);

	if (db_numrows($result) < 1) {
		exit_permission_denied();
	}

    /*
      See which fields changed during the modification
      and if we must keep history then do it. Also add them to the update
      statement
    */
    $changes = array();
    reset($vfl);
    while (list($field,$value) = each($vfl)) {
	
    	// skip over special fields  except for summary which in this 
    	// particular case can be processed normally
    	if (pm_data_is_special($field) && ($field != 'summary')) {
    	    continue; }
    
    	$old_value = db_result($result,0,$field);
    	$is_text = (pm_data_is_text_field($field) || pm_data_is_text_area($field));
    	if  ($is_text) {
    	    $differ = ($old_value != stripslashes(htmlspecialchars($value))); 
    	} else if (pm_data_is_date_field($field)) {
    	    // if it's a date we must convert the format to unix time
    	    list($value,$ok) = util_date_to_unixtime($value);
    	    $differ = ($old_value != $value);
    	} else {
    	    $differ = ($old_value != $value);
    	}
    
    	if ($differ) {
    	    if ($is_text) {
        		$upd_list .= "$field='".htmlspecialchars($value)."',";
        		pm_data_add_history($field,addslashes($old_value),$project_task_id);
        		$value = stripslashes($value);
    	    } else {
        		$upd_list .= "$field='$value',";
        		pm_data_add_history($field,$old_value,$project_task_id);
    	    }
    
    	    // Keep track of the change
    	    $changes[$field]['del']=
    		pm_field_display($field,$group_id,$old_value,false,false,true,true);
    	    $changes[$field]['add']=
    		pm_field_display($field,$group_id,$value,false,false,true,true);
    	}
    }

    // Details field history is handled a little differently. Followup comments
    // are added in the task history along with the comment type.
    $details = $vfl['details'];
    if ($details != '') {
    	pm_data_add_history ('details',htmlspecialchars($details),$project_task_id);
    	$changes['details']['add'] = stripslashes($details);
    	$changes['details']['type'] =
    	    pm_data_get_value('comment_type_id',$group_id, $vfl['comment_type_id']);
    }

    /*
      DELETE THEN Insert the list of task dependencies 
      Also compute the task added and removed - Never mention the 'None' item
    */

    $old_dep_on = util_result_column_to_array (pm_data_get_dependent_tasks($project_task_id));

    pm_data_update_dependent_tasks($dependent_on,$project_task_id);

    // Add None in both lists to make sure it is never taken into account
    // in the diffs
    $old_dep_on[] = 100;
    $dependent_on[] = 100;
    
    list($deleted_tasks,$added_tasks) = util_double_diff_array($old_dep_on,$dependent_on);

    if (count($deleted_tasks))
	    $changes['Dependent Tasks']['del'] = join(',',$deleted_tasks);
    if (count($added_tasks))
	    $changes['Dependent Tasks']['add'] = join(',',$added_tasks);

    /*
      DELETE THEN Insert the list of assigned to
      Also compute the task added and removed - Never mention the 'None' item
    */

    $old_assigned_to = util_result_column_to_array (pm_data_get_assigned_to($project_task_id));

    pm_data_update_assigned_to($assigned_to,$project_task_id);

    // Add None in both lists to make sure it is never taken into account
    // in the diffs
    $old_assigned_to[] = 100;
    $assigned_to[] = 100;
    list($deleted_assigned_to,$added_assigned_to) = util_double_diff_array($old_assigned_to,$assigned_to);

    if (count($deleted_assigned_to))
	    $changes['Assigned to']['del'] = pm_get_assigned_to_list_name($deleted_assigned_to);
    if (count($added_assigned_to))
	    $changes['Assigned to']['add'] = pm_get_assigned_to_list_name($added_assigned_to);
    
    /*
      Finally, build the full SQL query and update the task itself (if need be)
    */

    $result = true;
    if ($upd_list) {
    	// strip the excess comma at the end of the update field list
    	$upd_list = substr($upd_list,0,-1);
    
    	$sql="UPDATE project_task SET $upd_list ".
    	    "WHERE project_task_id='$project_task_id' AND group_project_id='$old_group_project_id'";
    
    	//echo "DBG - update sql : $sql<br>";
    	$result=db_query($sql);
    }

    if (!$result) {
    	exit_error('UPDATE FAILED',$feedback);
    	return false;
    } else {
    	$feedback .= " Successfully Updated Task";
    	return true;
    }

}

function pm_data_get_group_project_id($project_task_id) {
	/*
		simply return the group_project_id associated to $project_task_id
	*/
	$sql="SELECT * FROM project_task WHERE project_task_id = $project_task_id";
	$result=db_query($sql);
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,'group_project_id');
	} else {
	    // return the default to ANY (0)
		return 0;
	}
}

function pm_data_get_notification_roles() {
    $sql = 'SELECT * FROM project_notification_role ORDER BY rank ASC;';
    return db_query($sql);
}

function pm_data_get_notification_events() {
    $sql = 'SELECT * FROM project_notification_event ORDER BY rank ASC;';
    return db_query($sql);
}

function pm_data_get_notification($user_id) {
    $sql = "SELECT role_id,event_id,notify FROM project_notification WHERE user_id='$user_id'";
    return db_query($sql);
}

function pm_data_get_watchees($user_id) {
    $sql = "SELECT watchee_id FROM project_watcher WHERE user_id='$user_id'";
    return db_query($sql);    
}

function pm_data_get_watchers($user_id) {
    $sql = "SELECT user_id FROM project_watcher WHERE watchee_id='$user_id'";
    return db_query($sql);    
}

function pm_data_delete_watchees($user_id) {
    $sql = "DELETE FROM pm_watcher WHERE user_id='$user_id'";
    return db_query($sql);
}

function pm_data_insert_watchees($user_id, $arr_watchees) {
    $sql = 'INSERT INTO project_watcher (user_id,watchee_id) VALUES ';
    $num_watchees = count($arr_watchees);
    for ($i=0; $i<$num_watchees; $i++) {
	$sql .= "('$user_id','".$arr_watchees[$i]."'),";
    } 
    $sql = substr($sql,0,-1); // remove extra comma at the end 
    return db_query($sql);
}

function pm_data_delete_notification($user_id) {
    $sql = "DELETE FROM pm_notification WHERE user_id='$user_id'";
    return db_query($sql);
}

function pm_data_insert_notification($user_id, $arr_roles, $arr_events,
				    $arr_notification) {
    $sql = 'INSERT INTO project_notification (user_id,role_id,event_id,notify) VALUES ';

    $num_roles = count($arr_roles);
    $num_events = count($arr_events);
    for ($i=0; $i<$num_roles; $i++) {
	$role_id = $arr_roles[$i]['role_id'];
 	for ($j=0; $j<$num_events; $j++) { 
	    $event_id = $arr_events[$j]['event_id'];
 	    $sql .= "('$user_id','$role_id','$event_id','".$arr_notification[$role_id][$event_id]."'),"; 
 	} 
     } 
     $sql = substr($sql,0,-1); // remove extra comma at the end 
     return db_query($sql); 
}

function pm_data_get_cc_list ($project_task_id=false) {
    $sql="SELECT project_cc_id,project_cc.email,project_cc.added_by,project_cc.comment,project_cc.date,user.user_name ".
	    "FROM project_cc,user ".
	    "WHERE added_by=user.user_id ".
	    "AND project_task_id='$project_task_id' ORDER BY date DESC";
    return db_query($sql);
}

function pm_data_get_all_fields ($group_id=false,$reload=false) {

    /*
           Get all the possible task fields for this project both used and unused. If
           used then show the project specific information about field usage
           otherwise show the default usage parameter
           Make sure array element are sorted by ascending place
      */

    global $TF_USAGE_BY_ID,$TF_USAGE_BY_NAME, $AT_START;

    // Do nothing if already set and reload not forced
    if (isset($TF_USAGE_BY_ID) && !$reload) {
	    return;
    }

    // Clean up the array
    $TF_USAGE_BY_ID=array();
    $TF_USAGE_BY_NAME=array();

    // First get the all the defaults. 
    $sql='SELECT project_field.project_field_id, field_name, display_type, '.
	'display_size,label, description,scope,required,empty_ok,keep_history,special, custom, '.
	'group_id, use_it,show_on_add,show_on_add_members, place, custom_label,'.
	'custom_description,custom_display_size,custom_empty_ok,custom_keep_history '.
	'FROM project_field, project_field_usage '.
	'WHERE group_id=100  '.
	'AND project_field.project_field_id=project_field_usage.project_field_id ';
   
    $res_defaults = db_query($sql);

    // Now put all used fields in a global array for faster access
    // Index both by field_name and project_field_id
    while ($field_array = db_fetch_array($res_defaults)) {
    	$TF_USAGE_BY_ID[$field_array['project_field_id'] ] = $field_array;
    	$TF_USAGE_BY_NAME[$field_array['field_name'] ] = $field_array;
    }

    // Then select  all project specific entries
    $sql='SELECT project_field.project_field_id, field_name, display_type, '.
	'display_size,label, description,scope,required,empty_ok,keep_history,special, custom, '.
	'group_id, use_it, show_on_add, show_on_add_members, place, custom_label,'.
	'custom_description,custom_display_size,custom_empty_ok,custom_keep_history '.
	'FROM project_field, project_field_usage '.
	'WHERE group_id='.$group_id.
	' AND project_field.project_field_id=project_field_usage.project_field_id ';

    $res_project = db_query($sql);

    // And override entries in the default array
    while ($field_array = db_fetch_array($res_project)) {
    	$TF_USAGE_BY_ID[$field_array['project_field_id'] ] = $field_array;
    	$TF_USAGE_BY_NAME[$field_array['field_name'] ] = $field_array;
    }

    //Debug code
    //echo "<br>DBG - At end of project_get_all_fields: $rows";
    //reset($TF_USAGE_BY_NAME);
    //while (list($key, $val) = each($TF_USAGE_BY_NAME)) {
    //	echo "<br>DBG - $key -> use_it: $val[use_it], $val[place]";
    //}
      
    // rewind internal pointer of global arrays
    reset($TF_USAGE_BY_ID);
    reset($TF_USAGE_BY_NAME);
    $AT_START = true;
}

function pm_data_get_display_type($field, $by_field_id=false) {
    global $TF_USAGE_BY_ID,$TF_USAGE_BY_NAME;
    return($by_field_id ? $TF_USAGE_BY_ID[$field]['display_type'] : $TF_USAGE_BY_NAME[$field]['display_type']);
}

function pm_data_is_empty_ok($field, $by_field_id=false) {
    global $TF_USAGE_BY_ID,$TF_USAGE_BY_NAME;
    if ($by_field_id) {
	    $val = $TF_USAGE_BY_ID[$field]['custom_empty_ok'];
	    if (!isset($val)) { $val = $TF_USAGE_BY_ID[$field]['empty_ok']; }
    } else {
	    $val = $TF_USAGE_BY_NAME[$field]['custom_empty_ok'];
	    if (!isset($val)) { $val = $TF_USAGE_BY_NAME[$field]['empty_ok']; }
    }
    return($val);
}

function pm_data_get_label($field, $by_field_id=false) {
    global $TF_USAGE_BY_ID,$TF_USAGE_BY_NAME;
    if ($by_field_id) {
	    $lbl = $TF_USAGE_BY_ID[$field]['custom_label'];
	    if (!isset($lbl)) { $lbl = $TF_USAGE_BY_ID[$field]['label']; }
    } else {
    	$lbl = $TF_USAGE_BY_NAME[$field]['custom_label'];
	    if (!isset($lbl)) { $lbl = $TF_USAGE_BY_NAME[$field]['label']; }
    }
    return($lbl);
}

function pm_data_is_special($field, $by_field_id=false) {
    global $TF_USAGE_BY_ID,$TF_USAGE_BY_NAME;
    return($by_field_id ? $TF_USAGE_BY_ID[$field]['special']: $TF_USAGE_BY_NAME[$field]['special']);
}

function pm_data_is_date_field($field, $by_field_id=false) {
    return(pm_data_get_display_type($field, $by_field_id) == 'DF');
}

function pm_data_is_text_field($field, $by_field_id=false) {
    return(pm_data_get_display_type($field, $by_field_id) == 'TF');
}

function pm_data_is_text_area($field, $by_field_id=false) {
    return(pm_data_get_display_type($field, $by_field_id) == 'TA'); 
}

function pm_data_is_select_box($field, $by_field_id=false) {
    return(pm_data_get_display_type($field, $by_field_id) == 'SB');
}

function pm_data_get_keep_history($field, $by_field_id=false) {
    global $TF_USAGE_BY_ID,$TF_USAGE_BY_NAME;
    if ($by_field_id) {
    	$val = $TF_USAGE_BY_ID[$field]['custom_keep_history'];
    	if (!isset($val)) { $val = $TF_USAGE_BY_ID[$field]['keep_history']; }
    } else {
    	$val = $TF_USAGE_BY_NAME[$field]['custom_keep_history'];
    	if (!isset($val)) { $val = $TF_USAGE_BY_NAME[$field]['keep_history']; }
    }
    return($val);
}

function pm_data_add_history ($field_name,$old_value,$project_task_id) {

    // If field is not to be kept in task change history then do nothing
    if (!pm_data_get_keep_history($field_name)) { return; }

	/*
		handle the insertion of history for these parameters
	*/
	if (!user_isloggedin()) {
		$user=100;
	} else {
		$user=user_getid();
	}

	$sql="insert into project_history(project_task_id,field_name,old_value,mod_by,date) ".
	    "VALUES ('$project_task_id','$field_name','$old_value','$user','".time()."')";
	return db_query($sql);
}

function pm_data_is_username_field($field, $by_field_id=false) {
    global $TF_USAGE_BY_ID;
    if ($by_field_id) {
	    $field = pm_data_get_field_name($field);
    }
    return(($field == 'Assigned to') || ($field == 'created_by'));
}

function pm_data_get_field_id($field_name) {
    global $TF_USAGE_BY_NAME;
    return($TF_USAGE_BY_NAME[$field_name]['project_field_id']);
}

function pm_data_get_value($field,$group_id,$value_id,$by_field_id=false) {
    /*
      simply return the value associated with a given value_id
      for a given field of a given group. If associated value not
      found then return value_id itself.
      By doing so if this function is called by mistake on a field with type
      text area or text field then it returns the text itself.
    */

    // created_by,group_project_id and assigned_to fields are special select box fields
    if (($field == 'Assigned to') || ($field == 'created_by')) {
	    return user_getname($value_id);
	} else if ( $field == 'group_project_id' ) {
	    return pm_data_get_group_name($value_id);
    } else if (pm_data_is_date_field($field)) {
	    return format_date($sys_datefmt,$value_id);
    }

    if ($by_field_id) {
	    $field_id = $field;
    } else {
	    $field_id = pm_data_get_field_id($field);
    }

    // Look for project specific values first...
    $sql="SELECT * FROM project_field_value ".
	"WHERE  project_field_id='$field_id' AND group_id='$group_id' ".
	"AND value_id='$value_id'";
    $result=db_query($sql);
    if ($result && db_numrows($result) > 0) {
	    return db_result($result,0,'value');
    } 

    // ... if it fails, look for system wide default values (group_id=100)...
    $sql="SELECT * FROM project_field_value ".
	"WHERE  project_field_id='$field_id' AND group_id='100' ".
	"AND value_id='$value_id'";
    $result=db_query($sql);
    if ($result && db_numrows($result) > 0) {
	    return db_result($result,0,'value');
    } 
    
    // No value found for this value id !!!
    return $value_id.'(Error - Not Found)';

}

function pm_data_get_field_name($field_id) {
    global $TF_USAGE_BY_ID;
    return($TF_USAGE_BY_ID[$field_id]['field_name']);
}

function pm_data_get_display_size($field, $by_field_id=false) {
    global $TF_USAGE_BY_ID,$TF_USAGE_BY_NAME;
    if ($by_field_id) {
    	$val = $TF_USAGE_BY_ID[$field]['custom_display_size'];
    	if (!isset($val)) { $val = $TF_USAGE_BY_ID[$field]['display_size']; }
    } else {
    	$val = $TF_USAGE_BY_NAME[$field]['custom_display_size'];
    	if (!isset($val)) { $val = $TF_USAGE_BY_NAME[$field]['display_size']; }
    }
    return(explode('/',$val));
}

function pm_data_get_history ($project_task_id=false) {
	$sql="select project_history.field_name,project_history.old_value,project_history.date,user.user_name ".
		"FROM project_history,user ".
		"WHERE project_history.mod_by=user.user_id ".
		"AND project_history.field_name <> 'details' ".
		"AND project_task_id='$project_task_id' ORDER BY project_history.date DESC";
	return db_query($sql);
}

function pm_data_get_field_predefined_values ($field, $group_id=false, $checked=false,$by_field_id=false,$active_only=true) {

    /*
             Return all possible values for a select box field
             Rk: if the checked value is given then it means that we want this value
                  in the list in any case (even if it is hidden and active_only is requested)
       */
    $field_id = ($by_field_id ? $field : pm_data_get_field_id($field));
    $field_name = ($by_field_id ? pm_data_get_field_name($field) : $field);

    // The "Assigned_to" box requires some special processing
    // because possible values  are project members) and they are
    // not stored in the project_field_value table but in the user_group table
    if ($field_name == 'Assigned to') {
	    $res_value = pm_data_get_technicians($group_id);
    } else if ($field_name == 'created_by') {
	    $res_value = pm_data_get_submitters($group_id);
    } else {

    	// If only active field
    	if ($active_only) {
    	    if ($checked) {
    		    $status_cond = "AND  (status IN ('A','P') OR value_id='$checked') ";
    	    } else {
    		    $status_cond = "AND  status IN ('A','P') ";
    	    }
    	}
    
    	// CAUTION !! the fields value_id and value must be first in the
    	// select statement because the output is used in the html_build_select_box
    	// function
    
    	// Look for project specific values first
    	$sql="SELECT value_id,value,project_fv_id,project_field_id,group_id,description,order_id,status ".
    	    "FROM project_field_value ".
    	    "WHERE group_id=$group_id AND project_field_id= $field_id ".
    	    $status_cond." ORDER BY order_id ASC";
    	$res_value = db_query($sql);
    	$rows=db_numrows($res_value);
    	
    	// If no specific value for this group then look for default values
    	if ($rows == 0) {
    	    $sql="SELECT value_id,value,project_fv_id,project_field_id,group_id,description,order_id,status ".
    		"FROM project_field_value ".
    		"WHERE group_id=100 AND project_field_id=$field_id ".
    		$status_cond." ORDER BY order_id ASC";
    	    $res_value = db_query($sql);
    	    $rows=db_numrows($res_value);
    	}
    }

    return($res_value);

}

function pm_data_get_followups ($project_task_id=false) {
    $sql="SELECT project_history.project_history_id,project_history.field_name,project_history.old_value,project_history.date,user.user_name ".
	"FROM project_history,user ".
	"WHERE project_history.project_task_id='$project_task_id' ".
	"AND project_history.field_name = 'details' ".
	"AND project_history.mod_by=user.user_id ".
	"ORDER BY project_history.date DESC";
	return db_query($sql);
}

function pm_data_get_notification_with_labels($user_id) {
    $sql = 'SELECT role_label,event_label,notify FROM project_notification_role, project_notification_event,project_notification '.
	"WHERE project_notification.role_id=project_notification_role.role_id AND ".
	"project_notification.event_id=project_notification_event.event_id AND user_id='$user_id'";
    return db_query($sql);
}

function pm_data_get_commenters($project_task_id) {
    $sql="SELECT DISTINCT mod_by FROM project_history ".
	"WHERE project_history.project_task_id='$project_task_id' ".
	"AND project_history.field_name = 'details' ";
    return db_query($sql);
}

function pm_data_is_used($field, $by_field_id=false) {
    global $TF_USAGE_BY_ID,$TF_USAGE_BY_NAME;
    return($by_field_id ? $TF_USAGE_BY_ID[$field]['use_it']: $TF_USAGE_BY_NAME[$field]['use_it']);
}

function pm_data_get_default_value($field,  $by_field_id=false) {
    global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
    /*
      Return the default value associated to a field_name as defined in the
      bug table (SQL definition)
      */
    if ($by_field_id) {
	    $field = pm_data_get_field_name($field);
    }
    
    if (pm_data_is_date_field($field)) {
        // Special case for date: default value = today
	    return time();
    }

    $result = db_query('describe project_task '.$field);
    return (db_result($result,0,'Default'));
 
}

function pm_data_get_attached_files ($project_task_id=false) {
	$sql="SELECT project_file_id,filename,filesize,description,date,user.user_name ".
		"FROM project_file,user ".
		"WHERE submitted_by=user.user_id ".
		"AND project_task_id='$project_task_id' ORDER BY date DESC";
	return db_query($sql);
}

function pm_data_update_usage($field_name,$group_id,$label,$description,
				$use_it,$rank,$display_size,$empty_ok=0,
				$keep_history=0,$show_on_add_members=0,$show_on_add=0)
{
    global $feedback;
    /*
      Update a field settings in the bug_usage_table
      Rk: All the show_on_xxx boolean parameters are set to 0 by default because their
           values come from checkboxes and if not checked the form variable
           is not set at all. It must be 0 to be ok with the SQL statement
      */

    // if it's a required field then make sure the use_it flag is true
    if (pm_data_is_required($field_name)) {
		$use_it = 1;
	}

    $field_id = pm_data_get_field_id($field_name);

    // if it's a custom field then take label into account else store NULL
    //    if (bug_data_is_custom($field_name)) {
	$lbl = isset($label) ? "'$label'" : 'NULL';
	$desc = isset($description) ? "'$description'" : 'NULL';
	$disp_size = isset($display_size) ? "'$display_size'" : 'NULL';
	$empty = isset($empty_ok) ? "'$empty_ok'" : 'NULL';
	$keep_hist = isset($keep_history) ? "'$keep_history'" : 'NULL';
	//    } else {
	//	$lbl = $desc = $disp_size = $empty = $keep_hist = "NULL";
	//    }

    // See if this field usage exists in the table for this project
    $sql = 'SELECT pm_field_id FROM pm_field_usage '.
	"WHERE pm_field_id='$field_id' AND group_id='$group_id'";
    $result = db_query($sql);
    $rows = db_numrows($result);

    // if it does exist then update it else insert a new usage entry for this field.
    if ($rows) {
	$sql = 'UPDATE pm_field_usage '.
	    "SET use_it='$use_it',show_on_add='$show_on_add',".
	    "show_on_add_members='$show_on_add_members',place='$rank', ".
	    "custom_label=$lbl,  custom_description=$desc,".
	    "custom_display_size=$disp_size,  custom_empty_ok=$empty,".
	    "custom_keep_history=$keep_hist ".
	    "WHERE pm_field_id='$field_id' AND group_id='$group_id'";
	$result = db_query($sql);
    } else {
	$sql = 'INSERT INTO  pm_field_usage  (pm_field_id, group_id,use_it,show_on_add,'.
	    'show_on_add_members,place,custom_label,custom_description,custom_displaY_size,'.
	    'custom_empty_ok, custom_keep_history) '.
	    "VALUES ('$field_id','$group_id','$use_it','$show_on_add',".
	    "'$show_on_add_members','$rank',$lbl,$desc,$disp_size,$empty,$keep_hist )";
	$result = db_query($sql);
    }

    if (db_affected_rows($result) < 1) {
		$feedback .= ' UPDATE OF FIELD  USAGE FAILED ';
		$feedback .= ' - '.db_error();
    } else {
		$feedback .= ' Field usage updated ';
    }

}

function pm_data_is_required($field, $by_field_id=false) {
    global $TF_USAGE_BY_ID,$TF_USAGE_BY_NAME;
    return($by_field_id ? $TF_USAGE_BY_ID[$field]['required']: $TF_USAGE_BY_NAME[$field]['required']);
}

function pm_data_reset_usage($field_name,$group_id)
{
    global $feedback;
    /*
      Reset a field settings to its defaults usage (values are untouched). The defaults
      always belong to group_id 100 (None) so make sure we don;t delete entries for
      group 100
      */
    $field_id = pm_data_get_field_id($field_name);
    if ($group_id != 100) {
        $sql = "DELETE FROM pm_field_usage ".
            "WHERE group_id='$group_id' AND pm_field_id='$field_id'";
        db_query($sql);
	$feedback .= ' Field value successfully reset to defaults ';

    }
}

function pm_data_is_custom($field, $by_field_id=false) {
    global $TF_USAGE_BY_ID,$TF_USAGE_BY_NAME;
    return($by_field_id ? $TF_USAGE_BY_ID[$field]['custom']: $TF_USAGE_BY_NAME[$field]['custom']);
}

?>