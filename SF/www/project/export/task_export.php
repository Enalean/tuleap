<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$


// This is the SQL query to retrieve all the task history for this group

$sql = "SELECT project_task.project_task_id,'$group_id' AS group_id,".
'project_group_list.project_name AS subproject_id,'.
'project_task.summary, project_task.details, project_task.percent_complete,'.
'project_task.priority, project_task.hours, project_task.start_date,project_task.end_date,'.
'user.user_name AS created_by,project_status.status_name AS status '.
'FROM project_task, project_group_list,project_status,user '.
'WHERE (project_task.group_project_id=project_group_list.group_project_id AND '.
"project_group_list.group_id='$group_id') ".
'AND project_status.status_id=project_task.status_id '.
'AND user.user_id=project_task.created_by';

$col_list = array('project_task_id','group_id','subproject_id','summary',
		  'details','percent_complete','priority','hours',
		  'start_date','end_date','created_by','status',
		  'assigned_to','follow_ups','is_dependent_on_task_id');
$lbl_list = array('project_task_id' => 'Task ID',
		  'group_id' => 'Group ID',
		  'subproject_id' => 'Subproject',
		  'summary' => 'Task Summary',
		  'details' => 'Original Comment',
		  'percent_complete' => 'Percent Complete',
		  'priority' => 'Priority',
		  'hours' => 'Hours',
		  'start_date' => 'Start Date',
		  'end_date' => 'End Date',
		  'created_by' => 'Created By',
		  'status' => 'Status',
		  'assigned_to' => 'Assigned To',
		  'follow_ups' => 'Follow-up Comments',
		  'is_dependent_on_task_id'=> 'Depend on Task(s)');
$dsc_list = array('project_task_id' => 'Unique task identifier',
		  'group_id' => 'Unique project identifier',
		  'subproject_id' => 'Name of the subproject the task is in',
		  'summary' => 'One line description of the task',
		  'details' => 'Detailled description of the task',
		  'percent_complete' => 'How much of the task has already been completed',
		  'priority' => 'Priority',
		  'hours' => 'Effort spent on the task in hours',
		  'start_date' => 'Date when the task was started',
		  'end_date' => 'Date when the task was finished',
		  'created_by' => 'Project member who created the task',
		  'status' => 'Status (Open, Closed,...)',
		  'assigned_to' => 'List of project members the task is assigned to',
		  'follow_ups' => 'All follow-up comments in one chunck of text',
		  'is_dependent_on_task_id' => 'List of  tasks this task depends on');

$tbl_name = 'task';

$eol = "\n";

//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);


if ($export == 'task') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename=task_'.$dbname.'.csv');
	
	echo build_csv_header($col_list, $lbl_list).$eol;

	while ($arr = db_fetch_array($result)) {
	    prepare_task_record($group_id,$arr);
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>Task Export</h3>';
	if ($result) {
	    echo '<P>No task found. Could not generate an export.';
	} else {
	    echo '<P>Error while accessing your task database. Please report the error to the '.$GLOBALS['sys_name'].' Administrator';
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "task_format") {

    echo '<h3>Task Export Format</h3> The Task export provides you
with the following task fields. The sample values indicate what the
field data types are. <p>';

    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_task_record($group_id,$record);   
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
	    'task_id INTEGER, group_id INTEGER, subproject_id VARCHAR(255), '.
	    'summary VARCHAR(255), details TEXT, percent_complete INTEGER,'.
	    'priority INTEGER, hours FLOAT(10,2), start_date DATETIME,'.
	    'end_date DATETIME, created_by VARCHAR(255),'.
	    'status VARCHAR(255), assigned_to VARCHAR(255),'.
	    'follow_ups TEXT, is_dependent_on_task_id VARCHAR(255))';

	$res = db_project_query($dbname, $sql_create);

	// extract data from the task table and insert them into
	// the project database table
	if ($res) {
	    
	    while ($arr = db_fetch_array($result)) {
		prepare_task_record($group_id,$arr);
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
