<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$

$LANG->loadLanguageMsg('project/project');

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
$lbl_list = array('project_task_id' => $LANG->getText('project_export_task_assigned_to_export','task_id'),
		  'group_id' => $LANG->getText('project_export_bug_deps_export','g_id'),
		  'subproject_id' => $LANG->getText('project_export_task_export','subproject'),
		  'summary' => $LANG->getText('project_export_task_export','summary'),
		  'details' => $LANG->getText('project_export_task_export','details'),
		  'percent_complete' => $LANG->getText('project_export_task_export','percent_complete'),
		  'priority' => $LANG->getText('project_export_support_request_export','priority'),
		  'hours' => $LANG->getText('project_export_task_export','hours'),
		  'start_date' => $LANG->getText('project_export_task_export','start_date'),
		  'end_date' => $LANG->getText('project_export_task_export','end_date'),
		  'created_by' => $LANG->getText('project_export_task_export','created_by'),
		  'status' => $LANG->getText('project_export_support_request_export','status'),
		  'assigned_to' => $LANG->getText('project_export_support_request_export','assigned_to'),
		  'follow_ups' => $LANG->getText('project_export_artifact_export','follow_up_comments'),
		  'is_dependent_on_task_id'=> $LANG->getText('project_export_task_export','depend_on_task'));
$dsc_list = array('project_task_id' => $LANG->getText('project_export_task_assigned_to_export','task_id_desc'),
		  'group_id' => $LANG->getText('project_export_bug_deps_export','g_id_desc'),
		  'subproject_id' => $LANG->getText('project_export_task_export','subproject_desc'),
		  'summary' => $LANG->getText('project_export_task_export','summary_desc'),
		  'details' => $LANG->getText('project_export_task_export','details_desc'),
		  'percent_complete' => $LANG->getText('project_export_task_export','percent_complete_desc'),
		  'priority' => $LANG->getText('project_export_support_request_export','priority'),
		  'hours' => $LANG->getText('project_export_task_export','hours_desc'),
		  'start_date' => $LANG->getText('project_export_task_export','start_date_desc'),
		  'end_date' => $LANG->getText('project_export_task_export','end_date_desc'),
		  'created_by' => $LANG->getText('project_export_task_export','created_by_desc'),
		  'status' => $LANG->getText('project_export_support_request_export','status_desc'),
		  'assigned_to' => $LANG->getText('project_export_task_assigned_to_export','assigned_to_desc'),
		  'follow_ups' => $LANG->getText('project_export_artifact_export','all_followup_comments'),
		  'is_dependent_on_task_id' => $LANG->getText('project_export_task_export','depend_on_task_desc'));

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

	echo '<h3>'.$LANG->getText('project_export_artifact_deps_export','bug_deps_export','Task').'</h3>';
	if ($result) {
	    echo '<P>'.$LANG->getText('project_export_artifact_deps_export','no_bug_deps_found','task');
	} else {
	    echo '<P>'.$LANG->getText('project_export_artifact_deps_export','db_access_err',array('task',$GLOBALS['sys_name']));
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "task_format") {

    echo $LANG->getText('project_export_artifact_deps_export','bug_deps_export_format',' Task');

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
	    $feedback .= $LANG->getText('project_export_artifact_deps_export','create_proj_err',array($tbl_name,db_project_error()));
	}

    } else {
	$feedback .= $LANG->getText('project_export_artifact_deps_export','security_violation',$dbname);
    }
   
}

?>
