<?php

/*

	Simple way of wrapping our SQL so it can be 
	shared among the XML outputs and the PHP web front-end

	Also abstracts controls to update data

*/

function bug_data_get_categories ($group_id=false) {
	$sql="select bug_category_id,category_name from bug_category WHERE group_id='$group_id'";
	return db_query($sql);
}

function bug_data_get_groups ($group_id=false) {
	$sql="select bug_group_id,group_name from bug_group WHERE group_id='$group_id'";
	return db_query($sql);
}

function bug_data_get_resolutions () {
	$sql="select resolution_id,resolution_name from bug_resolution";
	return db_query($sql);
}

function bug_data_get_technicians ($group_id=false) {
	$sql="SELECT user.user_id,user.user_name ".
		"FROM user,user_group ".
		"WHERE user.user_id=user_group.user_id ".
		"AND user_group.bug_flags IN (1,2) ".
		"AND user_group.group_id='$group_id' ".
		"ORDER BY user.user_name";
	return db_query($sql);
}

function bug_data_get_statuses () {
	$sql="select * from bug_status";
	return db_query($sql);
}

function bug_data_get_tasks ($group_id=false) {
	/*
		Get the tasks for this project
	*/
	$sql="SELECT project_task.project_task_id,project_task.summary ".
	"FROM project_task,project_group_list ".
	"WHERE project_task.group_project_id=project_group_list.group_project_id ".
	"AND project_task.status_id <> '3' ".
	"AND project_group_list.group_id='$group_id' ORDER BY project_task_id DESC LIMIT 100";
	return db_query($sql);
}

function bug_data_get_dependent_tasks ($bug_id=false) {
	/*
		Get the list of ids this is dependent on
	*/
	$sql="SELECT is_dependent_on_task_id FROM bug_task_dependencies WHERE bug_id='$bug_id'";
	return db_query($sql);
}

function bug_data_get_valid_bugs ($group_id=false,$bug_id='') {
	$sql="SELECT bug_id,summary ".
		"FROM bug ".
		"WHERE group_id='$group_id' ".
		"AND bug_id <> '$bug_id' AND bug.resolution_id <> '2' ORDER BY bug_id DESC LIMIT 100";
	return db_query($sql);
}

function bug_data_get_dependent_bugs ($bug_id=false) {
	/*
		Get the list of ids this is dependent on
	*/
	$sql="SELECT is_dependent_on_bug_id FROM bug_bug_dependencies WHERE bug_id='$bug_id'";
	return db_query($sql);
}

function bug_data_get_followups ($bug_id=false) {
	$sql="select bug_history.field_name,bug_history.old_value,bug_history.date,user.user_name ".
		"FROM bug_history,user ".
		"WHERE bug_history.mod_by=user.user_id ".
		"AND bug_history.field_name = 'details' ".
		"AND bug_id='$bug_id' ORDER BY bug_history.date DESC";
	return db_query($sql);
}

function bug_data_get_history ($bug_id=false) {
	$sql="select bug_history.field_name,bug_history.old_value,bug_history.date,user.user_name ".
		"FROM bug_history,user ".
		"WHERE bug_history.mod_by=user.user_id ".
		"AND bug_history.field_name <> 'details' ".
		"AND bug_id='$bug_id' ORDER BY bug_history.date DESC";
	return db_query($sql);
}

function bug_data_add_history ($field_name,$old_value,$bug_id) {
	/*
		handle the insertion of history for these parameters
	*/
	if (!user_isloggedin()) {
		$user=100;
	} else {
		$user=user_getid();
	}

	$sql="insert into bug_history(bug_id,field_name,old_value,mod_by,date) ".
		"VALUES ('$bug_id','$field_name','$old_value','$user','".time()."')";
	return db_query($sql);
}

//
//	Handles security
/*
function bug_data_mass_update ($group_id,$bug_id,$status_id,$priority,$category_id,$assigned_to,$bug_group_id,$resolution_id) {
	global $feedback;
	//bug_id is an array of bugs that were checked. The other params are not arrays.
	if (!$group_id || !$bug_id || !$status_id || !$priority || !$category_id || !$assigned_to || !$bug_group_id || !$resolution_id) {
		exit_missing_param();
	}
	$count=count($bug_id);
	if ($count > 0) {

	}
}
*/
//
//       Handles security
//
function bug_data_handle_update ($group_id,$bug_id,$status_id,$priority,$category_id,$assigned_to,$summary,$bug_group_id,$resolution_id,$details,$dependent_on_task,$dependent_on_bug,$canned_response) {
	global $feedback;

	if (!$group_id || !$bug_id || !$status_id || !$priority || !$category_id || !$assigned_to || !$summary || !$bug_group_id || !$resolution_id || !$canned_response) {
		//force inclusion of parameters
		exit_missing_param();
	}

	//get this bug from the db
	$sql="SELECT * FROM bug WHERE bug_id='$bug_id'";
	$result=db_query($sql);

	if (!((db_numrows($result) > 0) && (user_ismember(db_result($result,0,'group_id'),'B2')))) {
		//verify permissions
		exit_permission_denied();
	}

	/*
		See which fields changed during the modification
	*/
	if (db_result($result,0,'status_id') != $status_id)
		{ bug_data_add_history ('status_id',db_result($result,0,'status_id'),$bug_id);  }
	if (db_result($result,0,'priority') != $priority)
		{ bug_data_add_history ('priority',db_result($result,0,'priority'),$bug_id);  }
	if (db_result($result,0,'category_id') != $category_id)
		{ bug_data_add_history ('category_id',db_result($result,0,'category_id'),$bug_id);  }
	if (db_result($result,0,'assigned_to') != $assigned_to)
		{ bug_data_add_history ('assigned_to',db_result($result,0,'assigned_to'),$bug_id);  }
	if (db_result($result,0,'summary') != stripslashes(htmlspecialchars($summary)))
		{ bug_data_add_history ('summary',addslashes(db_result($result,0,'summary')),$bug_id);  }
	if (db_result($result,0,'bug_group_id') != $bug_group_id)
		{ bug_data_add_history ('bug_group_id',db_result($result,0,'bug_group_id'),$bug_id);  }
	if (db_result($result,0,'resolution_id') != $resolution_id)
		{ bug_data_add_history ('resolution_id',db_result($result,0,'resolution_id'),$bug_id);  }

	/*
		Handle if canned response used
	*/
	if ($canned_response != 100) {
		$sql="SELECT * FROM bug_canned_responses WHERE bug_canned_id='$canned_response'";
		$res3=db_query($sql);

		if ($res3 && db_numrows($res3) > 0) {
			$details = addslashes(util_unconvert_htmlspecialchars(db_result($res3,0,'body')));
			$feedback .= ' Canned Response Used ';
		} else {
			$feedback .= ' Unable to use Canned Response ';
			echo db_error();
		}
	}

	/*
		Details field is handled a little differently
	*/
	if ($details != '')
		{ bug_data_add_history ('details',htmlspecialchars($details),$bug_id);  }

	/*
		Enter the timestamp if we are changing to closed
	*/
	if ($status_id == "3") {

		$now=time();
		$close_date="close_date='$now',";
		bug_data_add_history ('close_date',db_result($result,0,'close_date'),$bug_id);

	} else {

		$close_date='';

	}

	/*
		DELETE THEN Insert the list of task dependencies
	*/
	bug_data_update_dependent_tasks($dependent_on_task,$bug_id);

	/*
		DELETE THEN Insert the list of bug dependencies
	*/

	bug_data_update_dependent_bugs($dependent_on_bug,$bug_id);


	/*
		Finally, update the bug itself
	*/
	$sql="UPDATE bug SET status_id='$status_id', $close_date priority='$priority', category_id='$category_id', ".
		"assigned_to='$assigned_to', summary='".htmlspecialchars($summary)."',".
		"bug_group_id='$bug_group_id',resolution_id='$resolution_id' WHERE bug_id='$bug_id'";
	$result=db_query($sql);

	if (!$result) {
		exit_error('UPDATE FAILED','UPDATE FAILED');
	} else {
		$feedback .= " Successfully Modified Bug ";
	}

}

function bug_data_insert_dependent_bugs($array,$bug_id) {
	global $feedback;
	/*
		Insert the list of dependencies
	*/
	$depend_count=count($array);
	if ($depend_count < 1) {
		//if no tasks selected, insert task "none"
		$sql="INSERT INTO bug_bug_dependencies VALUES ('','$bug_id','100')";
		$result=db_query($sql);
	} else {
		for ($i=0; $i<$depend_count; $i++) {
			if (($depend_count > 1) && ($array[$i]==100)) {
				//don't insert the row if there's more
				//than 1 item selected and this item is the "none task"
			} else {
				$sql="INSERT INTO bug_bug_dependencies VALUES ('','$bug_id','$array[$i]')";
				//echo "\n$sql";
				$result=db_query($sql);

				if (!$result) {
					$feedback .= ' ERROR inserting dependent_bugs '.db_error();
				}
			}
		}
	}
}

function bug_data_update_dependent_bugs($array,$bug_id) {
	/*
		DELETE THEN Insert the list of dependencies
	*/
	$toss=db_query("DELETE FROM bug_bug_dependencies WHERE bug_id='$bug_id'");
	bug_data_insert_dependent_bugs($array,$bug_id);
}

function bug_data_insert_dependent_tasks($array,$bug_id) {
	global $feedback;
	/*
		Insert the list of dependencies
	*/
	$depend_count=count($array);
	if ($depend_count < 1) {
		//if no tasks selected, insert task "none"
		$sql="INSERT INTO bug_task_dependencies VALUES ('','$bug_id','100')";
		$result=db_query($sql);
	} else {
		for ($i=0; $i<$depend_count; $i++) {
			if (($depend_count > 1) && ($array[$i]==100)) {
				//don't insert the row if there's more
				//than 1 item selected and this item is the "none task"
			} else {
				$sql="INSERT INTO bug_task_dependencies VALUES ('','$bug_id','$array[$i]')";
				//echo "\n$sql";
				$result=db_query($sql);

				if (!$result) {
					$feedback .= ' ERROR inserting dependent_tasks '.db_error();
				}
			}
		}
	}
}

function bug_data_update_dependent_tasks($array,$bug_id) {
	/*
		DELETE THEN Insert the list of dependencies
	*/
	$toss=db_query("DELETE FROM bug_task_dependencies WHERE bug_id='$bug_id'");
	bug_data_insert_dependent_tasks($array,$bug_id);
}

function bug_data_create_bug($group_id,$summary,$details,$category_id,$bug_group_id,$priority,$assigned_to) {
	global $feedback;

	if (!$category_id) {
		//default category
		$category_id=100;
	}
	if (!$bug_group_id) {
		//default group
		$bug_group_id=100;
	}
	if (!$assigned_to) {
		//default assignment
		$assigned_to=100;
	}
	if (!$priority) {
		//default priority
		$priority=5;
	}

	//we don't force them to be logged in to submit a bug
	if (!user_isloggedin()) {
		$user=100;
	} else {
		$user=user_getid();
	}

	if (!$group_id || !$summary || !$details) {
		exit_missing_param();
	}

	//first check to make sure this wasn't double-submitted
	$res=db_query("SELECT * FROM bug WHERE submitted_by='$user' AND summary='$summary'");
	if ($res && db_numrows($res) > 0) {
		$feedback = ' ERROR - DOUBLE SUBMISSION. You are trying to double-submit this bug. Please don\'t do that ';
		return 0;		
	}

	$sql="INSERT INTO bug (close_date,group_id,status_id,priority,category_id,".
		"submitted_by,assigned_to,date,summary,details,bug_group_id,resolution_id) ".
		"VALUES ('0','$group_id','1','$priority','$category_id','$user','$assigned_to','".time()."','".
		htmlspecialchars($summary)."','".htmlspecialchars($details)."','$bug_group_id','100')";
	$result=db_query($sql);
	$bug_id=db_insertid($result);

	/*
		set up the default rows in the dependency table
		both rows will be dependent on id=100
	*/
	bug_data_insert_dependent_bugs($array,$bug_id);
	bug_data_insert_dependent_tasks($array,$bug_id);

	//now return the bug_id
	return $bug_id;
}

function bug_data_get_status_name($string) {
	/*
		simply return status_name from bug_status
	*/
	$sql="select * from bug_status WHERE status_id='$string'";
	$result=db_query($sql);
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,'status_name');
	} else {
		return 'Error - Not Found';
	}
}

function bug_data_get_category_name($string) {
	/*
		simply return the category_name from bug_category
	*/
	$sql="select * from bug_category WHERE bug_category_id='$string'";
	$result=db_query($sql);
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,'category_name');
	} else {
		return 'Error - Not Found';
	}
}

function bug_data_get_resolution_name($resolution_id) {
	/*
		Simply return the resolution name for this id
	*/

	$sql="select * from bug_resolution WHERE resolution_id='$resolution_id'";
	$result=db_query($sql);
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,'resolution_name');
	} else {
		return 'Error - Not Found';
	}
}

function bug_data_get_group_name($bug_group_id) {
	/*
		Simply return the resolution name for this id
	*/

	$sql="select * from bug_group WHERE bug_group_id='$bug_group_id'";
	$result=db_query($sql);
	if ($result && db_numrows($result) > 0) {
		return db_result($result,0,'group_name');
	} else {
		return 'Error - Not Found';
	}
}

function bug_data_get_canned_responses ($group_id) {
	/*
		Show defined and site-wide responses
	*/
	$sql="SELECT bug_canned_id,title,body FROM bug_canned_responses WHERE ".
		"(group_id='$group_id' OR group_id='0')";

	// return handle for use by select box
	return db_query($sql);
}

?>
