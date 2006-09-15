<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
// http://codex.xerox.com
//
// $Id$

$Language->loadLanguageMsg('project/project');

// This is the SQL query to retrieve all the task history for this group

$sql = "SELECT support.support_id,'$group_id' AS group_id,".
'support_category.category_name AS support_category, support.summary,'.
'support.priority, user.user_name AS submitted_by, user2.user_name AS assigned_to,'.
'support.open_date, support.close_date, support_status.status_name AS status '.
'FROM support, support_category,support_status, user, user user2 '.
"WHERE (support.group_id='$group_id' AND ".
'support.support_category_id=support_category.support_category_id  AND '.
'support.support_status_id=support_status.support_status_id AND '.
'user.user_id=support.submitted_by AND user2.user_id=support.assigned_to)';

$col_list = array('support_id','group_id','support_category','summary',
		  'priority','submitted_by','assigned_to', 'open_date','close_date',
		  'status','follow_ups');
$lbl_list = array('support_id' => $Language->getText('project_export_support_request_export','support_id'),
		  'group_id' => $Language->getText('project_export_bug_deps_export','g_id'),
		  'support_category' => $Language->getText('project_export_support_request_export','category'),
		  'summary' => $Language->getText('project_export_support_request_export','summary'),
		  'priority' => $Language->getText('project_export_support_request_export','priority'),
		  'submitted_by' => $Language->getText('project_export_support_request_export','submitted_by'),
		  'assigned_to' => $Language->getText('project_export_support_request_export','assigned_to'),
		  'open_date' => $Language->getText('project_export_support_request_export','open_date'),
		  'close_date' => $Language->getText('project_export_support_request_export','close_date'),
		  'status' => $Language->getText('global','status'),
		  'follow_ups' => $Language->getText('project_export_artifact_export','follow_up_comments'));
$dsc_list = array('support_id' => $Language->getText('project_export_support_request_export','support_id_desc'),
		  'group_id' => $Language->getText('project_export_bug_deps_export','g_id_desc'),
		  'support_category' => $Language->getText('project_export_support_request_export','category_desc'),
		  'summary' => $Language->getText('project_export_support_request_export','summary_desc'),
		  'priority' => $Language->getText('project_export_support_request_export','priority'),
		  'submitted_by' => $Language->getText('project_export_support_request_export','submitted_by_desc'),
		  'assigned_to' => $Language->getText('project_export_support_request_export','assigned_to_desc'),
		  'open_date' => $Language->getText('project_export_support_request_export','open_date_desc'),
		  'close_date' => $Language->getText('project_export_support_request_export','close_date_desc'),
		  'status' => $Language->getText('project_export_support_request_export','status_desc'),
		  'follow_ups' => $Language->getText('project_export_support_request_export','follow_up_desc'));

$tbl_name = 'support_request';

$eol = "\n";

$result=db_query($sql);
$rows = db_numrows($result);


if ($export == 'support_request') {

    // Send the result in CSV format
    if ($result && $rows > 0) {
	
	header ('Content-Type: text/csv');
	header ('Content-Disposition: filename=support_request_'.$dbname.'.csv');
	
	echo build_csv_header($col_list, $lbl_list).$eol;

	while ($arr = db_fetch_array($result)) {
	    prepare_support_request_record($group_id,$arr);
	    echo build_csv_record($col_list, $arr).$eol;
	}

    } else {

	project_admin_header(array('title'=>$pg_title));

	echo '<h3>'.$Language->getText('project_export_bug_deps_export','bug_deps_export','Support Request').'</h3>';
	if ($result) {
	    echo '<P>'.$Language->getText('project_export_bug_deps_export','no_bug_deps_found','support request');
	} else {
	    echo '<P>'.$Language->getText('project_export_bug_deps_export','db_access_err',array('support request',$GLOBALS['sys_name']));
	    echo '<br>'.db_error();
	}
	site_project_footer( array() );
    }


} else if ($export == "support_request_format") {

    echo $Language->getText('project_export_bug_deps_export','bug_deps_export_format',' Support Request');

    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_support_request_record($group_id,$record);   
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
	    'support_id INTEGER, group_id INTEGER, support_category VARCHAR(255), '.
	    'summary VARCHAR(255), priority INTEGER, '.
	    'submitted_by VARCHAR(255), assigned_to VARCHAR(255), '.
	    'open_date DATETIME, close_date DATETIME, '.
	    'status VARCHAR(255), follow_ups TEXT)';

	$res = db_project_query($dbname, $sql_create);

	// extract data from the support requests table and insert them into
	// the project database table
	if ($res) {
	    
	    while ($arr = db_fetch_array($result)) {
		prepare_support_request_record($group_id,$arr);
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
