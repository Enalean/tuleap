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

function pm_data_get_dependent_tasks ($project_task_id) {
	$sql="SELECT is_dependent_on_task_id ".
		"FROM project_dependencies ".
		"WHERE project_task_id='$project_task_id'";
	return db_query($sql);
}

function pm_data_get_assigned_to ($project_task_id) {
	$sql="SELECT assigned_to_id ".
		"FROM project_assigned_to ".
		"WHERE project_task_id='$project_task_id'";
	return db_query($sql);
}

function pm_data_get_statuses () {
	$sql='SELECT * FROM project_status';
	return db_query($sql);
}

function pm_data_get_status_name($string) {
	/*
		simply return status_name from bug_status
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

function pm_data_create_history ($field_name,$old_value,$project_task_id) {
	global $feedback;
	/*
		handle the insertion of history for these parameters
	*/
	$sql="insert into project_history(project_task_id,field_name,old_value,mod_by,date) ".
		"VALUES ('$project_task_id','$field_name','$old_value','".user_getid()."','".time()."')";
	$result=db_query($sql);
	if (!$result) {
		$feedback .= ' ERROR IN AUDIT TRAIL - '.db_error();
		error_set_true();
		error_set_string(db_error());
	}
}

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

function pm_data_create_task ($group_project_id,$start_month,$start_day,$start_year,$end_month,$end_day,
		$end_year,$summary,$details,$percent_complete,$priority,$hours,$assigned_to,$dependent_on,$bug_id=false) {

	global $feedback;
	if (!$group_project_id || !$summary || !$details || !$priority) {
		exit_missing_param();
	}

	// if any parts of the start and end dates is undefined then 
	// the whole date is undefined
	if ( !$start_month || !$start_day || !$start_year )
	    $start_date = 0;
	else 
	    $start_date = mktime(0,0,0,$start_month,$start_day,$start_year);


	if ( !$end_month || !$end_day || !$end_year )
	    $end_date = 0;
	else 
	    $end_date = mktime(0,0,0,$end_month,$end_day,$end_year);

	/*
		Enforce start date > end date
	*/
	if ($start_date && $end_date && ($start_date > $end_date)) {
		exit_error('Error','End Date Must Be Greater Than Begin Date');
	}

	$sql="INSERT INTO project_task (group_project_id,summary,details,percent_complete,".
		"priority,hours,start_date,end_date,".
		"created_by,status_id) VALUES ('$group_project_id','".htmlspecialchars($summary)."',".
		"'".htmlspecialchars($details)."','$percent_complete','$priority','$hours','".
		$start_date."','".
		$end_date."','".user_getid()."','1')";

	$result=db_query($sql);

	if (!$result) {
		$feedback .= ' ERROR INSERTING ROW '.db_error();
	} else {
		$feedback .= ' Successfully added task ';
		$project_task_id=db_insertid($result);
		/*
		  Insert a task dependency => the create task comes from the
		  Bugs menu (Create Task)
		*/
		if ($bug_id) {
		    $dep_tasks = util_result_column_to_array (bug_data_get_dependent_tasks($bug_id));
		    $dep_tasks[] = $project_task_id;
		    bug_data_update_dependent_tasks($dep_tasks,$bug_id);
		}
    
		pm_data_insert_assigned_to($assigned_to,$project_task_id);
		pm_data_insert_dependent_tasks($dependent_on,$project_task_id);
	}
}

function pm_data_update_task ($group_project_id,$project_task_id,$start_month,$start_day,$start_year,
		$end_month,$end_day,$end_year,$summary,$original_comment,$details,$percent_complete,$priority,$hours,
		$status_id,$assigned_to,$dependent_on,$new_group_project_id,$group_id) {

	if (!$group_project_id || !$project_task_id || !$status_id ||  !$summary || !$priority || !$new_group_project_id || !$group_id) {
		exit_missing_param();
	}

	$sql="SELECT * FROM project_task WHERE project_task_id='$project_task_id' AND group_project_id='$group_project_id'";

	$result=db_query($sql);

	if (db_numrows($result) < 1) {
		exit_permission_denied();
	}

	// if any parts of the start and end dates is undefined then 
	// the whole date is undefined
	if ( !$start_month || !$start_day || !$start_year )
	    $start_date = 0;
	else 
	    $start_date = mktime(0,0,0,$start_month,$start_day,$start_year);


	if ( !$end_month || !$end_day || !$end_year )
	    $end_date = 0;
	else 
	    $end_date = mktime(0,0,0,$end_month,$end_day,$end_year);

	/*
		Enforce start date > end date
	*/
	if ($start_date && $end_date && ($start_date > $end_date)) {
		exit_error('Error','End Date Must Be Greater Than Begin Date');
	}

	/*
		If changing subproject, verify the new subproject belongs to this project
	*/
	if ($group_project_id != $new_group_project_id) {
		$sql = "SELECT group_id FROM project_group_list WHERE group_project_id='$new_group_project_id'";
		
		if (db_result(db_query($sql),0,'group_id') != $group_id) {
			echo db_error();
			exit_error('Error','You can not put this task into the subproject of another group.');
		} else {
			pm_data_create_history ('subproject_id',$group_project_id,$project_task_id);
		}
	}

	/*
		See which fields changed during the modification
	*/

	if (db_result($result,0,'status_id') != $status_id)
		{ pm_data_create_history ('status_id',db_result($result,0,'status_id'),$project_task_id);  }

	if (db_result($result,0,'priority') != $priority)
		{ pm_data_create_history ('priority',db_result($result,0,'priority'),$project_task_id);  }

	if (db_result($result,0,'summary') != htmlspecialchars(stripslashes($summary)))
		{ pm_data_create_history ('summary',addslashes(db_result($result,0,'summary')),$project_task_id);  }

	if (db_result($result,0,'percent_complete') != $percent_complete)
		{ pm_data_create_history ('percent_complete',db_result($result,0,'percent_complete'),$project_task_id);  }

	if (db_result($result,0,'hours') != $hours)
		{ pm_data_create_history ('hours',db_result($result,0,'hours'),$project_task_id);  }

	if (db_result($result,0,'start_date') != $start_date)
		{ pm_data_create_history ('start_date',db_result($result,0,'start_date'),$project_task_id);  }

	if (db_result($result,0,'end_date') != $end_date)
		{ pm_data_create_history ('end_date',db_result($result,0,'end_date'),$project_task_id);  }

	/*
		See if Original Comment was modified
	*/
	if ( ($original_comment != '') &&
		($original_comment != db_result($result,0,'details')) ) {
		$set_oc_str = ",details='".htmlspecialchars($original_comment)."' ";
	} else {
		$set_oc_str = '';
	}

	/*
		Details field is handled a little differently
	*/
	if ($details != '') { pm_data_create_history ('details',htmlspecialchars($details),$project_task_id);  }

	pm_data_update_dependent_tasks($dependent_on,$project_task_id);
	pm_data_update_assigned_to($assigned_to,$project_task_id);
    
	/*
		Update the actual db record
	*/
	$sql="UPDATE project_task SET status_id='$status_id', priority='$priority',".
		"summary='".htmlspecialchars($summary)."',start_date='".
		$start_date."',end_date='".
		$end_date."',hours='$hours',".
		"percent_complete='$percent_complete', ".
		"group_project_id='$new_group_project_id' ".
		$set_oc_str.
		"WHERE project_task_id='$project_task_id' AND group_project_id='$group_project_id'";

	$result=db_query($sql);

	if (!$result) {
		exit_error('ERROR','Database update failed '.db_error());
	} else {
		$feedback .= ' Successfully Modified Task ';
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

?>
