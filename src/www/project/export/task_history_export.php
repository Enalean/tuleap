<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// 

$Language->loadLanguageMsg('project/project');

// This is the SQL query to retrieve all the task history for this group

$sql = "SELECT project_history.project_task_id,'$group_id' AS group_id,project_history.field_name,".
'project_history.old_value, user.user_name AS mod_by, project_history.date '.
' FROM project_history,project_task, project_group_list,user '.
'WHERE (project_history.project_task_id=project_task.project_task_id AND '.
'project_task.group_project_id=project_group_list.group_project_id AND '.
"project_group_list.group_id='$group_id' ) ".
'AND user.user_id=project_history.mod_by';

$col_list = array('project_task_id','group_id','field_name','old_value','mod_by','date');
$lbl_list = array('project_task_id' => $Language->getText('project_export_task_assigned_to_export','task_id'),
		  'group_id' => $Language->getText('project_export_bug_deps_export','g_id'),
		  'field_name' => $Language->getText('project_export_artifact_history_export','field_name'),
		  'old_value' => $Language->getText('project_export_artifact_history_export','old_val'),
		  'mod_by' => $Language->getText('project_export_artifact_history_export','mod_by'),
		  'date' => $Language->getText('project_export_artifact_history_export','mod_on'));
$dsc_list = array('project_task_id' => $Language->getText('project_export_task_assigned_to_export','task_id_desc'),
		  'group_id' => $Language->getText('project_export_bug_deps_export','g_id_desc'),
		  'field_name' => $Language->getText('project_export_artifact_history_export','field_name_desc'),
		  'old_value' => $Language->getText('project_export_artifact_history_export','old_val_desc'),
		  'mod_by' => $Language->getText('project_export_artifact_history_export','mod_by_desc'),
		  'date' => $Language->getText('project_export_artifact_history_export','mod_on_desc'));

$tbl_name = 'task_history';

$eol = "\n";

//echo "DBG -- $sql<br>";

$result=db_query($sql);
$rows = db_numrows($result);       

if ($export == 'task_history') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename=task_history_'.$dbname.'.csv');
		
	echo build_csv_header($col_list, $lbl_list).$eol;

	while ($arr = db_fetch_array($result)) {
	    prepare_task_history_record($arr);
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>'.$Language->getText('project_export_bug_deps_export','bug_deps_export','Task History').'</h3>';
	if ($result) {
	    echo '<P>'.$Language->getText('project_export_bug_deps_export','no_bug_deps_found','task history');
	} else {
	    echo '<P>'.$Language->getText('project_export_bug_deps_export','db_access_err',array('task history',$GLOBALS['sys_name']));
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == 'task_history_format') {

    echo $Language->getText('project_export_bug_deps_export','bug_deps_export_format',' Task History');

    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_task_history_record($record);    
    display_exported_fields($col_list,$lbl_list,$dsc_list,$record);
   
} else if ($export == 'project_db') {

    // make sure the database name is not the same as the 
    // CodeX database name !!!!
    if ($dbname != $sys_dbname) {

	// Let's create the project database if it does not exist
	// Drop the existing table and create a fresh one
	db_project_create($dbname);
	db_project_query($dbname,'DROP TABLE IF EXISTS '.$tbl_name);
	
	$sql_create = "CREATE TABLE $tbl_name (".
	    'task_id INTEGER, group_id INTEGER, field_name VARCHAR(255), '.
	    'old_value TEXT, mod_by VARCHAR(255), date DATETIME)';

	$res = db_project_query($dbname, $sql_create);

	// extract data from the task table and insert them into
	// the project database table
	if ($res) {
	    while ($arr = db_fetch_array($result)) {
		prepare_task_history_record($arr);
		insert_record_in_table($dbname, $tbl_name, $col_list, $arr);
	    }

	} else {
	    $feedback .= $Language->getText('project_export_artifact_deps_export','create_proj_err',array($tbl_name,db_project_error()));
	}

    } else {
	$feedback .= $Language->getText('project_export_artifact_deps_export','security_violation',$dbname);
    }
   
}

?>
