<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$


// This is the SQL query to retrieve all the tasks which depends on another task

$sql = 'SELECT project_assigned_to.project_task_id,'.
"$group_id AS group_id,user.user_name AS assigned_to ".
'FROM project_assigned_to, project_task, project_group_list,user '.
'WHERE project_assigned_to.project_task_id= project_task.project_task_id AND '.
'project_task.group_project_id=project_group_list.group_project_id AND '.
'project_assigned_to.assigned_to_id=user.user_id AND '.
"project_group_list.group_id='$group_id' ";

$col_list = array('project_task_id','group_id','assigned_to');
$lbl_list = array('project_task_id' => 'Task ID',
		  'group_id' => 'Group ID',
		  'assigned_to' => 'Assigned To');

$dsc_list = array('project_task_id' => 'Unique task identifier',
		  'group_id' => 'Unique project identifier',
		  'assigned_to' => 'Project members the task  is assigned to');
$tbl_name = 'task_assigned_to';

$eol = "\n";

//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);

if ($export == 'task_assigned_to') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename='.$tbl_name.'_'.$dbname.'.csv');

	echo build_csv_header($col_list, $lbl_list).$eol;
	
	while ($arr = db_fetch_array($result)) {
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>Task Assignees Export</h3>';
	if ($result) {
	    echo '<P>No task assignee  found. Could not generate an export.';
	} else {
	    echo '<P>Error while accessing your task assignees database. Please report the error to the '.$GLOBALS['sys_name'].' Administrator';
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "task_assigned_to_format") {

    echo '<h3>Task Assignees Export Format</h3> The Task Assignees
export provides you with the following fields. The sample values i
ndicate what the field data types are. <p>';
    
    $record = pick_a_record_at_random($result, $rows, $col_list);   
    display_exported_fields($col_list,$lbl_list,$dsc_list,$record);

} else if ($export == "project_db") {

    // make sure the database name is not the same as the 
    // CodeX database name !!!!
    if ($dbname != $sys_dbname) {

	// Let's create the project database if it does not exist
	// Drop the existing table and create a fresh one
	db_project_create($dbname);
	db_project_query($dbname,'DROP TABLE IF EXISTS '.$tbl_name);
	
	$sql_create = "CREATE TABLE $tbl_name (".
	    'task_id INTEGER, group_id INTEGER, assigned_to VARCHAR(255))';

	$res = db_project_query($dbname, $sql_create);

	// extract data from the task table and insert them into
	// the project database table. Do it in one shot here.
	if ($res) {
	    while ($arr = db_fetch_array($result)) {
		insert_record_in_table($dbname, $tbl_name, $col_list, $arr);
	    }

	} else {
	    $feedback .= 'Error in Create project '.$tbl_name.' table:'.db_project_error();
	}

    } else {
	$feedback .= "SECURITY VIOLATION!!! Unauthorized database name: $dbname";
    }
   
}

?>
